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
                <div class="tsbd-section-header">
                    <h2>⚙️ <?php _e( 'Cài đặt chung', 'ts-bank-donate' ); ?></h2>
                </div>

                <div class="tsbd-field">
                    <label for="ts_title"><?php _e( 'Tiêu đề box donate', 'ts-bank-donate' ); ?></label>
                    <input type="text" id="ts_title" name="title" value="<?php echo esc_attr( $s['title'] ); ?>" placeholder="Ủng hộ chúng tôi">
                </div>
                <div class="tsbd-field">
                    <label for="ts_description"><?php _e( 'Mô tả / Lời kêu gọi', 'ts-bank-donate' ); ?></label>
                    <textarea id="ts_description" name="description" rows="2" placeholder="Quét mã QR hoặc chuyển khoản trực tiếp..."><?php echo esc_textarea( $s['description'] ); ?></textarea>
                </div>
                <div class="tsbd-field tsbd-field-inline">
                    <label>
                        <input type="checkbox" name="show_footer_credit" value="1" <?php checked( $s['show_footer_credit'] ); ?>>
                        <?php _e( 'Hiển thị "Powered by TS Donate"', 'ts-bank-donate' ); ?>
                    </label>
                </div>
            </div>

            <!-- APPEARANCE — Design Tokens -->
            <div class="tsbd-settings-section">
                <div class="tsbd-section-header">
                    <h2>🎨 <?php _e( 'Giao diện', 'ts-bank-donate' ); ?></h2>
                </div>

                <div class="tsbd-field">
                    <label for="ts_default_template"><?php _e( 'Template mặc định', 'ts-bank-donate' ); ?></label>
                    <select id="ts_default_template" name="default_template">
                        <?php foreach ( [ 'modern' => 'Modern Card', 'minimal' => 'Minimal', 'glass' => 'Glassmorphism', 'classic' => 'Classic' ] as $val => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $s['default_template'], $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small><?php _e( 'Có thể ghi đè cho từng tài khoản riêng.', 'ts-bank-donate' ); ?></small>
                </div>

                <div class="tsbd-field">
                    <label><?php _e( 'Gradient Header', 'ts-bank-donate' ); ?></label>
                    <div class="tsbd-color-row">
                        <div>
                            <small>Bắt đầu</small>
                            <input type="color" name="gradient_start" value="<?php echo esc_attr( $s['gradient_start'] ); ?>">
                        </div>
                        <div style="display:flex;align-items:center;color:#94a3b8;font-size:1.2rem">→</div>
                        <div>
                            <small>Kết thúc</small>
                            <input type="color" name="gradient_end" value="<?php echo esc_attr( $s['gradient_end'] ); ?>">
                        </div>
                        <div style="flex:1;height:32px;border-radius:8px;background:linear-gradient(135deg, <?php echo esc_attr( $s['gradient_start'] ); ?>, <?php echo esc_attr( $s['gradient_end'] ); ?>)"></div>
                    </div>
                </div>

                <div class="tsbd-field tsbd-color-row">
                    <div>
                        <label><?php _e( 'Màu chính', 'ts-bank-donate' ); ?></label>
                        <input type="color" name="primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>">
                    </div>
                    <div>
                        <label><?php _e( 'Màu nền', 'ts-bank-donate' ); ?></label>
                        <input type="color" name="bg_color" value="<?php echo esc_attr( $s['bg_color'] ); ?>">
                    </div>
                    <div>
                        <label><?php _e( 'Màu chữ', 'ts-bank-donate' ); ?></label>
                        <input type="color" name="text_color" value="<?php echo esc_attr( $s['text_color'] ); ?>">
                    </div>
                </div>

                <div class="tsbd-field tsbd-range-field">
                    <label><?php _e( 'Bo góc', 'ts-bank-donate' ); ?></label>
                    <div class="tsbd-range-wrap">
                        <input type="range" name="border_radius" min="0" max="32" value="<?php echo absint( $s['border_radius'] ); ?>" oninput="document.getElementById('tsbd_radius_val').textContent=this.value">
                        <span class="tsbd-range-val"><span id="tsbd_radius_val"><?php echo absint( $s['border_radius'] ); ?></span>px</span>
                    </div>
                </div>

                <div class="tsbd-field">
                    <label><?php _e( 'Max-width box', 'ts-bank-donate' ); ?></label>
                    <input type="text" name="max_width" value="<?php echo esc_attr( $s['max_width'] ); ?>" style="width:120px" placeholder="460px">
                </div>
            </div>

            <!-- ADVANCED -->
            <div class="tsbd-settings-section tsbd-settings-full">
                <div class="tsbd-section-header">
                    <h2>🔧 <?php _e( 'Nâng cao', 'ts-bank-donate' ); ?></h2>
                </div>
                <div class="tsbd-field">
                    <label for="ts_custom_css"><?php _e( 'Custom CSS', 'ts-bank-donate' ); ?></label>
                    <textarea id="ts_custom_css" name="custom_css" rows="5" style="font-family:monospace;font-size:13px" placeholder="/* Thêm CSS tuỳ chỉnh tại đây */"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
                </div>
                <div class="tsbd-field">
                    <button type="button" class="button" id="tsbd-clear-cache">
                        🔄 <?php _e( 'Xoá cache bank list', 'ts-bank-donate' ); ?>
                    </button>
                    <small><?php _e( 'Danh sách ngân hàng được cache 6 giờ.', 'ts-bank-donate' ); ?></small>
                </div>
            </div>

        </div>

        <div class="tsbd-form-actions">
            <button type="submit" class="button button-primary button-hero">
                💾 <?php _e( 'Lưu cài đặt', 'ts-bank-donate' ); ?>
            </button>
            <span class="spinner tsbd-spinner"></span>
        </div>
    </form>

    <!-- ABOUT / AUTHOR -->
    <div class="tsbd-settings-grid" style="margin-top:24px">
        <div class="tsbd-settings-section tsbd-settings-full tsbd-about-section">
            <div class="tsbd-section-header">
                <span class="dashicons dashicons-businessman"></span>
                <h2><?php _e( 'Tác giả & Hỗ trợ', 'ts-bank-donate' ); ?></h2>
            </div>
            <div class="tsbd-about-card">
                <div class="tsbd-about-avatar">T</div>
                <div class="tsbd-about-info">
                    <h3>Trần Vĩ Thành</h3>
                    <p class="tsbd-about-role">Full-stack WordPress Developer · Founder TechShare VN</p>
                    <p class="tsbd-about-bio">
                        Hơn 8 năm kinh nghiệm phát triển web, chuyên WordPress/WooCommerce, thiết kế website doanh nghiệp,
                        xây dựng hệ thống quản lý, plugin tùy chỉnh. Đã thực hiện nhiều dự án cho các doanh nghiệp trong và ngoài nước
                        như SR Vietnam, Mind Connector, Global Tax, Q2 Legal, DongRealty, Life360.vn...
                    </p>
                    <div class="tsbd-about-links">
                        <a href="https://techsharevn.com" target="_blank" rel="noopener">🌐 techsharevn.com</a>
                        <a href="mailto:thanh.web1001@gmail.com">✉️ thanh.web1001@gmail.com</a>
                        <a href="tel:0949897293">📞 0949 897 293</a>
                    </div>
                    <div class="tsbd-about-services">
                        <span>💼 Dịch vụ:</span>
                        <span class="tsbd-tag">Thiết kế Website</span>
                        <span class="tsbd-tag">WordPress Plugin</span>
                        <span class="tsbd-tag">WooCommerce</span>
                        <span class="tsbd-tag">Quản trị Web</span>
                        <span class="tsbd-tag">SEO</span>
                    </div>
                </div>
            </div>
            <p class="tsbd-about-footer">
                <em>TS Bank Donate v<?php echo TSBD_VERSION; ?> — Made with ❤️ by TechShare VN</em>
            </p>
        </div>
    </div>
</div>
