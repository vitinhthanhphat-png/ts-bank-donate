<?php
defined( 'ABSPATH' ) || exit;
$id      = sanitize_key( $_GET['id'] ?? '' );
$account = $id ? TSBD_Account::find( $id ) : [];
$is_edit = ! empty( $account );

$templates = [ 'modern' => 'Modern Card', 'minimal' => 'Minimal', 'glass' => 'Glassmorphism', 'classic' => 'Classic' ];
$vqr_tpls  = [ 'compact' => 'Compact (540×540)', 'compact2' => 'Compact2 (540×640)', 'qr_only' => 'QR thuần (480×480)', 'print' => 'In ấn (600×776)' ];
$qr_url    = ! empty( $account['attachment_id'] ) ? wp_get_attachment_url( $account['attachment_id'] ) : '';

$type      = $account['type'] ?? 'bank';
$bank_short = $account['bank_short'] ?? '';
$acct_no   = $account['account_no'] ?? '';
$label     = $account['label'] ?? '';

// QR preview badge: initials
$badge_initials = $type === 'momo' ? 'M'
    : mb_strtoupper( mb_substr( $bank_short ?: $label, 0, 1, 'UTF-8' ), 'UTF-8' );
?>
<div class="wrap tsbd-admin-wrap">

    <div class="tsbd-notice" id="tsbd-notice" style="display:none"></div>

    <!-- ─── Page Header ─── -->
    <div class="tsbd-admin-header">
        <div class="tsbd-admin-header-left">
            <div class="tsbd-admin-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
            </div>
            <div class="tsbd-admin-title-group">
                <h1><?php _e( 'TS Bank Donate', 'ts-bank-donate' ); ?></h1>
                <p><?php echo $is_edit ? __( 'Sửa tài khoản', 'ts-bank-donate' ) : __( 'Thêm tài khoản', 'ts-bank-donate' ); ?></p>
            </div>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ts-bank-donate' ) ); ?>" class="tsbd-admin-add-btn tsbd-admin-back-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
            <?php _e( 'Quay lại', 'ts-bank-donate' ); ?>
        </a>
    </div>

    <!-- ─── Breadcrumb ─── -->
    <div class="tsbd-breadcrumb">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ts-bank-donate' ) ); ?>"><?php _e( 'Tài khoản', 'ts-bank-donate' ); ?></a>
        <span class="sep">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
        <span class="current">
            <?php echo $is_edit
                ? esc_html( sprintf( __( 'Sửa: %s', 'ts-bank-donate' ), $label ?: 'Tài khoản' ) )
                : __( 'Thêm mới', 'ts-bank-donate' ); ?>
        </span>
    </div>

    <!-- ─── 2-column edit layout ─── -->
    <div class="tsbd-edit-layout">

        <!-- ─── Left: Form Card ─── -->
        <div class="tsbd-edit-form">
            <div class="tsbd-form-card">
                <div class="tsbd-form-card-head">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <h3><?php _e( 'Thông tin tài khoản', 'ts-bank-donate' ); ?></h3>
                </div>

                <div class="tsbd-form-card-body">
                    <form id="tsbd-account-form">
                        <input type="hidden" name="id" value="<?php echo esc_attr( $account['id'] ?? '' ); ?>">

                        <!-- Type selector pills -->
                        <div class="tsbd-field">
                            <label><?php _e( 'Loại tài khoản', 'ts-bank-donate' ); ?></label>
                            <div class="tsbd-type-pills">
                                <label class="tsbd-type-pill<?php echo $type === 'bank' ? ' is-selected' : ''; ?>" id="tsbd-pill-bank">
                                    <input type="radio" name="type" value="bank" <?php checked( $type, 'bank' ); ?>>
                                    <span class="tsbd-type-pill-dot"></span>
                                    <?php _e( 'Ngân hàng (VietQR)', 'ts-bank-donate' ); ?>
                                </label>
                                <label class="tsbd-type-pill<?php echo $type === 'momo' ? ' is-selected' : ''; ?>" id="tsbd-pill-momo">
                                    <input type="radio" name="type" value="momo" <?php checked( $type, 'momo' ); ?>>
                                    <span class="tsbd-type-pill-dot"></span>
                                    <?php _e( 'MoMo', 'ts-bank-donate' ); ?>
                                </label>
                            </div>
                        </div>

                        <!-- Label / Tab name -->
                        <div class="tsbd-field">
                            <label for="tsbd_label"><?php _e( 'Tên hiển thị (trên tab)', 'ts-bank-donate' ); ?> <span class="required">*</span></label>
                            <input type="text" id="tsbd_label" name="label" value="<?php echo esc_attr( $label ); ?>" required placeholder="VD: MB Bank Test">
                        </div>

                        <!-- BANK FIELDS -->
                        <div id="tsbd-bank-fields" class="tsbd-type-fields"<?php echo $type === 'momo' ? ' style="display:none"' : ''; ?>>

                            <!-- Bank + Account No (side-by-side) -->
                            <div class="tsbd-field-row">
                                <div class="tsbd-field">
                                    <label for="tsbd_bank_bin"><?php _e( 'Ngân hàng', 'ts-bank-donate' ); ?> <span class="required">*</span></label>
                                    <select id="tsbd_bank_bin" name="bank_bin">
                                        <option value=""><?php _e( 'Đang tải danh sách…', 'ts-bank-donate' ); ?></option>
                                    </select>
                                    <input type="hidden" id="tsbd_bank_short" name="bank_short" value="<?php echo esc_attr( $account['bank_short'] ?? '' ); ?>">
                                    <input type="hidden" id="tsbd_bank_saved_bin" value="<?php echo esc_attr( $account['bank_bin'] ?? '' ); ?>">
                                </div>
                                <div class="tsbd-field">
                                    <label for="tsbd_account_no"><?php _e( 'Số tài khoản', 'ts-bank-donate' ); ?> <span class="required">*</span></label>
                                    <input type="text" id="tsbd_account_no" name="account_no" value="<?php echo esc_attr( $acct_no ); ?>" inputmode="numeric" placeholder="0123456789">
                                </div>
                            </div>

                            <!-- Account owner name -->
                            <div class="tsbd-field">
                                <label for="tsbd_account_name"><?php _e( 'Tên chủ tài khoản (IN HOA)', 'ts-bank-donate' ); ?> <span class="required">*</span></label>
                                <input type="text" id="tsbd_account_name" name="account_name" value="<?php echo esc_attr( $account['account_name'] ?? '' ); ?>" style="text-transform:uppercase" placeholder="NGUYEN VAN A">
                            </div>

                            <!-- VietQR Template Picker -->
                            <div class="tsbd-field">
                                <label><?php _e( 'VietQR Image Template', 'ts-bank-donate' ); ?></label>
                                <p style="font-size:0.75rem;color:#8E8E93;margin:0 0 8px"><?php _e( 'Chọn kiểu layout ảnh QR được tạo từ VietQR.', 'ts-bank-donate' ); ?></p>
                                <div class="tsbd-vqr-options">
                                    <?php
                                    $cur = $account['vietqr_template'] ?? 'compact2';
                                    $vqr = [
                                        'compact'  => [ 'label' => 'Compact',   'size' => '540×540' ],
                                        'compact2' => [ 'label' => 'Compact 2', 'size' => '540×640', 'taller' => true ],
                                        'qr_only'  => [ 'label' => 'QR Only',   'size' => '480×480' ],
                                        'print'    => [ 'label' => 'Print',     'size' => '600×776', 'taller' => true ],
                                    ];
                                    foreach ( $vqr as $val => $info ) :
                                        $selected = $cur === $val;
                                        $h = ! empty( $info['taller'] ) ? '64' : '54';
                                    ?>
                                    <label class="tsbd-vqr-opt<?php echo $selected ? ' is-selected' : ''; ?>">
                                        <input type="radio" name="vietqr_template" value="<?php echo esc_attr( $val ); ?>" <?php checked( $cur, $val ); ?>>
                                        <svg viewBox="0 0 54 <?php echo $h; ?>" xmlns="http://www.w3.org/2000/svg" class="tsbd-vqr-svg">
                                            <rect width="54" height="<?php echo $h; ?>" rx="3" fill="#fff" stroke="#e2e8f0" stroke-width="1"/>
                                            <?php if ( $val === 'qr_only' ) : ?>
                                            <rect x="7" y="7" width="40" height="40" rx="2" fill="#f1f5f9"/>
                                            <rect x="11" y="11" width="14" height="14" rx="1" fill="#5B8CD5"/>
                                            <rect x="29" y="11" width="14" height="14" rx="1" fill="#5B8CD5"/>
                                            <rect x="11" y="29" width="14" height="14" rx="1" fill="#5B8CD5"/>
                                            <rect x="27" y="27" width="12" height="12" rx="1" fill="#94a3b8"/>
                                            <?php else : ?>
                                            <rect x="4" y="3" width="46" height="8" rx="2" fill="#f8fafc" stroke="#e2e8f0" stroke-width=".5"/>
                                            <rect x="6" y="5" width="12" height="4" rx="1" fill="#e2e8f0"/>
                                            <text x="28" y="8.5" font-size="4" fill="#94a3b8" text-anchor="middle">VIETQR</text>
                                            <rect x="4" y="14" width="46" height="32" rx="2" fill="#f1f5f9"/>
                                            <rect x="9" y="17" width="10" height="10" rx="1" fill="#5B8CD5"/>
                                            <rect x="35" y="17" width="10" height="10" rx="1" fill="#5B8CD5"/>
                                            <rect x="9" y="31" width="10" height="10" rx="1" fill="#5B8CD5"/>
                                            <rect x="23" y="21" width="8" height="8" rx="1" fill="#94a3b8"/>
                                            <rect x="35" y="31" width="10" height="10" rx="1" fill="#94a3b8"/>
                                            <?php if ( ! empty( $info['taller'] ) ) : ?>
                                            <rect x="4" y="49" width="46" height="6" rx="2" fill="#f8fafc" stroke="#e2e8f0" stroke-width=".5"/>
                                            <rect x="7" y="51" width="16" height="2" rx="1" fill="#cbd5e1"/>
                                            <rect x="25" y="51" width="20" height="2" rx="1" fill="#cbd5e1"/>
                                            <?php endif; ?>
                                            <rect x="4" y="47" width="46" height="3" rx="1" fill="#e2e8f0"/>
                                            <?php endif; ?>
                                        </svg>
                                        <span class="tsbd-vqr-label">
                                            <?php echo esc_html( $info['label'] ); ?>
                                            <em><?php echo esc_html( $info['size'] ); ?></em>
                                        </span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- MOMO FIELDS -->
                        <div id="tsbd-momo-fields" class="tsbd-type-fields"<?php echo $type !== 'momo' ? ' style="display:none"' : ''; ?>>
                            <div class="tsbd-field-row">
                                <div class="tsbd-field">
                                    <label for="tsbd_phone"><?php _e( 'Số điện thoại MoMo', 'ts-bank-donate' ); ?> <span class="required">*</span></label>
                                    <input type="tel" id="tsbd_phone" name="phone" value="<?php echo esc_attr( $account['phone'] ?? '' ); ?>" inputmode="tel" placeholder="0912345678">
                                </div>
                                <div class="tsbd-field">
                                    <label for="tsbd_momo_name"><?php _e( 'Tên tài khoản MoMo', 'ts-bank-donate' ); ?></label>
                                    <input type="text" id="tsbd_momo_name" name="account_name" value="<?php echo esc_attr( $account['account_name'] ?? '' ); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Common: Note + Amount (side-by-side) -->
                        <div class="tsbd-field-row">
                            <div class="tsbd-field">
                                <label for="tsbd_default_note"><?php _e( 'Nội dung CK mặc định', 'ts-bank-donate' ); ?></label>
                                <input type="text" id="tsbd_default_note" name="default_note" value="<?php echo esc_attr( $account['default_note'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Ủng hộ Website', 'ts-bank-donate' ); ?>">
                            </div>
                            <div class="tsbd-field">
                                <label for="tsbd_default_amount"><?php _e( 'Số tiền mặc định (0 = tự nhập)', 'ts-bank-donate' ); ?></label>
                                <input type="number" id="tsbd_default_amount" name="default_amount" value="<?php echo esc_attr( $account['default_amount'] ?? 0 ); ?>" min="0" step="1000">
                            </div>
                        </div>

                        <!-- Box display template -->
                        <div class="tsbd-field">
                            <label for="tsbd_box_template"><?php _e( 'Box Display Template', 'ts-bank-donate' ); ?></label>
                            <select id="tsbd_box_template" name="box_template">
                                <option value=""><?php _e( '(dùng global setting)', 'ts-bank-donate' ); ?></option>
                                <?php foreach ( $templates as $val => $lbl ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $account['box_template'] ?? '', $val ); ?>><?php echo esc_html( $lbl ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Template live preview cards -->
                            <?php
                            $tpl_meta = [
                                ''        => [ 'icon' => '🌐', 'desc' => 'Dùng setting toàn cục', 'color' => '#8E8E93' ],
                                'modern'  => [ 'icon' => '✦', 'desc' => 'Card hiện đại, gradient header, có QR code to lớn', 'color' => '#5B8CD5' ],
                                'minimal' => [ 'icon' => '—', 'desc' => 'Tối giản, chỉ thông tin cần thiết', 'color' => '#7C9070' ],
                                'glass'   => [ 'icon' => '◈', 'desc' => 'Glassmorphism, hiệu ứng trong mờ', 'color' => '#9B8EC4' ],
                                'classic' => [ 'icon' => '▣', 'desc' => 'Classic, phù hợp mọi theme', 'color' => '#D4845E' ],
                            ];
                            $cur_tpl = $account['box_template'] ?? '';
                            ?>
                            <div class="tsbd-tpl-preview-strip" id="tsbd-tpl-strip">
                                <?php foreach ( $tpl_meta as $tval => $tmeta ) : ?>
                                <div class="tsbd-tpl-card<?php echo $cur_tpl === $tval ? ' is-active' : ''; ?>" data-tpl="<?php echo esc_attr( $tval ); ?>">
                                    <span class="tsbd-tpl-icon" style="color:<?php echo esc_attr( $tmeta['color'] ); ?>"><?php echo $tmeta['icon']; ?></span>
                                    <span class="tsbd-tpl-name"><?php echo esc_html( $tval ?: 'global' ); ?></span>
                                    <span class="tsbd-tpl-desc"><?php echo esc_html( $tmeta['desc'] ); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Active toggle -->
                        <div class="tsbd-field tsbd-field-inline tsbd-field-mt">
                            <label>
                                <input type="checkbox" name="active" value="1" <?php checked( $account['active'] ?? true ); ?>>
                                <?php _e( 'Hiển thị (active)', 'ts-bank-donate' ); ?>
                            </label>
                        </div>

                        <!-- Save button -->
                        <div class="tsbd-form-actions">
                            <button type="submit" class="tsbd-save-btn" id="tsbd-save-btn">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                <?php echo $is_edit ? __( 'Cập nhật & Tạo QR', 'ts-bank-donate' ) : __( 'Lưu & Tạo QR', 'ts-bank-donate' ); ?>
                            </button>
                            <span class="tsbd-spinner spinner"></span>
                        </div>
                    </form>
                </div><!-- .tsbd-form-card-body -->
            </div><!-- .tsbd-form-card -->
        </div><!-- .tsbd-edit-form -->

        <!-- ─ Right: QR Preview Panel ─ -->
        <div class="tsbd-qr-preview-box"
             data-bank-bin="<?php echo esc_attr( $account['bank_bin'] ?? '' ); ?>"
             data-account-no="<?php echo esc_attr( $acct_no ); ?>"
             data-account-name="<?php echo esc_attr( $account['account_name'] ?? '' ); ?>"
             data-vqr-tpl="<?php echo esc_attr( $account['vietqr_template'] ?? 'compact2' ); ?>">
            <!-- Preview tabs: QR / Template -->
            <div class="tsbd-preview-tabs">
                <button type="button" class="tsbd-preview-tab is-active" data-tab="qr">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <?php _e( 'QR Code', 'ts-bank-donate' ); ?>
                    <span class="tsbd-qr-live-badge">Live</span>
                </button>
                <button type="button" class="tsbd-preview-tab" data-tab="template">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <?php _e( 'Template', 'ts-bank-donate' ); ?>
                </button>
            </div>
            <div class="tsbd-qr-preview-body">
                <?php if ( $qr_url ) : ?>
                    <img id="tsbd-qr-preview" src="<?php echo esc_url( $qr_url ); ?>" alt="QR Preview">
                    <div id="tsbd-qr-preview-placeholder" style="display:none"><?php _e( 'QR sẽ xuất hiện sau khi lưu.', 'ts-bank-donate' ); ?></div>
                <?php else : ?>
                    <div id="tsbd-qr-preview-placeholder"><?php _e( 'Nhập thông tin ngân hàng để xem trước QR.', 'ts-bank-donate' ); ?></div>
                    <img id="tsbd-qr-preview" src="" alt="QR Preview" style="display:none">
                <?php endif; ?>

                <p class="tsbd-qr-live-note">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php _e( 'Preview tự động cập nhật. QR chỉ được lưu khi bấm "Lưu & Tạo QR".', 'ts-bank-donate' ); ?>
                </p>

                <?php if ( ! empty( $bank_short ) || ! empty( $acct_no ) ) : ?>
                <div class="tsbd-qr-bank-badge" id="tsbd-qr-bank-badge">
                    <div class="tsbd-qr-bank-badge-ico" id="tsbd-qr-badge-ico"><?php echo esc_html( $badge_initials ); ?></div>
                    <div>
                        <div class="tsbd-qr-bank-badge-name" id="tsbd-qr-badge-name"><?php echo esc_html( $bank_short ?: $label ); ?></div>
                        <?php if ( $acct_no ) : ?>
                        <div class="tsbd-qr-bank-badge-acct" id="tsbd-qr-badge-acct"><?php echo esc_html( $acct_no ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else : ?>
                <div class="tsbd-qr-bank-badge" id="tsbd-qr-bank-badge" style="display:none">
                    <div class="tsbd-qr-bank-badge-ico" id="tsbd-qr-badge-ico"></div>
                    <div>
                        <div class="tsbd-qr-bank-badge-name" id="tsbd-qr-badge-name"></div>
                        <div class="tsbd-qr-bank-badge-acct" id="tsbd-qr-badge-acct"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $account['attachment_id'] ) ) : ?>
                    <a href="<?php echo esc_url( get_edit_post_link( $account['attachment_id'] ) ); ?>" target="_blank" class="tsbd-media-link">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <?php _e( 'Xem trong Media Library', 'ts-bank-donate' ); ?>
                    </a>
                <?php endif; ?>
            </div><!-- .tsbd-qr-preview-body (QR tab) -->

            <!-- Template live preview tab -->
            <div class="tsbd-tpl-live-pane" id="tsbd-tpl-live-pane" style="display:none">
                <div class="tsbd-tpl-live-scale-wrap">
                    <?php
                    $settings_obj = TSBD_Settings::all();
                    $preview_tpl  = $account['box_template'] ?? 'modern';
                    $preview_title = $settings_obj['title'] ?? 'Ủng hộ chúng tôi';
                    $preview_desc  = $settings_obj['description'] ?? '';
                    ?>
                    <!-- Full widget structure matching frontend output exactly -->
                    <div class="tsbd-box" id="tsbd-tpl-live-box" style="pointer-events:none;">
                        <!-- Header (gradient banner) -->
                        <div class="tsbd-header">
                            <h3 class="tsbd-title"><?php echo esc_html( $preview_title ); ?></h3>
                            <?php if ( $preview_desc ) : ?>
                                <p class="tsbd-desc"><?php echo esc_html( $preview_desc ); ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- Tabs (single tab for preview) -->
                        <div class="tsbd-tabs">
                            <button class="tsbd-tab is-active"><?php echo esc_html( $label ?: $bank_short ?: 'Tài khoản' ); ?></button>
                        </div>
                        <!-- Panel with template class — JS updates this id -->
                        <div class="tsbd-panel tsbd-template-<?php echo esc_attr( $preview_tpl ); ?> is-active" id="tsbd-tpl-live-panel" role="tabpanel">
                            <div class="tsbd-panel-inner">
                                <div class="tsbd-panel-content">
                                    <div class="tsbd-bank-intro">
                                        <?php
                                        // Same brand colors map as frontend template
                                        $admin_bank_colors = [
                                            'VCB' => '#00703C', 'Vietcombank' => '#00703C',
                                            'CTG' => '#004B8D', 'VietinBank' => '#004B8D',
                                            'BIDV'=> '#004B91',
                                            'TCB' => '#ED1C24', 'Techcombank'=> '#ED1C24',
                                            'MB'  => '#1A4D8F', 'MBBank'     => '#1A4D8F',
                                            'ACB' => '#1A237E',
                                            'VPB' => '#00A651', 'VPBank'     => '#00A651',
                                            'SHB' => '#0066B3',
                                            'TPB' => '#6C2D82', 'TPBank'     => '#6C2D82',
                                            'STB' => '#0052A5', 'Sacombank'  => '#0052A5',
                                            'HDB' => '#E30613', 'HDBank'     => '#E30613',
                                            'OCB' => '#E87722',
                                            'AGR' => '#004B3E', 'Agribank'   => '#004B3E',
                                        ];
                                        $av_brand = $admin_bank_colors[ $bank_short ] ?? '';
                                        $av_style = $av_brand ? "background:{$av_brand};color:#fff;" : '';
                                        $av_cls = 'tsbd-bank-avatar';
                                        if ( $type === 'momo' ) $av_cls .= ' is-momo';
                                        ?>
                                        <div class="<?php echo esc_attr( $av_cls ); ?>" id="tsbd-lp-avatar"<?php echo $av_style ? ' style="' . esc_attr( $av_style ) . '"' : ''; ?>>
                                            <?php echo esc_html( $badge_initials ); ?>
                                        </div>
                                        <div class="tsbd-bank-intro-info">
                                            <p class="tsbd-bank-name" id="tsbd-lp-name"><?php echo esc_html( $label ?: $bank_short ); ?></p>
                                            <p class="tsbd-bank-sub"><?php echo $type === 'momo' ? 'Ví điện tử MoMo' : 'Tài khoản nhận donate'; ?></p>
                                        </div>
                                    </div>
                                    <?php if ( $qr_url ) : ?>
                                    <div class="tsbd-qr-card">
                                        <div class="tsbd-qr-wrap"><img class="tsbd-qr-img" src="<?php echo esc_url( $qr_url ); ?>" alt="QR" style="max-width:100%;height:auto;"></div>
                                        <p class="tsbd-qr-hint">Quét mã QR để chuyển khoản</p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="tsbd-info-rows">
                                        <div class="tsbd-info-row">
                                            <div class="tsbd-info-row-left">
                                                <span class="tsbd-info-label"><?php echo $type === 'momo' ? 'SĐT MoMo' : 'Số tài khoản'; ?></span>
                                                <span class="tsbd-info-value tsbd-account-no" id="tsbd-lp-acct"><?php echo esc_html( $type === 'momo' ? ( $account['phone'] ?? '' ) : $acct_no ); ?></span>
                                            </div>
                                        </div>
                                        <?php if ( ! empty( $account['account_name'] ) ) : ?>
                                        <div class="tsbd-info-row">
                                            <div class="tsbd-info-row-left">
                                                <span class="tsbd-info-label">Tên chủ TK</span>
                                                <span class="tsbd-info-value" id="tsbd-lp-owner"><?php echo esc_html( $account['account_name'] ); ?></span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="tsbd-copy-btn">
                                        ♡ <?php echo $type === 'momo' ? 'Mở ứng dụng MoMo' : 'Mở app ngân hàng để CK'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div><!-- .tsbd-box -->
                </div>
                <p class="tsbd-qr-live-note" style="margin:8px 16px 12px;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php _e( 'Preview kiểu hiển thị widget frontend.', 'ts-bank-donate' ); ?>
                </p>
            </div><!-- .tsbd-tpl-live-pane -->
        </div>

    </div><!-- .tsbd-edit-layout -->
</div><!-- .wrap -->
