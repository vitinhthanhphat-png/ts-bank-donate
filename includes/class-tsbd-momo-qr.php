<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Momo_QR {

    /**
     * Generate QR from MoMo deeplink and save to Media Library.
     * Uses bundled TSBD_QR_Lib (qrserver.com API, no external dependencies).
     *
     * @param  array  $account  (by reference)
     * @return bool
     */
    public static function generate( array &$account ): bool {
        if ( $account['type'] !== 'momo' ) return false;

        require_once TSBD_DIR . 'lib/phpqrcode/qrlib.php';

        $phone    = sanitize_text_field( $account['phone'] ?? '' );
        $note     = sanitize_text_field( $account['default_note'] ?? 'Donate' );

        // MoMo deeplink — scanned by MoMo app on mobile
        $deeplink = 'momo://pay?action=payRequest'
                    . '&phone='  . rawurlencode( $phone )
                    . '&amount=0'
                    . '&note='   . rawurlencode( $note )
                    . '&isScanQR=true';

        $png_data = TSBD_QR_Lib::generate_png( $deeplink, 300 );

        if ( empty( $png_data ) ) {
            error_log( '[TSBD] MoMo QR generation failed' );
            return false;
        }

        return TSBD_QR_Generator::save_to_media( $account, $png_data );
    }
}
