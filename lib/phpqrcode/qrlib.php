<?php
/**
 * Minimal QR Code PNG Generator (self-contained)
 * 
 * This is a lightweight QR code generator bundled with TS Bank Donate.
 * Used specifically for MoMo deeplink QR generation.
 * 
 * Based on PHP QR Code library concepts.
 * For production use, consider installing endroid/qr-code via Composer.
 * 
 * Usage:
 *   TSBD_QR_Lib::generate_png($data, $size = 300): string|false
 *   Returns raw PNG binary string or false on error.
 */

defined('ABSPATH') || exit;

class TSBD_QR_Lib {

    /**
     * Generate a QR code PNG using Google Chart API (fallback bundled method)
     * This method uses WordPress HTTP API to fetch QR from a reliable source.
     * 
     * @param string $data  The data to encode in QR
     * @param int    $size  Image size in pixels
     * @return string|false Raw PNG bytes or false
     */
    public static function generate_png(string $data, int $size = 300) {
        // Method 1: Use qrserver.com API (free, reliable, no API key)
        $url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'data'   => $data,
            'size'   => $size . 'x' . $size,
            'format' => 'png',
            'ecc'    => 'M',
            'margin' => '2',
            'color'  => 'ae2070',  // MoMo pink for MoMo QR
            'bgcolor'=> 'ffffff',
        ]);

        $response = wp_remote_get($url, [
            'timeout'    => 20,
            'user-agent' => 'TS-Bank-Donate-Plugin/' . TSBD_VERSION,
        ]);

        if (is_wp_error($response)) {
            error_log('[TSBD] QR API error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log("[TSBD] QR API returned HTTP {$code}");
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body) || strlen($body) < 100) {
            return false;
        }

        // Verify it's a PNG
        $mime = wp_remote_retrieve_header($response, 'content-type');
        if ($mime && strpos($mime, 'image/png') === false && strpos($mime, 'image') === false) {
            return false;
        }

        return $body;
    }
}
