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
        $s = TSBD_Settings::all();
        $d = TSBD_Settings::defaults();

        $vars = [];
        if ( $s['primary_color'] !== $d['primary_color'] )
            $vars[] = '--tsbd-primary:' . esc_attr( $s['primary_color'] );
        if ( $s['bg_color'] !== $d['bg_color'] )
            $vars[] = '--tsbd-bg:' . esc_attr( $s['bg_color'] );
        if ( $s['text_color'] !== $d['text_color'] )
            $vars[] = '--tsbd-text:' . esc_attr( $s['text_color'] );
        if ( (int) $s['border_radius'] !== (int) $d['border_radius'] )
            $vars[] = '--tsbd-radius:' . (int) $s['border_radius'] . 'px';
        if ( $s['gradient_start'] !== $d['gradient_start'] || $s['gradient_end'] !== $d['gradient_end'] )
            $vars[] = '--tsbd-gradient:linear-gradient(135deg,' . esc_attr( $s['gradient_start'] ) . ' 0%,' . esc_attr( $s['gradient_end'] ) . ' 100%)';

        $inline = '';
        if ( $vars ) {
            $inline .= ':root{' . implode( ';', $vars ) . '}';
        }
        if ( $s['max_width'] !== $d['max_width'] ) {
            $inline .= '.tsbd-box{max-width:' . esc_attr( $s['max_width'] ) . '}';
        }
        if ( ! empty( $s['custom_css'] ) ) {
            $inline .= "\n" . wp_strip_all_tags( $s['custom_css'] );
        }
        if ( $inline ) {
            wp_add_inline_style( 'tsbd-base', $inline );
        }
    }
}
