<?php
/**
 * TSBD GitHub Updater
 *
 * Lightweight OTA updater — checks GitHub releases for newer versions
 * and injects update data into WordPress's plugin update system.
 *
 * @package TS_Bank_Donate
 */

defined( 'ABSPATH' ) || exit;

class TSBD_GitHub_Updater {

	/** @var string GitHub API endpoint */
	private $api_url;

	/** @var string Plugin basename (e.g. ts_bank_donate/ts-bank-donate.php) */
	private $plugin_basename;

	/** @var string Plugin slug */
	private $slug;

	/** @var string Current plugin version */
	private $version;

	/** @var string Transient cache key */
	private $cache_key = 'tsbd_github_update';

	/** @var int Cache duration (12 hours) */
	private $cache_ttl = 43200;

	/**
	 * Constructor.
	 *
	 * @param string $github_user GitHub username/org.
	 * @param string $github_repo GitHub repository name.
	 * @param string $plugin_file Main plugin file (__FILE__ from ts-bank-donate.php).
	 */
	public function __construct( $github_user, $github_repo, $plugin_file ) {
		$this->api_url         = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->slug            = dirname( $this->plugin_basename );
		$this->version         = TSBD_VERSION;

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'after_install' ], 10, 3 );
	}

	/**
	 * Fetch release data from GitHub (cached).
	 *
	 * @return object|false Release data or false on failure.
	 */
	private function fetch_release() {
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $this->api_url, [
			'timeout' => 10,
			'headers' => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			],
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache failure for 1 hour to avoid hammering API
			set_transient( $this->cache_key, 'error', 3600 );
			return false;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $release->tag_name ) ) {
			return false;
		}

		set_transient( $this->cache_key, $release, $this->cache_ttl );
		return $release;
	}

	/**
	 * Clean version tag (remove leading "v" if present).
	 *
	 * @param string $tag Version tag from GitHub.
	 * @return string Cleaned version string.
	 */
	private function clean_version( $tag ) {
		return ltrim( $tag, 'vV' );
	}

	/**
	 * Filter: inject update data when a newer version exists on GitHub.
	 *
	 * @param object $transient WordPress update_plugins transient.
	 * @return object Modified transient.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->fetch_release();
		if ( ! $release || 'error' === $release ) {
			return $transient;
		}

		$remote_version = $this->clean_version( $release->tag_name );

		if ( version_compare( $remote_version, $this->version, '>' ) ) {
			$update              = new \stdClass();
			$update->slug        = $this->slug;
			$update->plugin      = $this->plugin_basename;
			$update->new_version = $remote_version;
			$update->url         = $release->html_url ?? '';
			$update->package     = $release->zipball_url ?? '';
			$update->icons       = [];
			$update->banners     = [];
			$update->tested      = '';
			$update->requires    = '5.0';
			$update->requires_php = '7.4';

			$transient->response[ $this->plugin_basename ] = $update;
		} else {
			// Report as up-to-date
			$item              = new \stdClass();
			$item->slug        = $this->slug;
			$item->plugin      = $this->plugin_basename;
			$item->new_version = $this->version;
			$item->url         = '';
			$item->package     = '';

			$transient->no_update[ $this->plugin_basename ] = $item;
		}

		return $transient;
	}

	/**
	 * Filter: provide plugin info for the "View Details" popup.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action API action.
	 * @param object             $args   API arguments.
	 * @return false|object Plugin info object or passthrough.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== $this->slug ) {
			return $result;
		}

		$release = $this->fetch_release();
		if ( ! $release || 'error' === $release ) {
			return $result;
		}

		$remote_version = $this->clean_version( $release->tag_name );

		$info                 = new \stdClass();
		$info->name           = 'TS Bank Donate';
		$info->slug           = $this->slug;
		$info->version        = $remote_version;
		$info->author         = '<a href="https://techshare.vn">TechShare VN</a>';
		$info->homepage       = 'https://techshare.vn';
		$info->requires       = '5.0';
		$info->requires_php   = '7.4';
		$info->tested         = '';
		$info->downloaded     = 0;
		$info->last_updated   = $release->published_at ?? '';
		$info->download_link  = $release->zipball_url ?? '';

		$info->sections = [
			'description' => 'Hiển thị hộp donate với QR chuyển khoản ngân hàng (VietQR) và MoMo. Quản lý nhiều tài khoản, tự sinh ảnh QR lưu vào Media Library.',
			'changelog'   => nl2br( esc_html( $release->body ?? 'Không có changelog.' ) ),
		];

		return $info;
	}

	/**
	 * Filter: fix directory name after GitHub ZIP extraction.
	 *
	 * GitHub ZIPs extract as "repo-branch/" but WordPress expects the original folder name.
	 *
	 * @param bool  $response   Install response.
	 * @param array $hook_extra Extra hook data.
	 * @param array $result     Installation result.
	 * @return array|WP_Error Modified result.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		$proper_destination = WP_PLUGIN_DIR . '/' . $this->slug;
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination']      = $proper_destination;
		$result['destination_name'] = $this->slug;

		// Clear update cache
		delete_transient( $this->cache_key );

		// Re-activate if was active before
		if ( is_plugin_active( $this->plugin_basename ) ) {
			activate_plugin( $this->plugin_basename );
		}

		return $result;
	}
}
