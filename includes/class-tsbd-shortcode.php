<?php
defined( 'ABSPATH' ) || exit;

class TSBD_Shortcode {

    public function register(): void {
        add_shortcode( 'ts_donate', [ $this, 'render' ] );
    }

    public function render( $atts ): string {
        $settings = TSBD_Settings::all();

        $atts = shortcode_atts( [
            'accounts'          => '',         // comma-separated IDs, empty = all active
            'show_note_field'   => '',
            'show_amount_field' => '',
            'title'             => '',
            'width'             => '',
        ], $atts, 'ts_donate' );

        // Resolve accounts
        if ( ! empty( $atts['accounts'] ) ) {
            $ids      = array_map( 'sanitize_key', explode( ',', $atts['accounts'] ) );
            $accounts = TSBD_Account::get_by_ids( $ids );
        } else {
            $accounts = TSBD_Account::active();
        }

        if ( empty( $accounts ) ) {
            return '<p class="tsbd-empty">' . esc_html__( 'Chưa có tài khoản nào được cấu hình.', 'ts-bank-donate' ) . '</p>';
        }

        // Sort by order
        usort( $accounts, fn( $a, $b ) => ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 ) );

        // Resolve options
        $title    = ! empty( $atts['title'] )  ? esc_html( $atts['title'] ) : esc_html( $settings['title'] );
        $desc     = esc_html( $settings['description'] );
        $width    = ! empty( $atts['width'] )  ? esc_attr( $atts['width'] ) : esc_attr( $settings['max_width'] );

        $show_note   = $atts['show_note_field']   !== ''
            ? filter_var( $atts['show_note_field'], FILTER_VALIDATE_BOOLEAN )
            : (bool) $settings['allow_note_change'];

        $show_amount = $atts['show_amount_field'] !== ''
            ? filter_var( $atts['show_amount_field'], FILTER_VALIDATE_BOOLEAN )
            : (bool) $settings['allow_custom_amount'];


        // Collect all templates needed to enqueue CSS
        $templates_needed = [];
        foreach ( $accounts as $account ) {
            $tpl = ! empty( $account['box_template'] ) ? $account['box_template'] : $settings['default_template'];
            $templates_needed[ $tpl ] = true;
        }
        foreach ( array_keys( $templates_needed ) as $tpl ) {
            TSBD_Frontend::enqueue_template( $tpl );
        }

        // Build output
        ob_start();
        $box_id = 'tsbd-' . wp_unique_id();
        // First account's template determines initial .tsbd-box styling (header, tabs, footer)
        $first_tpl = ! empty( $accounts[0]['box_template'] ) ? $accounts[0]['box_template'] : $settings['default_template'];
        ?>
        <div id="<?php echo esc_attr( $box_id ); ?>"
             class="tsbd-box tsbd-template-<?php echo esc_attr( $first_tpl ); ?>"
             style="max-width:<?php echo esc_attr( $width ); ?>"
             data-current-tpl="<?php echo esc_attr( $first_tpl ); ?>"
        >

            <div class="tsbd-header">
                <h3 class="tsbd-title"><?php echo $title; ?></h3>
                <?php if ( $desc ) : ?>
                    <p class="tsbd-desc"><?php echo $desc; ?></p>
                <?php endif; ?>
            </div>

            <?php if ( count( $accounts ) > 1 ) : ?>
            <div class="tsbd-tabs" role="tablist">
                <?php foreach ( $accounts as $i => $account ) :
                    $tpl    = ! empty( $account['box_template'] ) ? $account['box_template'] : $settings['default_template'];
                    $tab_id = $box_id . '-tab-' . esc_attr( $account['id'] );
                    ?>
                    <button class="tsbd-tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                            role="tab"
                            data-target="<?php echo esc_attr( $box_id . '-panel-' . $account['id'] ); ?>"
                            data-template="<?php echo esc_attr( $tpl ); ?>"
                            aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                            id="<?php echo esc_attr( $tab_id ); ?>">
                        <?php echo esc_html( $account['label'] ?? ( $account['bank_short'] ?? 'Tài khoản ' . ( $i + 1 ) ) ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php foreach ( $accounts as $i => $account ) :
                $tpl        = ! empty( $account['box_template'] ) ? $account['box_template'] : $settings['default_template'];
                $note       = $account['default_note'] ?? '';
                $amount     = $account['default_amount'] ?? 0;
                $panel_id   = $box_id . '-panel-' . $account['id'];
                $template_f = TSBD_DIR . "public/templates/template-{$tpl}.php";
                if ( ! file_exists( $template_f ) ) {
                    $template_f = TSBD_DIR . 'public/templates/template-modern.php';
                }
                ?>
                <div id="<?php echo esc_attr( $panel_id ); ?>"
                     class="tsbd-panel tsbd-template-<?php echo esc_attr( $tpl ); ?><?php echo $i === 0 ? ' is-active' : ''; ?>"
                     role="tabpanel">
                    <?php include $template_f; ?>
                </div>
            <?php endforeach; ?>

            <?php if ( $settings['show_footer_credit'] ) : ?>
                <div class="tsbd-footer">
                    <small>Powered by <a href="https://techshare.vn" target="_blank" rel="noopener">TS Donate</a></small>
                </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }
}
