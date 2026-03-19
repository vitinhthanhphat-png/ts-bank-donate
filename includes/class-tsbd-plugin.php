<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Plugin {

    private static ?TSBD_Plugin $instance = null;

    /** @var TSBD_Admin|null */
    private ?TSBD_Admin $admin = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load admin class early so wp_ajax_ hooks register.
        // admin-ajax.php does NOT fire admin_menu, so we cannot lazy-load here.
        if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            require_once TSBD_DIR . 'admin/class-tsbd-admin.php';
            $this->admin = new TSBD_Admin();
            $this->admin->register_ajax_hooks();           // register wp_ajax_* NOW
            add_action( 'admin_menu',              [ $this->admin, 'register_menu' ] );
            add_action( 'admin_enqueue_scripts',   [ $this->admin, 'enqueue_admin_assets' ] );
        }

        add_action( 'init',    [ $this, 'init' ] );
        register_activation_hook( TSBD_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( TSBD_FILE, [ $this, 'deactivate' ] );
    }

    public function init(): void {
        // Frontend
        $frontend = new TSBD_Frontend();
        $frontend->register();

        // Shortcode
        $shortcode = new TSBD_Shortcode();
        $shortcode->register();
    }

    public function activate(): void {
        if ( ! get_option( TSBD_OPTION_SETTINGS ) ) {
            update_option( TSBD_OPTION_SETTINGS, TSBD_Settings::defaults() );
        }
        if ( ! get_option( TSBD_OPTION_ACCOUNTS ) ) {
            update_option( TSBD_OPTION_ACCOUNTS, [] );
        }
    }

    public function deactivate(): void {
        // Keep data on deactivate, remove only on uninstall
    }
}
