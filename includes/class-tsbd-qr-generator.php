<?php
defined( 'ABSPATH' ) || exit;

class TSBD_QR_Generator {

    /**
     * Generate VietQR PNG and save to Media Library.
     * Updates the account's attachment_id in-place.
     *
     * @param  array  $account  Account array (passed by reference)
     * @return bool
     */
    public static function generate( array &$account ): bool {
        if ( $account['type'] !== 'bank' ) return false;

        $url = add_query_arg(
            [
                'amount'      => absint( $account['default_amount'] ?? 0 ),
                'addInfo'     => rawurlencode( $account['default_note'] ?? '' ),
                'accountName' => rawurlencode( strtoupper( $account['account_name'] ?? '' ) ),
            ],
            sprintf(
                'https://img.vietqr.io/image/%s-%s-%s.png',
                rawurlencode( $account['bank_bin'] ),
                rawurlencode( $account['account_no'] ),
                rawurlencode( $account['vietqr_template'] ?? 'compact2' )
            )
        );

        $response = wp_remote_get( $url, [ 'timeout' => 20 ] );

        if ( is_wp_error( $response ) ) {
            error_log( '[TSBD] VietQR fetch error: ' . $response->get_error_message() );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            error_log( "[TSBD] VietQR returned HTTP {$code}" );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) return false;

        return self::save_to_media( $account, $body );
    }

    /**
     * Save raw PNG bytes to WordPress Media Library.
     * Deletes old attachment first.
     */
    public static function save_to_media( array &$account, string $png_data ): bool {
        // Delete old attachment
        if ( ! empty( $account['attachment_id'] ) ) {
            wp_delete_attachment( (int) $account['attachment_id'], true );
            $account['attachment_id'] = 0;
        }

        $filename = 'tsbd-qr-' . sanitize_key( $account['id'] ) . '.png';
        $upload   = wp_upload_bits( $filename, null, $png_data );

        if ( ! empty( $upload['error'] ) ) {
            error_log( '[TSBD] wp_upload_bits error: ' . $upload['error'] );
            return false;
        }

        // Register in Media Library
        $attachment_id = wp_insert_attachment(
            [
                'post_mime_type' => 'image/png',
                'post_title'     => 'QR Donate – ' . sanitize_text_field( $account['label'] ?? $account['id'] ),
                'post_status'    => 'inherit',
                'post_content'   => '',
            ],
            $upload['file']
        );

        if ( is_wp_error( $attachment_id ) ) {
            error_log( '[TSBD] wp_insert_attachment error: ' . $attachment_id->get_error_message() );
            return false;
        }

        // Generate image metadata (thumbnails etc.)
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $meta = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        wp_update_attachment_metadata( $attachment_id, $meta );

        $account['attachment_id'] = $attachment_id;
        return true;
    }
}
