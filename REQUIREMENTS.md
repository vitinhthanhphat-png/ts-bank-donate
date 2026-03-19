# REQUIREMENTS: TS Bank Donate Plugin

> **Plugin Slug:** `ts_bank_donate`
> **Version Target:** 1.0.0
> **Last Updated:** 2026-03-18
> **Stack:** WordPress Plugin (PHP 7.4+, Vanilla JS/CSS)

---

## 1. TỔNG QUAN (Overview)

Plugin WordPress nhỏ gọn cho phép hiển thị **hộp donate** ngoài frontend, hỗ trợ QR chuyển khoản ngân hàng (VietQR) và ví MoMo. Admin quản lý nhiều tài khoản, mỗi tài khoản tự động sinh ảnh QR và lưu vào **WordPress Media Library** — không generate lại mỗi lần hiển thị.

---

## 2. KIẾN TRÚC KỸ THUẬT (Technical Architecture)

### 2.1 QR Generation Strategy

**Phương pháp: Option C — VietQR Image API + WordPress Media Cache**

```
[Admin Save Account] 
    → PHP fetch PNG từ img.vietqr.io 
    → wp_upload_bits() lưu file vào uploads/ts-donate/
    → wp_insert_attachment() đăng ký vào Media Library
    → Lưu attachment_id vào account array trong wp_options
[Frontend shortcode]
    → Đọc attachment_id → wp_get_attachment_url()
    → Render <img src="..."> tĩnh — KHÔNG gọi API
```

### 2.2 VietQR API Endpoint

```
https://img.vietqr.io/image/{BANK_BIN}-{ACCOUNT_NO}-{TEMPLATE}.png
    ?amount={AMOUNT}
    &addInfo={ENCODE_NOTE}
    &accountName={OWNER_NAME}
```

**Templates có sẵn:**
| Template | Kích thước | Mô tả |
|---|---|---|
| `compact` | 540×540 | QR + Logo VietQR + Logo Ngân hàng |
| `compact2` | 540×640 | QR + Logo + Thông tin chuyển khoản |
| `qr_only` | 480×480 | Chỉ mã QR thuần |
| `print` | 600×776 | QR + Logo + Thông tin đầy đủ (cho in ấn) |

**Re-generate trigger:** Khi Admin thay đổi: số tài khoản, tên chủ, note mặc định, template, hoặc nhấn "Regenerate QR" thủ công.

### 2.3 MoMo Integration Strategy

**✅ Confirmed: Auto-generate deeplink QR (PHP QR Lib)**

Dùng `endroid/qr-code` (bundled, không cần Composer) để tạo QR từ deeplink MoMo:
```
momo://pay?action=payRequest&phone={PHONE}&amount=0&note={NOTE}
```
- PHP render PNG → `wp_upload_bits()` → `wp_insert_attachment()` → lưu `attachment_id`
- Frontend: `<img src>` tĩnh như VietQR
- Desktop fallback: Button "Mở MoMo" link `https://me.momo.vn/{phone}` hoặc `https://nhantien.momo.vn`

> **Lưu ý:** QR MoMo deeplink chỉ hoạt động khi điện thoại có cài app MoMo. Desktop user dùng button fallback.

---

## 3. CẤU TRÚC FILE PLUGIN

```
ts_bank_donate/
├── ts-bank-donate.php               # Main plugin file (headers, init)
├── REQUIREMENTS.md
│
├── includes/
│   ├── class-tsbd-plugin.php        # Singleton loader + hook registration
│   ├── class-tsbd-account.php       # Account CRUD via wp_options
│   ├── class-tsbd-qr-generator.php  # VietQR fetch + Media save
│   ├── class-tsbd-momo-qr.php       # MoMo deeplink QR via bundled lib
│   ├── class-tsbd-shortcode.php     # [ts_donate] render
│   └── class-tsbd-settings.php      # Settings get/set helpers
│
├── admin/
│   ├── class-tsbd-admin.php         # Admin menu, AJAX handlers
│   ├── views/
│   │   ├── accounts-list.php        # Danh sách tài khoản (HTML table)
│   │   ├── account-edit.php         # Form thêm/sửa tài khoản
│   │   └── settings-page.php        # Settings page (WP Settings API)
│   └── js/
│       └── tsbd-admin.js            # AJAX bank list, QR preview, regenerate
│
├── public/
│   ├── class-tsbd-frontend.php      # Enqueue CSS/JS conditionally
│   ├── templates/
│   │   ├── template-modern.php
│   │   ├── template-minimal.php
│   │   ├── template-glass.php
│   │   └── template-classic.php
│   ├── css/
│   │   ├── tsbd-base.css            # CSS Variables, reset, shared
│   │   ├── tsbd-modern.css
│   │   ├── tsbd-minimal.css
│   │   ├── tsbd-glass.css
│   │   └── tsbd-classic.css
│   └── js/
│       └── tsbd-public.js           # Tab, amount buttons, copy toast
│
├── lib/
│   └── phpqrcode/                   # phpqrcode.sourceforge.net (bundled)
│       └── qrlib.php
│
└── assets/
    └── bank-logos/                  # SVG logos (optional fallback)
```

---

## 4. TÍNH NĂNG CHI TIẾT

### 4.1 Admin — Quản lý Tài Khoản

#### Danh sách tài khoản (`WP_List_Table`)
- Columns: Tên tài khoản, Ngân hàng/Ví, Số TK/SĐT, Template QR, Trạng thái, QR Preview (thumbnail), Actions
- Bulk actions: Delete, Regenerate QR

#### Form thêm/sửa tài khoản
```
Fields:
├── Loại (radio): Ngân hàng | MoMo
│
├── [Nếu là Ngân hàng]
│   ├── Chọn Ngân hàng (Select AJAX — gọi api.vietqr.io/v2/banks realtime khi open form)
│   ├── Số tài khoản *
│   ├── Tên chủ tài khoản *
│   ├── VietQR Image Template (radio + preview thumbnail): compact | compact2 | qr_only | print
│   ├── Box Display Template ✅ per-account (select): modern | minimal | glass | classic
│   ├── Nội dung CK mặc định (text) → dùng khi generate QR
│   └── Số tiền mặc định (number, tùy chọn) → 0 = user tự nhập
│
├── [Nếu là MoMo]
│   ├── Số điện thoại MoMo *
│   ├── Tên tài khoản MoMo *
│   ├── Box Display Template ✅ per-account (select): modern | minimal | glass | classic
│   └── Nội dung mặc định
│
├── Thứ tự hiển thị (order)
├── Hiển thị (toggle active/inactive)
│
└── [Preview QR] + [Lưu & Tạo QR]
```

#### Nút "Regenerate QR"
- Xoá attachment cũ khỏi Media Library
- Fetch lại từ VietQR API / regenerate từ deeplink
- Lưu attachment_id mới
- Hiển thị thông báo thành công/thất bại

### 4.2 Admin — Cài đặt Toàn cục (`Settings API`)

```
GENERAL
├── Tên box donate (heading)
├── Mô tả/lời kêu gọi (textarea)
├── Hiển thị số tiền gợi ý (toggle)
├── Các mức tiền gợi ý (text, cách nhau dấu phẩy): 20000,50000,100000,200000
├── Cho phép nhập số tiền tự do (toggle)
├── Cho phép thay đổi nội dung CK (toggle)
└── Currency symbol (mặc định: VNĐ)

APPEARANCE
├── Box template mặc định (select): modern | minimal | glass | classic
├── Primary color (#color picker)  → CSS var(--tsbd-primary)
├── Background color (#color picker) → CSS var(--tsbd-bg)
├── Text color (#color picker) → CSS var(--tsbd-text)
├── Border radius (px slider)
├── Box shadow (toggle + intensity)
├── Max width box (px, mặc định: 480px)
└── Position shortcode (center | left | right)

ADVANCED
├── Custom CSS (textarea codemirror)
├── Load Google Fonts (toggle)
└── Cache busting (button "Xoá cache QR")
```

### 4.3 Frontend — Shortcode `[ts_donate]`

#### Cú pháp đầy đủ
```
[ts_donate 
  template="modern"
  accounts="1,3"
  amount="50000"
  note="Ủng hộ Website"
  show_note_field="true"
  show_amount_field="true"
  title="Ủng hộ chúng tôi"
  width="400px"
]
```

#### Shortcode Args
| Arg | Mặc định | Mô tả |
|---|---|---|
| `template` | settings global | Override template |
| `accounts` | tất cả active | IDs tài khoản muốn show (cách nhau dấu phẩy) |
| `amount` | 0 | Số tiền mặc định (0 = trống) |
| `note` | từ account | Override nội dung CK |
| `show_note_field` | theo settings | Hiện ô nhập nội dung CK |
| `show_amount_field` | theo settings | Hiện ô nhập số tiền |
| `title` | từ settings | Override tiêu đề box |
| `width` | 480px | Override max-width |

#### Frontend Render Flow

```
[ts_donate shortcode]
    └── Render <div class="tsbd-box tsbd-template-{name}">
            ├── Header: Title + Description
            ├── Tab Bar: [Bank 1][Bank 2][MoMo]   (nếu > 1 TK)
            ├── Tab Content (active bank):
            │   ├── QR Image (static <img>)
            │   ├── Bank Info: Logo + Tên NH + Số TK + Tên CTK
            │   ├── Amount Suggested Buttons: [20K] [50K] [100K] [200K]
            │   ├── Input: Số tiền (số, có format VNĐ)
            │   ├── Input: Nội dung CK (text)
            │   ├── Copy Button: Copy thông tin CK
            │   └── [MoMo tab]: Button "Mở MoMo" (deeplink)
            └── Footer: Powered by TS Donate (có thể tắt)
```

#### Interactive Logic (Vanilla JS)
- Tab switching: fade transition giữa các ngân hàng
- Amount buttons: click chọn → highlight, update input
- Copy button: copy `{STK} | {TÊN} | {SỐ TIỀN} | {NỘI DUNG}` vào clipboard → toast "Đã copy!"
- Note field change: chỉ thay đổi text hiển thị trên UI — QR ảnh lưu sẵn không đổi
- Nếu amount = 0 và không có input → QR hiển thị bình thường (user nhập trên app)

---

## 5. TEMPLATES CHO BOX DONATE

**✅ Confirmed: Tất cả 4 templates trong v1.0. Mỗi tài khoản chọn template riêng (per-account), fallback về global setting nếu chưa chọn.**

Mỗi template là 1 bộ PHP view + CSS file riêng biệt. Global settings chứa template mặc định.

### Template: Modern Card (`modern`)
```
- Nền: Gradient nhẹ (primary color → lighter shade)
- QR: Bordered card với subtle box-shadow
- Tabs: Pill-style tabs với logo ngân hàng nhỏ
- Typography: Inter/Nunito, clean, weight 500-700
- Color: CSS vars từ primary color settings
- Accent: Gradient button, animated active-tab indicator
```

### Template: Minimal (`minimal`)
```
- Nền: Trắng hoặc light gray (#f8f9fa)
- QR: Không viền, trực tiếp, centered
- Tabs: Underline tabs, no background
- Typography: System font stack (-apple-system, BlinkMacSystemFont...)
- Emphasis: Minimal markup, nhỏ gọn, tải nhanh nhất
```

### Template: Glassmorphism
```
- Nền: Blur backdrop + semi-transparent
- QR: Float card với deep shadow
- Tabs: Frosted glass pills
- Effects: backdrop-filter: blur(12px)
- Phù hợp: Trang có background ảnh/gradient
```

### Template: Classic
```
- Nền: Truyền thống, border rõ ràng
- QR: Box bordered đơn giản
- Layout: Thông tin ngân hàng bên trái, QR bên phải
- Phù hợp: Blog/news truyền thống
```

---

## 6. DATA MODEL

**✅ Confirmed: Không dùng CPT. Tất cả lưu trong `wp_options` — plugin gọn nhẹ.**

### `wp_options` Key: `tsbd_settings`
```php
[
  'title'                  => 'Ủng hộ chúng tôi',
  'description'            => 'Quét mã QR để chuyển khoản',
  'show_amount_suggestions'=> true,
  'amount_suggestions'     => [20000, 50000, 100000, 200000],
  'allow_custom_amount'    => true,
  'allow_note_change'      => true,
  'currency'               => 'VNĐ',
  'default_template'       => 'modern',  // fallback nếu account không chọn
  'primary_color'          => '#2563eb',
  'bg_color'               => '#ffffff',
  'text_color'             => '#1f2937',
  'border_radius'          => 12,
  'max_width'              => '480px',
  'custom_css'             => '',
  'load_google_fonts'      => false,
  'show_footer_credit'     => true,
]
```

### `wp_options` Key: `tsbd_accounts`
```php
[
  [
    'id'             => 'acc_abc123',    // uniqid('acc_')
    'type'           => 'bank',          // 'bank' | 'momo'
    'label'          => 'MB Bank',       // Tên hiển thị trên tab
    'bank_bin'       => '970422',        // VietQR BIN
    'bank_short'     => 'MB',            // shortname từ API
    'account_no'     => '1234567890',
    'account_name'   => 'NGUYEN VAN A',
    'default_amount' => 0,
    'default_note'   => 'Donate Website',
    'vietqr_template'=> 'compact2',      // compact|compact2|qr_only|print
    'box_template'   => 'modern',        // per-account box template
    'attachment_id'  => 1234,            // WP Media attachment ID
    'order'          => 0,
    'active'         => true,
  ],
  [
    'id'             => 'acc_def456',
    'type'           => 'momo',
    'label'          => 'MoMo',
    'phone'          => '0901234567',
    'account_name'   => 'NGUYEN VAN A',
    'default_note'   => 'Donate',
    'box_template'   => 'glass',
    'attachment_id'  => 1235,
    'order'          => 1,
    'active'         => true,
  ],
]
```

### CRUD Pattern
```php
// Read all
$accounts = get_option('tsbd_accounts', []);

// Create
$accounts[] = $new_account;  // $new_account['id'] = uniqid('acc_')
update_option('tsbd_accounts', $accounts);

// Update by id
$accounts = array_map(fn($a) => $a['id'] === $id ? array_merge($a, $data) : $a, $accounts);
update_option('tsbd_accounts', $accounts);

// Delete by id
$accounts = array_values(array_filter($accounts, fn($a) => $a['id'] !== $id));
update_option('tsbd_accounts', $accounts);
```

---

## 7. LOGIC QR REGENERATION

```php
// Trigger: AJAX call 'tsbd_save_account' hoặc 'tsbd_regenerate_qr'
function tsbd_generate_and_save_qr(array &$account): bool {
    if ($account['type'] === 'bank') {
        $url = add_query_arg([
            'amount'      => $account['default_amount'],
            'addInfo'     => rawurlencode($account['default_note']),
            'accountName' => rawurlencode($account['account_name']),
        ], sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png',
            $account['bank_bin'],
            $account['account_no'],
            $account['vietqr_template']
        ));
        $response = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return false;
        $image_data = wp_remote_retrieve_body($response);

    } elseif ($account['type'] === 'momo') {
        // Bundled phpqrcode
        require_once plugin_dir_path(__FILE__) . 'lib/phpqrcode/qrlib.php';
        $deeplink = 'momo://pay?action=payRequest&phone=' . $account['phone'] . '&amount=0&note=' . rawurlencode($account['default_note']);
        ob_start();
        QRcode::png($deeplink, false, QR_ECLEVEL_M, 8, 2);
        $image_data = ob_get_clean();
    }

    // Xoá attachment cũ
    if (!empty($account['attachment_id'])) {
        wp_delete_attachment($account['attachment_id'], true);
    }

    // Lưu vào uploads/ts-donate/
    $upload = wp_upload_bits('tsbd-qr-' . $account['id'] . '.png', null, $image_data);
    if ($upload['error']) return false;

    $attachment_id = wp_insert_attachment([
        'post_mime_type' => 'image/png',
        'post_title'     => 'QR Donate - ' . $account['label'],
        'post_status'    => 'inherit',
    ], $upload['file']);

    $account['attachment_id'] = $attachment_id;
    return true;
}
```

---

## 8. RESPONSIVE & MOBILE

- **Breakpoints:** Mobile (<480px), Tablet (480-768px), Desktop (>768px)
- QR image: `max-width: 100%`, luôn hiển thị đầy đủ trên mobile
- Tabs: Scroll horizontal nếu nhiều ngân hàng (>3)
- Amount buttons: Flexible wrap
- Copy button: Ở cuối, kích thước touch-friendly (min 44px height)
- Font size tối thiểu: 14px trên mobile
- `viewport meta` được WordPress xử lý, plugin không cần thêm

---

## 9. PERFORMANCE

| Item | Giải pháp |
|---|---|
| QR load | Static image từ Media, CDN nếu có |
| CSS | 1 base file + 1 template file (lazy load) |
| JS | Vanilla JS, không jQuery dependency |
| Font | Google Fonts toggle (tắt mặc định) |
| Cache | attachment_id lưu trong wp_options array, không query lại |

---

## 10. COMPATIBILITY

- WordPress: 5.6+
- PHP: 7.4+
- Browsers: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- WooCommerce: Không conflict (no query/hook interference)
- Multisite: Không hỗ trợ trong v1.0

---

## 11. SECURITY

- Nonce cho tất cả AJAX requests
- `sanitize_text_field()`, `absint()` cho tất cả input
- `wp_kses_post()` cho custom CSS
- `current_user_can('manage_options')` cho admin actions
- Attachment ID verify: `wp_attachment_is('image', $id)` trước khi render
- File fetch từ VietQR: verify HTTP 200 + mime type trước khi save

---

## 12. SCOPE v1.0 vs v2.0

### v1.0 (MVP — Phát triển bây giờ)
- [x] Admin: CRUD tài khoản ngân hàng
- [x] Admin: CRUD tài khoản MoMo
- [x] VietQR image fetch + lưu Media
- [x] MoMo QR via deeplink + bundled PHP QR lib
- [x] Shortcode `[ts_donate]` với tab UI
- [x] 4 Templates (modern, minimal, glass, classic)
- [x] Settings page với branding customization
- [x] Copy to clipboard
- [x] Mobile responsive
- [x] Amount suggestions

### v2.0 (Tương lai)
- [ ] Block Editor (Gutenberg block) thay thế shortcode
- [ ] Widget support
- [ ] QR regeneration tự động theo schedule
- [ ] Analytics: đếm lượt click copy/tab
- [ ] Webhook callback khi nhận payment (nếu dùng MoMo Business API)
- [ ] Multiple currency support
- [ ] Dark mode auto detect

---

## 13. TESTING PLAN

### Manual Tests (WordPress local)
1. Plugin activate → không lỗi fatal
2. Thêm tài khoản ngân hàng → QR tự generate → xuất hiện trong Media Library
3. Thêm tài khoản MoMo (auto) → QR generate từ deeplink
4. Thêm shortcode vào page → Box hiển thị đúng template
5. Click tab nhiều ngân hàng → chuyển đúng QR
6. Click copy → toast hiển thị → clipboard có nội dung đúng
7. Resize về mobile (375px) → layout không vỡ
8. Shortcode args override → hiển thị đúng

---

*Document này là nguồn sự thật (source of truth) cho toàn bộ development của plugin `ts_bank_donate` v1.0.*
