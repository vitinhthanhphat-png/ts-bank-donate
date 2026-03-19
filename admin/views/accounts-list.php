<?php
defined( 'ABSPATH' ) || exit;
$accounts = TSBD_Account::all();

/**
 * Helper: get avatar CSS class based on bank_bin or bank_short
 */
function tsbd_list_avatar_class( $account ) {
    $short = strtolower( $account['bank_short'] ?? '' );
    if ( $account['type'] === 'momo' ) return 'tsbd-acct-avatar is-momo';
    $map = [
        'vcb' => 'is-vcb', 'vietcombank' => 'is-vcb',
        'tcb' => 'is-tcb', 'techcombank' => 'is-tcb',
        'vtb' => 'is-vtb', 'vietinbank'  => 'is-vtb',
        'acb' => 'is-acb',
        'agr' => 'is-agr', 'agribank' => 'is-agr',
    ];
    foreach ( $map as $k => $cls ) {
        if ( strpos( $short, $k ) !== false ) return "tsbd-acct-avatar {$cls}";
    }
    return 'tsbd-acct-avatar';
}
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
                <p><?php _e( 'Quản lý tài khoản nhận donate', 'ts-bank-donate' ); ?></p>
            </div>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsbd-add' ) ); ?>" class="tsbd-admin-add-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?php _e( 'Thêm tài khoản', 'ts-bank-donate' ); ?>
        </a>
    </div>

    <!-- ─── Accounts Table ─── -->
    <div class="tsbd-table-card">
        <table class="tsbd-accounts-tbl">
            <thead>
                <tr>
                    <th style="width:48px"><?php _e( 'QR', 'ts-bank-donate' ); ?></th>
                    <th style="width:140px"><?php _e( 'ID', 'ts-bank-donate' ); ?></th>
                    <th><?php _e( 'Tài khoản', 'ts-bank-donate' ); ?></th>
                    <th><?php _e( 'Ngân hàng', 'ts-bank-donate' ); ?></th>
                    <th><?php _e( 'Template', 'ts-bank-donate' ); ?></th>
                    <th><?php _e( 'Shortcode', 'ts-bank-donate' ); ?></th>
                    <th class="col-center"><?php _e( 'Trạng thái', 'ts-bank-donate' ); ?></th>
                    <th class="col-right"><?php _e( 'Hành động', 'ts-bank-donate' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $accounts ) ) : ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px 20px;color:#8E8E93;font-size:0.88rem">
                            <?php _e( 'Chưa có tài khoản nào. Hãy thêm mới!', 'ts-bank-donate' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $accounts as $account ) :
                        $qr_url    = ! empty( $account['attachment_id'] ) ? wp_get_attachment_url( $account['attachment_id'] ) : '';
                        $edit_url  = add_query_arg( [ 'page' => 'tsbd-add', 'id' => $account['id'] ], admin_url( 'admin.php' ) );
                        $is_momo   = $account['type'] === 'momo';
                        $short     = $account['bank_short'] ?? ( $is_momo ? 'MoMo' : 'Bank' );
                        $initials  = mb_strtoupper( $is_momo ? 'M' : mb_substr( $short, 0, 2, 'UTF-8' ), 'UTF-8' );
                        $avatar_cls = tsbd_list_avatar_class( $account );
                        $tpl_label = $account['box_template'] ?: '—';
                    ?>
                    <tr data-id="<?php echo esc_attr( $account['id'] ); ?>">
                        <!-- QR thumb -->
                        <td>
                            <?php if ( $qr_url ) : ?>
                                <img src="<?php echo esc_url( $qr_url ); ?>" alt="QR" class="tsbd-qr-thumb">
                            <?php else : ?>
                                <span class="tsbd-no-qr">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Account ID -->
                        <td>
                            <div class="tsbd-sc-cell">
                                <code class="tsbd-id-code"><?php echo esc_html( $account['id'] ); ?></code>
                                <button type="button" class="tsbd-sc-copy" data-sc="<?php echo esc_attr( $account['id'] ); ?>" title="Copy ID">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </button>
                            </div>
                        </td>
                        <!-- Account info -->
                        <td>
                            <div class="tsbd-acct-cell">
                                <div class="<?php echo esc_attr( $avatar_cls ); ?>">
                                    <?php echo esc_html( $initials ); ?>
                                </div>
                                <div class="tsbd-acct-info">
                                    <div class="tsbd-acct-name"><?php echo esc_html( $account['label'] ?? '—' ); ?></div>
                                    <div class="tsbd-acct-sub">
                                        <?php if ( $is_momo ) : ?>
                                            <?php echo esc_html( $account['phone'] ?? '' ); ?>
                                        <?php else : ?>
                                            <?php echo esc_html( ( $account['account_no'] ?? '' ) . ' · ' . ( $account['account_name'] ?? '' ) ); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <!-- Bank badge -->
                        <td>
                            <?php if ( $is_momo ) : ?>
                                <span class="tsbd-badge tsbd-badge-momo">MoMo</span>
                            <?php else : ?>
                                <span class="tsbd-badge tsbd-badge-bank"><?php echo esc_html( $short ); ?></span>
                            <?php endif; ?>
                        </td>
                        <!-- Template badge -->
                        <td>
                            <span class="tsbd-badge tsbd-badge-tpl"><?php echo esc_html( $tpl_label ); ?></span>
                        </td>
                        <!-- Shortcode -->
                        <td>
                            <?php $sc = '[ts_donate accounts="' . esc_attr( $account['id'] ) . '"]'; ?>
                            <div class="tsbd-sc-cell">
                                <code class="tsbd-sc-code" title="<?php echo esc_attr( $sc ); ?>"><?php echo esc_html( $sc ); ?></code>
                                <button type="button" class="tsbd-sc-copy" data-sc="<?php echo esc_attr( $sc ); ?>" title="<?php esc_attr_e( 'Copy shortcode', 'ts-bank-donate' ); ?>">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </button>
                            </div>
                        </td>
                        <!-- Status -->
                        <td class="col-center">
                            <?php if ( ! empty( $account['active'] ) ) : ?>
                                <span class="tsbd-status-active">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php _e( 'Hiện', 'ts-bank-donate' ); ?>
                                </span>
                            <?php else : ?>
                                <span class="tsbd-status-inactive">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    <?php _e( 'Ẩn', 'ts-bank-donate' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <!-- Actions -->
                        <td class="col-right">
                            <div class="tsbd-action-btns">
                                <a href="<?php echo esc_url( $edit_url ); ?>" class="tsbd-action-btn is-edit" title="<?php esc_attr_e( 'Sửa', 'ts-bank-donate' ); ?>">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <button class="tsbd-action-btn is-regen tsbd-btn-regen" data-id="<?php echo esc_attr( $account['id'] ); ?>" title="<?php esc_attr_e( 'Tạo lại QR', 'ts-bank-donate' ); ?>">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                </button>
                                <button class="tsbd-action-btn is-delete tsbd-btn-delete" data-id="<?php echo esc_attr( $account['id'] ); ?>" title="<?php esc_attr_e( 'Xoá', 'ts-bank-donate' ); ?>">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6m4-6v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ─── Shortcode hint ─── -->
    <div class="tsbd-shortcode-hint">
        <h3><?php _e( 'Cách sử dụng shortcode', 'ts-bank-donate' ); ?></h3>
        <code>[ts_donate]</code> — <?php _e( 'Hiển thị tất cả tài khoản đang active', 'ts-bank-donate' ); ?><br>
        <code>[ts_donate accounts="acc_xxx,acc_yyy"]</code> — <?php _e( 'Chọn tài khoản cụ thể', 'ts-bank-donate' ); ?>
    </div>

</div>
