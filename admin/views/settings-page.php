<?php
defined( 'ABSPATH' ) || exit;
$s = TSBD_Settings::all();
?>
<div class="wrap tsbd-admin-wrap">
    <h1><?php _e( 'TS Donate — Cài đặt', 'ts-bank-donate' ); ?></h1>
    <div class="tsbd-notice" id="tsbd-notice" style="display:none"></div>

    <form id="tsbd-settings-form">
        <div class="tsbd-settings-grid">

            <!-- GENERAL -->
            <div class="tsbd-settings-section">
                <h2><?php _e( 'Chung', 'ts-bank-donate' ); ?></h2>

                <div class="tsbd-field">
                    <label for="ts_title"><?php _e( 'Tiêu đề box donate', 'ts-bank-donate' ); ?></label>
                    <input type="text" id="ts_title" name="title" value="<?php echo esc_attr( $s['title'] ); ?>">
                </div>
                <div class="tsbd-field">
                    <label for="ts_description"><?php _e( 'Mô tả / Lời kêu gọi', 'ts-bank-donate' ); ?></label>
                    <textarea id="ts_description" name="description" rows="2"><?php echo esc_textarea( $s['description'] ); ?></textarea>
                </div>
                <div class="tsbd-field tsbd-field-inline">
                    <label>
                        <input type="checkbox" name="show_amount_suggestions" value="1" <?php checked( $s['show_amount_suggestions'] ); ?>>
                        <?php _e( 'Hiển thị các mức tiền gợi ý', 'ts-bank-donate' ); ?>
                    </label>
                </div>
                <div class="tsbd-field">
                    <label for="ts_amount_suggestions"><?php _e( 'Mức tiền gợi ý (cách nhau dấu phẩy)', 'ts-bank-donate' ); ?></label>
                    <input type="text" id="ts_amount_suggestions" name="amount_suggestions" value="<?php echo esc_attr( implode( ',', (array) $s['amount_suggestions'] ) ); ?>" placeholder="20000,50000,100000,200000">
                </div>
                <div class="tsbd-field tsbd-field-inline">
                    <label>
                        <input type="checkbox" name="allow_custom_amount" value="1" <?php checked( $s['allow_custom_amount'] ); ?>>
                        <?php _e( 'Cho phép nhập số tiền tự do', 'ts-bank-donate' ); ?>
                    </label>
                </div>
                <div class="tsbd-field tsbd-field-inline">
                    <label>
                        <input type="checkbox" name="allow_note_change" value="1" <?php checked( $s['allow_note_change'] ); ?>>
                        <?php _e( 'Cho phép thay đổi nội dung CK', 'ts-bank-donate' ); ?>
                    </label>
                </div>
                <div class="tsbd-field">
                    <label for="ts_currency"><?php _e( 'Ký hiệu tiền tệ', 'ts-bank-donate' ); ?></label>
                    <input type="text" id="ts_currency" name="currency" value="<?php echo esc_attr( $s['currency'] ); ?>" style="width:80px">
                </div>
                <div class="tsbd-field tsbd-field-inline">
                    <label>
                        <input type="checkbox" name="show_footer_credit" value="1" <?php checked( $s['show_footer_credit'] ); ?>>
                        <?php _e( 'Hiển thị "Powered by TS Donate"', 'ts-bank-donate' ); ?>
                    </label>
                </div>
            </div>

            <!-- APPEARANCE -->
            <div class="tsbd-settings-section">
                <h2><?php _e( 'Giao diện', 'ts-bank-donate' ); ?></h2>

                <div class="tsbd-field">
                    <label for="ts_default_template"><?php _e( 'Template mặc định', 'ts-bank-donate' ); ?></label>
                    <select id="ts_default_template" name="default_template">
                        <?php foreach ( [ 'modern' => 'Modern Card', 'minimal' => 'Minimal', 'glass' => 'Glassmorphism', 'classic' => 'Classic' ] as $val => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $s['default_template'], $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tsbd-field tsbd-color-row">
                    <div>
                        <label for="ts_primary_color"><?php _e( 'Màu chính', 'ts-bank-donate' ); ?></label>
                        <input type="color" id="ts_primary_color" name="primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>">
                    </div>
                    <div>
                        <label for="ts_bg_color"><?php _e( 'Màu nền', 'ts-bank-donate' ); ?></label>
                        <input type="color" id="ts_bg_color" name="bg_color" value="<?php echo esc_attr( $s['bg_color'] ); ?>">
                    </div>
                    <div>
                        <label for="ts_text_color"><?php _e( 'Màu chữ', 'ts-bank-donate' ); ?></label>
                        <input type="color" id="ts_text_color" name="text_color" value="<?php echo esc_attr( $s['text_color'] ); ?>">
                    </div>
                </div>
                <div class="tsbd-field">
                    <label for="ts_border_radius"><?php _e( 'Bo góc (px)', 'ts-bank-donate' ); ?></label>
                    <input type="range" id="ts_border_radius" name="border_radius" min="0" max="32" value="<?php echo absint( $s['border_radius'] ); ?>" oninput="document.getElementById('tsbd_radius_val').textContent=this.value">
                    <span id="tsbd_radius_val"><?php echo absint( $s['border_radius'] ); ?></span>px
                </div>
                <div class="tsbd-field">
                    <label for="ts_max_width"><?php _e( 'Max-width box', 'ts-bank-donate' ); ?></label>
                    <input type="text" id="ts_max_width" name="max_width" value="<?php echo esc_attr( $s['max_width'] ); ?>" style="width:120px" placeholder="480px">
                </div>
                <div class="tsbd-field tsbd-field-inline">
                    <label>
                        <input type="checkbox" name="load_google_fonts" value="1" <?php checked( $s['load_google_fonts'] ); ?>>
                        <?php _e( 'Tải Google Fonts (Inter)', 'ts-bank-donate' ); ?>
                    </label>
                </div>
            </div>

            <!-- ADVANCED -->
            <div class="tsbd-settings-section tsbd-settings-full">
                <h2><?php _e( 'Nâng cao', 'ts-bank-donate' ); ?></h2>
                <div class="tsbd-field">
                    <label for="ts_custom_css"><?php _e( 'Custom CSS', 'ts-bank-donate' ); ?></label>
                    <textarea id="ts_custom_css" name="custom_css" rows="6" style="font-family:monospace"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
                </div>
                <div class="tsbd-field">
                    <button type="button" class="button" id="tsbd-clear-cache"><?php _e( 'Xoá cache bank list', 'ts-bank-donate' ); ?></button>
                    <small><?php _e( 'Danh sách ngân hàng được cache 6 giờ để tăng tốc độ.', 'ts-bank-donate' ); ?></small>
                </div>
            </div>

        </div>

        <div class="tsbd-form-actions">
            <button type="submit" class="button button-primary"><?php _e( 'Lưu cài đặt', 'ts-bank-donate' ); ?></button>
            <span class="spinner tsbd-spinner"></span>
        </div>
    </form>
</div>
