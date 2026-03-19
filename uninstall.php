<?php
// Runs only when user deletes plugin from WP Admin
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'tsbd_settings' );
delete_option( 'tsbd_accounts' );
delete_transient( 'tsbd_bank_list' );
