<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Settings {

    private static array $defaults = [
        'title'                   => 'Ủng hộ chúng tôi',
        'description'             => 'Quét mã QR hoặc chuyển khoản trực tiếp để ủng hộ chúng tôi.',
        'show_amount_suggestions' => true,
        'amount_suggestions'      => [ 20000, 50000, 100000, 200000 ],
        'allow_custom_amount'     => true,
        'allow_note_change'       => true,
        'currency'                => 'đ',
        'default_template'        => 'modern',
        'primary_color'           => '#3B82F6',
        'gradient_start'          => '#3B82F6',
        'gradient_end'            => '#8B5CF6',
        'bg_color'                => '#F8FAFC',
        'text_color'              => '#0F172A',
        'border_radius'           => 16,
        'max_width'               => '460px',
        'custom_css'              => '',
        'load_google_fonts'       => false,
        'show_footer_credit'      => true,
    ];

    public static function get( string $key, $fallback = null ) {
        $settings = get_option( TSBD_OPTION_SETTINGS, [] );
        if ( isset( $settings[ $key ] ) ) {
            return $settings[ $key ];
        }
        return $fallback ?? ( self::$defaults[ $key ] ?? null );
    }

    public static function all(): array {
        $saved = get_option( TSBD_OPTION_SETTINGS, [] );
        return wp_parse_args( $saved, self::$defaults );
    }

    public static function save( array $data ): bool {
        $current  = self::all();
        $sanitized = self::sanitize( $data );
        return update_option( TSBD_OPTION_SETTINGS, wp_parse_args( $sanitized, $current ) );
    }

    public static function defaults(): array {
        return self::$defaults;
    }

    private static function sanitize( array $data ): array {
        $out = [];

        if ( isset( $data['title'] ) )
            $out['title'] = sanitize_text_field( $data['title'] );

        if ( isset( $data['description'] ) )
            $out['description'] = wp_kses_post( $data['description'] );

        if ( isset( $data['show_amount_suggestions'] ) )
            $out['show_amount_suggestions'] = (bool) $data['show_amount_suggestions'];

        if ( isset( $data['amount_suggestions'] ) ) {
            $raw = is_array( $data['amount_suggestions'] )
                ? $data['amount_suggestions']
                : explode( ',', $data['amount_suggestions'] );
            $out['amount_suggestions'] = array_map( 'absint', $raw );
        }

        if ( isset( $data['allow_custom_amount'] ) )
            $out['allow_custom_amount'] = (bool) $data['allow_custom_amount'];

        if ( isset( $data['allow_note_change'] ) )
            $out['allow_note_change'] = (bool) $data['allow_note_change'];

        if ( isset( $data['currency'] ) )
            $out['currency'] = sanitize_text_field( $data['currency'] );

        if ( isset( $data['default_template'] ) )
            $out['default_template'] = in_array( $data['default_template'], [ 'modern', 'minimal', 'glass', 'classic' ], true )
                ? $data['default_template'] : 'modern';

        foreach ( [ 'primary_color', 'gradient_start', 'gradient_end', 'bg_color', 'text_color' ] as $key ) {
            if ( isset( $data[ $key ] ) )
                $out[ $key ] = sanitize_hex_color( $data[ $key ] ) ?: self::$defaults[ $key ];
        }

        if ( isset( $data['border_radius'] ) )
            $out['border_radius'] = absint( $data['border_radius'] );

        if ( isset( $data['max_width'] ) )
            $out['max_width'] = sanitize_text_field( $data['max_width'] );

        if ( isset( $data['custom_css'] ) )
            $out['custom_css'] = wp_strip_all_tags( $data['custom_css'] );

        if ( isset( $data['load_google_fonts'] ) )
            $out['load_google_fonts'] = (bool) $data['load_google_fonts'];

        if ( isset( $data['show_footer_credit'] ) )
            $out['show_footer_credit'] = (bool) $data['show_footer_credit'];

        return $out;
    }
}
