<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Admin {

    /**
     * Register only the wp_ajax_* hooks.
     * Called early (before admin_menu) so AJAX requests on admin-ajax.php work.
     */
    public function register_ajax_hooks(): void {
        add_action( 'wp_ajax_tsbd_save_account',  [ $this, 'ajax_save_account' ] );
        add_action( 'wp_ajax_tsbd_delete_account', [ $this, 'ajax_delete_account' ] );
        add_action( 'wp_ajax_tsbd_regenerate_qr', [ $this, 'ajax_regenerate_qr' ] );
        add_action( 'wp_ajax_tsbd_get_banks',      [ $this, 'ajax_get_banks' ] );
        add_action( 'wp_ajax_tsbd_save_settings',  [ $this, 'ajax_save_settings' ] );
    }

    public function register_menu(): void {
        $hook = add_menu_page(
            __( 'TS Donate', 'ts-bank-donate' ),
            __( 'TS Donate', 'ts-bank-donate' ),
            'manage_options',
            'ts-bank-donate',
            [ $this, 'page_accounts' ],
            'dashicons-money-alt',
            81
        );
        add_submenu_page( 'ts-bank-donate', __( 'Tài khoản', 'ts-bank-donate' ), __( 'Tài khoản', 'ts-bank-donate' ), 'manage_options', 'ts-bank-donate', [ $this, 'page_accounts' ] );
        add_submenu_page( 'ts-bank-donate', __( 'Thêm tài khoản', 'ts-bank-donate' ), __( 'Thêm mới', 'ts-bank-donate' ), 'manage_options', 'tsbd-add', [ $this, 'page_edit' ] );
        add_submenu_page( 'ts-bank-donate', __( 'Cài đặt', 'ts-bank-donate' ), __( 'Cài đặt', 'ts-bank-donate' ), 'manage_options', 'tsbd-settings', [ $this, 'page_settings' ] );

        add_action( "load-{$hook}", [ $this, 'handle_actions' ] );
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( strpos( $hook, 'ts-bank-donate' ) === false && strpos( $hook, 'tsbd-' ) === false ) return;
        wp_enqueue_style( 'tsbd-admin', TSBD_URL . 'admin/css/tsbd-admin.css', [], TSBD_VERSION );
        wp_enqueue_script( 'tsbd-admin', TSBD_URL . 'admin/js/tsbd-admin.js', [ 'jquery' ], TSBD_VERSION, true );
        // On add/edit page: also load frontend CSS so the live template preview works
        $current_page = $_GET['page'] ?? '';
        if ( $current_page === 'tsbd-add' ) {
            wp_enqueue_style( 'tsbd-base',    TSBD_URL . 'public/css/tsbd-base.css',    [], TSBD_VERSION );
            wp_enqueue_style( 'tsbd-modern',  TSBD_URL . 'public/css/tsbd-modern.css',  [ 'tsbd-base' ], TSBD_VERSION );
            wp_enqueue_style( 'tsbd-minimal', TSBD_URL . 'public/css/tsbd-minimal.css', [ 'tsbd-base' ], TSBD_VERSION );
            wp_enqueue_style( 'tsbd-glass',   TSBD_URL . 'public/css/tsbd-glass.css',   [ 'tsbd-base' ], TSBD_VERSION );
            wp_enqueue_style( 'tsbd-classic', TSBD_URL . 'public/css/tsbd-classic.css', [ 'tsbd-base' ], TSBD_VERSION );
        }
        wp_localize_script( 'tsbd-admin', 'TSBD', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tsbd_admin' ),
            'strings'  => [
                'confirm_delete' => __( 'Bạn có chắc muốn xoá tài khoản này?', 'ts-bank-donate' ),
                'generating'     => __( 'Đang tạo QR…', 'ts-bank-donate' ),
                'success'        => __( 'Đã lưu thành công!', 'ts-bank-donate' ),
                'error'          => __( 'Có lỗi xảy ra. Vui lòng thử lại.', 'ts-bank-donate' ),
            ],
        ] );
        wp_enqueue_media();
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public function page_accounts(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        require_once TSBD_DIR . 'admin/views/accounts-list.php';
    }

    public function page_edit(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        require_once TSBD_DIR . 'admin/views/account-edit.php';
    }

    public function page_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        require_once TSBD_DIR . 'admin/views/settings-page.php';
    }

    public function handle_actions(): void {
        // Non-AJAX form actions handled here if needed
    }

    // ─── AJAX Handlers ────────────────────────────────────────────────────────

    public function ajax_save_account(): void {
        check_ajax_referer( 'tsbd_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );

        $data = $_POST['account'] ?? [];
        if ( ! is_array( $data ) ) wp_send_json_error( 'Invalid data' );

        $id = sanitize_key( $data['id'] ?? '' );

        if ( $id ) {
            // Update existing — apply new data first, then fetch fresh copy
            if ( ! TSBD_Account::find( $id ) ) wp_send_json_error( 'Account not found' );
            TSBD_Account::update( $id, $data );
            $account = TSBD_Account::find( $id );  // fresh copy after update
        } else {
            // Create new
            $account = TSBD_Account::create( $data );
            $id      = $account['id'];
        }

        // Generate QR — generators take &$account and update attachment_id in-place
        // Use output buffer to suppress any PHP notices/warnings that would corrupt JSON
        $generated = false;
        ob_start();
        try {
            if ( $account['type'] === 'bank' ) {
                $generated = TSBD_QR_Generator::generate( $account );
            } elseif ( $account['type'] === 'momo' && ! empty( $account['momo_qr_custom'] ) ) {
                // MoMo: use uploaded QR as the attachment_id (no auto-generation)
                $account['attachment_id'] = absint( $account['momo_qr_custom'] );
                $generated = true;
            }
        } catch ( \Throwable $e ) {
            error_log( '[TSBD] QR generation exception: ' . $e->getMessage() );
        }
        ob_end_clean();

        // Persist the attachment_id back to wp_options
        if ( $generated && ! empty( $account['attachment_id'] ) ) {
            TSBD_Account::update( $id, [ 'attachment_id' => $account['attachment_id'] ] );
        }

        $qr_url = '';
        if ( ! empty( $account['attachment_id'] ) ) {
            $qr_url = wp_get_attachment_url( (int) $account['attachment_id'] );
        }

        wp_send_json_success( [
            'id'        => $id,
            'qr_url'    => $qr_url,
            'generated' => $generated,
        ] );
    }

    public function ajax_delete_account(): void {
        check_ajax_referer( 'tsbd_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );

        $id = sanitize_key( $_POST['id'] ?? '' );
        if ( ! $id ) wp_send_json_error( 'Missing ID' );

        $result = TSBD_Account::delete( $id );
        $result ? wp_send_json_success() : wp_send_json_error( 'Delete failed' );
    }

    public function ajax_regenerate_qr(): void {
        check_ajax_referer( 'tsbd_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );

        $id = sanitize_key( $_POST['id'] ?? '' );
        $account = TSBD_Account::find( $id );
        if ( ! $account ) wp_send_json_error( 'Account not found' );

        $generated = false;
        if ( $account['type'] === 'bank' ) {
            $generated = TSBD_QR_Generator::generate( $account );
        } elseif ( $account['type'] === 'momo' ) {
            // MoMo requires uploaded QR — cannot regenerate
            wp_send_json_error( 'MoMo QR cần upload từ app MoMo. Không thể tự tạo.' );
        }

        if ( $generated ) {
            TSBD_Account::set_attachment( $id, $account['attachment_id'] );
            wp_send_json_success( [ 'qr_url' => wp_get_attachment_url( $account['attachment_id'] ) ] );
        }

        wp_send_json_error( 'QR generation failed' );
    }

    public function ajax_get_banks(): void {
        check_ajax_referer( 'tsbd_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );

        $transient_key = 'tsbd_bank_list';
        $cached        = get_transient( $transient_key );
        if ( $cached ) {
            wp_send_json_success( $cached );
            return;
        }

        $response = wp_remote_get( 'https://api.vietqr.io/v2/banks', [ 'timeout' => 15 ] );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['data'] ) ) {
            wp_send_json_error( 'Empty bank list' );
        }

        $banks = array_map( fn( $b ) => [
            'bin'      => $b['bin'],
            'name'     => $b['name'],
            'short'    => $b['shortName'],
            'logo'     => $b['logo'],
        ], $body['data'] );

        set_transient( $transient_key, $banks, 6 * HOUR_IN_SECONDS );
        wp_send_json_success( $banks );
    }

    public function ajax_save_settings(): void {
        check_ajax_referer( 'tsbd_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );

        $data = $_POST['settings'] ?? [];
        if ( ! is_array( $data ) ) wp_send_json_error( 'Invalid data' );

        TSBD_Settings::save( $data );
        wp_send_json_success();
    }
}
