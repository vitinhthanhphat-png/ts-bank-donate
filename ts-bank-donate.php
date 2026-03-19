<?php
/**
 * Plugin Name: TS Bank Donate
 * Plugin URI:  https://techshare.vn
 * Description: Hiển thị hộp donate với QR chuyển khoản ngân hàng (VietQR) và MoMo. Quản lý nhiều tài khoản, tự sinh ảnh QR lưu vào Media Library.
 * Version:     1.3.1
 * Author:      TechShare VN
 * Author URI:  https://techshare.vn
 * License:     GPL-2.0-or-later
 * Text Domain: ts-bank-donate
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

// Constants
define( 'TSBD_VERSION',   '1.3.1' );
define( 'TSBD_FILE',      __FILE__ );
define( 'TSBD_DIR',       plugin_dir_path( __FILE__ ) );
define( 'TSBD_URL',       plugin_dir_url( __FILE__ ) );
define( 'TSBD_OPTION_ACCOUNTS', 'tsbd_accounts' );
define( 'TSBD_OPTION_SETTINGS', 'tsbd_settings' );

// Autoload includes
foreach ( [
    'includes/class-tsbd-settings.php',
    'includes/class-tsbd-account.php',
    'includes/class-tsbd-qr-generator.php',
    'includes/class-tsbd-momo-qr.php',
    'includes/class-tsbd-shortcode.php',
    'includes/class-tsbd-frontend.php',
    'includes/class-tsbd-plugin.php',
    'includes/class-tsbd-github-updater.php',
] as $file ) {
    require_once TSBD_DIR . $file;
}

// Boot
TSBD_Plugin::instance();

// OTA Update via GitHub
new TSBD_GitHub_Updater( 'vitinhthanhphat-png', 'ts-bank-donate', TSBD_FILE );

