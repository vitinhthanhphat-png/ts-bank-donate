<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Frontend {

    private bool $shortcode_used = false;

    public function register(): void {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'the_content', [ $this, 'check_content' ] );
    }

    /**
     * Check if shortcode is present on early content filter — we use a flag
     * so we only load CSS/JS when needed.
     */
    public function check_content( string $content ): string {
        if ( has_shortcode( $content, 'ts_donate' ) ) {
            $this->shortcode_used = true;
        }
        return $content;
    }

    public function enqueue(): void {
        // Always register, conditionally enqueue
        $ver = TSBD_VERSION;

        wp_register_style( 'tsbd-base',    TSBD_URL . 'public/css/tsbd-base.css',    [], $ver );
        wp_register_style( 'tsbd-modern',  TSBD_URL . 'public/css/tsbd-modern.css',  [ 'tsbd-base' ], $ver );
        wp_register_style( 'tsbd-minimal', TSBD_URL . 'public/css/tsbd-minimal.css', [ 'tsbd-base' ], $ver );
        wp_register_style( 'tsbd-glass',   TSBD_URL . 'public/css/tsbd-glass.css',   [ 'tsbd-base' ], $ver );
        wp_register_style( 'tsbd-classic', TSBD_URL . 'public/css/tsbd-classic.css', [ 'tsbd-base' ], $ver );
        wp_register_script( 'tsbd-public', TSBD_URL . 'public/js/tsbd-public.js', [], $ver, true );

        // Google Fonts (optional)
        if ( TSBD_Settings::get( 'load_google_fonts' ) ) {
            wp_register_style( 'tsbd-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], null );
        }

        if ( $this->shortcode_used || is_singular() ) {
            // will be enqueued per shortcode call when needed
        }
    }

    /**
     * Enqueue styles for a specific template. Called by shortcode renderer.
     */
    public static function enqueue_template( string $template ): void {
        $handles = [
            'modern'  => 'tsbd-modern',
            'minimal' => 'tsbd-minimal',
            'glass'   => 'tsbd-glass',
            'classic' => 'tsbd-classic',
        ];

        wp_enqueue_style( 'tsbd-base' );
        if ( isset( $handles[ $template ] ) ) {
            wp_enqueue_style( $handles[ $template ] );
        }
        wp_enqueue_script( 'tsbd-public' );

        if ( TSBD_Settings::get( 'load_google_fonts' ) ) {
            wp_enqueue_style( 'tsbd-fonts' );
        }

        // Inline CSS vars from settings
        $settings = TSBD_Settings::all();
        $inline   = sprintf(
            ':root { --tsbd-primary: %s; --tsbd-bg: %s; --tsbd-text: %s; --tsbd-radius: %dpx; }',
            esc_attr( $settings['primary_color'] ),
            esc_attr( $settings['bg_color'] ),
            esc_attr( $settings['text_color'] ),
            (int) $settings['border_radius']
        );
        if ( ! empty( $settings['custom_css'] ) ) {
            $inline .= "\n" . wp_strip_all_tags( $settings['custom_css'] );
        }
        wp_add_inline_style( 'tsbd-base', $inline );
    }
}
