<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Momo_QR {

    /**
     * MoMo BIN code on NAPAS / VietQR system.
     * Source: https://api.vietqr.io/v2/banks  (code: "momo", bin: "971025")
     */
    const MOMO_BIN = '971025';

    /**
     * Generate MoMo QR using EMVCo / VietQR standard.
     *
     * NOTE: MoMo app QR includes field 62 with a private wallet reference
     * (e.g., MOMOW2W1626388) that only MoMo can generate. Without this,
     * MoMo scanner may not recognize the QR, but banking apps WILL work
     * because the EMVCo payload is valid NAPAS standard.
     *
     * For 100% MoMo compatibility, users should upload their QR from the
     * MoMo app (Nhận tiền → Tải QR) using the upload field.
     *
     * @param  array  $account  (by reference)
     * @return bool
     */
    public static function generate( array &$account ): bool {
        if ( $account['type'] !== 'momo' ) return false;

        require_once TSBD_DIR . 'lib/phpqrcode/qrlib.php';

        $phone = preg_replace( '/[^0-9]/', '', $account['phone'] ?? '' );
        if ( empty( $phone ) ) return false;

        $payload = self::build_emvco_payload( $phone );

        $png_data = TSBD_QR_Lib::generate_png( $payload, 300 );

        if ( empty( $png_data ) ) {
            error_log( '[TSBD] MoMo QR generation failed for phone: ' . $phone );
            return false;
        }

        return TSBD_QR_Generator::save_to_media( $account, $png_data );
    }

    /**
     * Build EMVCo QR payload (VietQR / NAPAS standard).
     *
     * Structure:
     *   00 - Payload Format Indicator (01)
     *   01 - Point of Initiation Method (11 = Static)
     *   38 - Merchant Account Information (NAPAS/VietQR)
     *        00 - AID (A000000727)
     *        01 - Consumer Account Info
     *             00 - BIN (971025)
     *             01 - Account No (phone)
     *        02 - Service Code (QRIBFTTA)
     *   53 - Transaction Currency (704 = VND)
     *   58 - Country Code (VN)
     *   63 - CRC (auto-calculated)
     *
     * Field 62 (Additional Data / MOMOW2W reference) is intentionally OMITTED
     * because it contains a MoMo-internal wallet ID that cannot be generated
     * externally. The QR remains valid for NAPAS bank transfers.
     */
    private static function build_emvco_payload( string $phone ): string {
        // Field 38 sub-fields (Merchant Account Info for NAPAS/VietQR)
        $f38_00 = self::tlv( '00', 'A000000727' );                    // AID
        $f38_01_00 = self::tlv( '00', self::MOMO_BIN );               // BIN
        $f38_01_01 = self::tlv( '01', $phone );                       // Account (phone)
        $f38_01 = self::tlv( '01', $f38_01_00 . $f38_01_01 );        // Consumer Account Info
        $f38_02 = self::tlv( '02', 'QRIBFTTA' );                     // Service Code

        // Build payload (without field 62 — see docblock)
        $payload  = self::tlv( '00', '01' );                          // Payload Format Indicator
        $payload .= self::tlv( '01', '11' );                          // Static QR
        $payload .= self::tlv( '38', $f38_00 . $f38_01 . $f38_02 );  // Merchant Account Info
        $payload .= self::tlv( '53', '704' );                         // Currency (VND)
        $payload .= self::tlv( '58', 'VN' );                          // Country

        // Append CRC placeholder and calculate
        $payload .= '6304';
        $crc = self::crc16( $payload );
        $payload .= strtoupper( str_pad( dechex( $crc ), 4, '0', STR_PAD_LEFT ) );

        return $payload;
    }

    /**
     * Create a TLV (Tag-Length-Value) data object.
     */
    private static function tlv( string $id, string $value ): string {
        return $id . str_pad( strlen( $value ), 2, '0', STR_PAD_LEFT ) . $value;
    }

    /**
     * CRC-16/CCITT-FALSE checksum (EMVCo standard).
     * Polynomial: 0x1021, Init: 0xFFFF
     */
    private static function crc16( string $data ): int {
        $crc = 0xFFFF;
        for ( $i = 0, $len = strlen( $data ); $i < $len; $i++ ) {
            $crc ^= ( ord( $data[ $i ] ) << 8 );
            for ( $j = 0; $j < 8; $j++ ) {
                if ( $crc & 0x8000 ) {
                    $crc = ( ( $crc << 1 ) ^ 0x1021 ) & 0xFFFF;
                } else {
                    $crc = ( $crc << 1 ) & 0xFFFF;
                }
            }
        }
        return $crc & 0xFFFF;
    }
}
