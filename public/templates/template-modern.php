<?php
/**
 * Modern Card Template — Redesigned (Scandinavian Minimal Fintech)
 * Variables:
 *   $account   - account array
 *   $settings  - global settings array
 *   $note      - resolved note string
 *   $amount    - resolved default amount int
 *   $show_note - bool
 *   $show_amount - bool
 */
defined( 'ABSPATH' ) || exit;

// For MoMo: prefer user-uploaded QR over auto-generated
$qr_url = '';
if ( ( $account['type'] ?? 'bank' ) === 'momo' && ! empty( $account['momo_qr_custom'] ) ) {
    $qr_url = wp_get_attachment_url( $account['momo_qr_custom'] );
}
if ( ! $qr_url && ! empty( $account['attachment_id'] ) ) {
    $qr_url = wp_get_attachment_url( $account['attachment_id'] );
}
$currency = esc_html( $settings['currency'] ?? 'đ' );
$type     = $account['type'] ?? 'bank';
$phone    = $account['phone'] ?? '';
$label    = $account['label'] ?? '';
$bank_short = $account['bank_short'] ?? 'BK';
// Bank brand colors map (keyed by bank_short)
$bank_colors = [
    'VCB'  => '#00703C', 'Vietcombank' => '#00703C',
    'CTG'  => '#004B8D', 'VietinBank'  => '#004B8D',
    'BIDV' => '#004B91',
    'TCB'  => '#ED1C24', 'Techcombank' => '#ED1C24',
    'MB'   => '#1A4D8F', 'MBBank'      => '#1A4D8F',
    'ACB'  => '#1A237E',
    'VPB'  => '#00A651', 'VPBank'      => '#00A651',
    'SHB'  => '#0066B3',
    'TPB'  => '#6C2D82', 'TPBank'      => '#6C2D82',
    'STB'  => '#0052A5', 'Sacombank'   => '#0052A5',
    'HDB'  => '#E30613', 'HDBank'      => '#E30613',
    'OCB'  => '#E87722',
    'MSB'  => '#D91E18',
    'VIB'  => '#003DA5',
    'EIB'  => '#004990', 'Eximbank'    => '#004990',
    'LPB'  => '#00539B', 'LienVietPostBank' => '#00539B',
    'SSB'  => '#FF6600', 'SeABank'     => '#FF6600',
    'NAB'  => '#003E7E', 'NamABank'    => '#003E7E',
    'BAB'  => '#005CA9', 'BacABank'    => '#005CA9',
    'AGR'  => '#004B3E', 'Agribank'    => '#004B3E',
    'ABB'  => '#0072BC', 'ABBank'      => '#0072BC',
    'NCB'  => '#0073B7',
    'PGB'  => '#00539F', 'PGBank'      => '#00539F',
    'VAB'  => '#003399', 'VietABank'   => '#003399',
    'KLB'  => '#004B9B', 'KienLongBank'=> '#004B9B',
    'DAB'  => '#00498D', 'DongABank'   => '#00498D',
    'CIMB' => '#ED1C24',
    'CAKE' => '#F7941D',
    'Ubank'=> '#ED1C24',
    'SCBVN'=> '#0072CE', 'SCB' => '#0072CE',
];

// Avatar: use initials with brand color background
$avatar = mb_strtoupper( mb_substr( $type === 'momo' ? 'M' : ( $bank_short ?: $label ), 0, 1, 'UTF-8' ), 'UTF-8' );
$brand_color = $bank_colors[ $bank_short ] ?? '';
$avatar_style = $brand_color ? "background:{$brand_color};color:#fff;" : '';
$avatar_class = 'tsbd-bank-avatar';
if ( $type === 'momo' ) $avatar_class .= ' is-momo';
?>
<div class="tsbd-panel-inner"
     data-account-no="<?php echo esc_attr( $account['account_no'] ?? '' ); ?>"
     data-account-name="<?php echo esc_attr( $account['account_name'] ?? '' ); ?>"
     data-bank-name="<?php echo esc_attr( $bank_short ?: $label ); ?>"
     data-default-note="<?php echo esc_attr( $note ); ?>">

    <div class="tsbd-panel-content">

        <?php /* ─── Bank intro row ─── */ ?>
        <div class="tsbd-bank-intro">
            <div class="<?php echo esc_attr( $avatar_class ); ?>"<?php echo $avatar_style ? ' style="' . esc_attr( $avatar_style ) . '"' : ''; ?>>
                <?php echo esc_html( $avatar ); ?>
            </div>
            <div class="tsbd-bank-intro-info">
                <p class="tsbd-bank-name">
                    <?php echo esc_html( $label ); ?>
                </p>
                <p class="tsbd-bank-sub">
                    <?php echo $type === 'bank'
                        ? esc_html__( 'Tài khoản nhận donate', 'ts-bank-donate' )
                        : esc_html__( 'Ví điện tử MoMo', 'ts-bank-donate' ); ?>
                </p>
            </div>
        </div>

        <?php /* ─── QR Image Card ─── */ ?>
        <?php if ( $qr_url ) : ?>
        <div class="tsbd-qr-card">
            <div class="tsbd-qr-wrap">
                <img class="tsbd-qr-img"
                     src="<?php echo esc_url( $qr_url ); ?>"
                     alt="QR <?php echo esc_attr( $label ); ?>"
                     loading="lazy">
            </div>
            <p class="tsbd-qr-hint"><?php esc_html_e( 'Quét mã QR để chuyển khoản', 'ts-bank-donate' ); ?></p>
        </div>
        <?php endif; ?>

        <?php /* ─── Account info rows ─── */ ?>
        <div class="tsbd-info-rows">
            <?php if ( $type === 'bank' ) : ?>

                <?php /* Account number row */ ?>
                <div class="tsbd-info-row">
                    <div class="tsbd-info-row-left">
                        <span class="tsbd-info-label"><?php esc_html_e( 'Số tài khoản', 'ts-bank-donate' ); ?></span>
                        <span class="tsbd-info-value tsbd-account-no"><?php echo esc_html( $account['account_no'] ?? '' ); ?></span>
                    </div>
                    <button type="button" class="tsbd-info-action tsbd-copy-acct" title="<?php esc_attr_e( 'Copy số tài khoản', 'ts-bank-donate' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </button>
                </div>

                <?php /* Account owner row */ ?>
                <div class="tsbd-info-row">
                    <div class="tsbd-info-row-left">
                        <span class="tsbd-info-label"><?php esc_html_e( 'Tên chủ TK', 'ts-bank-donate' ); ?></span>
                        <span class="tsbd-info-value tsbd-account-owner"><?php echo esc_html( $account['account_name'] ?? '' ); ?></span>
                    </div>
                </div>

            <?php else : ?>

                <?php /* MoMo phone row */ ?>
                <div class="tsbd-info-row">
                    <div class="tsbd-info-row-left">
                        <span class="tsbd-info-label"><?php esc_html_e( 'Số điện thoại MoMo', 'ts-bank-donate' ); ?></span>
                        <span class="tsbd-info-value tsbd-account-no"><?php echo esc_html( $phone ); ?></span>
                    </div>
                    <button type="button" class="tsbd-info-action tsbd-copy-acct" title="<?php esc_attr_e( 'Copy SĐT', 'ts-bank-donate' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </button>
                </div>

                <?php /* MoMo account name */ ?>
                <?php if ( ! empty( $account['account_name'] ) ) : ?>
                <div class="tsbd-info-row">
                    <div class="tsbd-info-row-left">
                        <span class="tsbd-info-label"><?php esc_html_e( 'Tên tài khoản', 'ts-bank-donate' ); ?></span>
                        <span class="tsbd-info-value"><?php echo esc_html( $account['account_name'] ); ?></span>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>



        <?php /* ─── CTA / Action Button ─── */ ?>
        <?php if ( $type === 'momo' ) : ?>
            <a href="momo://pay?action=payRequest&phone=<?php echo rawurlencode( $phone ); ?>&amount=0&note=<?php echo rawurlencode( $note ); ?>"
               class="tsbd-momo-open-btn"
               role="button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                <?php esc_html_e( 'Mở ứng dụng MoMo', 'ts-bank-donate' ); ?>
            </a>
        <?php else : ?>
            <button type="button" class="tsbd-copy-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <?php esc_html_e( 'Mở app ngân hàng để CK', 'ts-bank-donate' ); ?>
            </button>
        <?php endif; ?>

    </div>
</div>
