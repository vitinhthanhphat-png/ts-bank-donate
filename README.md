# TS Bank Donate

> WordPress plugin hiển thị hộp donate với QR chuyển khoản ngân hàng (VietQR) và MoMo. Quản lý nhiều tài khoản, tự sinh ảnh QR lưu vào Media Library.

## ✨ Tính năng

- 🏦 **Hỗ trợ VietQR** — Tạo mã QR chuyển khoản ngân hàng tự động từ API VietQR.io
- 📱 **MoMo QR** — Sinh mã QR cho ví MoMo (deeplink thanh toán)
- 👥 **Đa tài khoản** — Quản lý nhiều tài khoản bank/MoMo, hiển thị dạng tab
- 🎨 **4 Template** — Modern, Minimal, Glass, Classic — tuỳ chỉnh theo từng tài khoản
- 🖼️ **4 kiểu VietQR** — Compact, Compact2, QR Only, Print
- 🏷️ **Brand Avatar** — Avatar hiển thị chữ cái đầu ngân hàng với brand color tương ứng (~30 ngân hàng VN)
- 🎯 **Shortcode linh hoạt** — `[ts_donate]` với nhiều tham số tuỳ chỉnh
- ⚙️ **Settings đầy đủ** — Tiêu đề, mô tả, template mặc định, màu sắc, bo góc, max-width, Custom CSS
- 📊 **Admin Preview** — Xem trước widget ngay trong admin (QR Code + Template)
- 🔄 **Cache thông minh** — Danh sách ngân hàng cache 6 giờ
- 📦 **Media Library** — Ảnh QR tự lưu vào WordPress Media

## 📸 Screenshots

*(Thêm ảnh chụp giao diện frontend và admin tại đây)*

## 🚀 Cài đặt

### Upload thủ công
1. Tải folder `ts_bank_donate` vào `/wp-content/plugins/`
2. Vào **WordPress Admin → Plugins** → Kích hoạt **TS Bank Donate**
3. Menu **TS Donate** sẽ xuất hiện trên sidebar

### Từ WordPress Admin
1. **Plugins → Add New → Upload Plugin**
2. Upload file `.zip` → **Install Now → Activate**

### Cập nhật tự động (OTA Update)
Plugin hỗ trợ cập nhật tự động trực tiếp từ GitHub. Khi có phiên bản mới:
1. Sẽ có thông báo update trong **Dashboard → Updates** và trang **Plugins**.
2. Chỉ cần bấm **Update Now** là plugin sẽ tự động tải và cài đặt phiên bản mới nhất.

## 📖 Sử dụng

### Thêm tài khoản
1. Vào **TS Donate → Thêm mới**
2. Chọn loại: **Ngân hàng** hoặc **MoMo**
3. Điền thông tin → Chọn template → Bấm **Cập nhật & Tạo QR**

### Hiển thị trên trang

```
[ts_donate]
```

#### Tham số shortcode

| Tham số | Mô tả | Mặc định |
|---------|--------|----------|
| `ids` | ID các tài khoản cần hiển thị (cách nhau dấu phẩy) | Tất cả active |
| `width` | Max-width của box | `480px` |

#### Ví dụ

```
[ts_donate ids="acc_abc123,acc_def456" width="400px"]
```

### Cài đặt chung
Vào **TS Donate → Cài đặt** để tuỳ chỉnh:
- Tiêu đề & mô tả box
- Template mặc định (Modern/Minimal/Glass/Classic)
- Bảng màu (màu chính, nền, chữ)
- Bo góc, max-width
- Gợi ý mức tiền donate
- Custom CSS
- Bật/tắt footer credit

## 🏗️ Cấu trúc thư mục

```
ts_bank_donate/
├── ts-bank-donate.php          # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── includes/
│   ├── class-tsbd-settings.php # Settings management
│   ├── class-tsbd-account.php  # Account CRUD
│   ├── class-tsbd-qr-generator.php  # VietQR API integration
│   ├── class-tsbd-momo-qr.php  # MoMo QR generation
│   ├── class-tsbd-shortcode.php # [ts_donate] shortcode
│   └── class-tsbd-frontend.php # Frontend asset loading
├── admin/
│   ├── class-tsbd-admin.php    # Admin controller + AJAX
│   ├── views/                  # Admin page templates
│   ├── css/tsbd-admin.css      # Admin styles
│   └── js/tsbd-admin.js        # Admin JavaScript
└── public/
    ├── css/
    │   ├── tsbd-base.css       # Base widget styles
    │   ├── tsbd-modern.css     # Modern template
    │   ├── tsbd-minimal.css    # Minimal template
    │   ├── tsbd-glass.css      # Glassmorphism template
    │   └── tsbd-classic.css    # Classic template
    ├── js/tsbd-public.js       # Frontend JavaScript
    └── templates/
        └── template-modern.php # Widget HTML template
```

## 🔧 Yêu cầu

- WordPress 5.8+
- PHP 7.4+
- Kết nối internet (để gọi API VietQR.io lấy danh sách ngân hàng & tạo QR)

## 📝 Changelog

### v1.3.4
- ✅ Fix lỗi nghiêm trọng: Không lưu được hình ảnh mã QR MoMo khi "Thêm/Sửa Tài khoản" trong Admin.

### v1.3.3
- ✅ Fix lỗi UI vỡ layout góc bo tròn trên trình duyệt Safari (iOS/Mac)
- ✅ Cập nhật tài liệu marketing (chiến dịch giới thiệu plugin)

### v1.3.2
- ✅ Sử dụng `git archive` để đóng gói release ZIP, fix lỗi "Plugin file does not exist" khi upload lên host Linux
- ✅ Tối ưu hoá OTA Updater

### v1.3.1
- ✅ Fix lỗi cập nhật OTA bị mất file do sai tên thư mục sau khi giải nén

### v1.3.0
- ✅ Thêm tính năng **Tự động Cập nhật (OTA Update)** qua GitHub

### v1.2.1 (2026-03-19)
- ✅ Fix admin template preview — rebuild HTML khớp frontend
- ✅ Fix frontend CSS không load trên trang admin edit
- ✅ Template switching hoạt động đúng trong preview
- ✅ Bank brand color avatar (thay thế logo xấu)
- ✅ VietQR picker layout 1 hàng ngang
- ✅ Thêm section tác giả trong Settings
- ✅ Thêm README.md

### v1.0.0
- 🎉 Release đầu tiên
- VietQR bank transfer QR
- MoMo QR support
- 4 templates (Modern, Minimal, Glass, Classic)
- Multi-account management
- Shortcode `[ts_donate]`
- Admin settings page

## 👨‍💻 Tác giả

**Trần Vĩ Thành** — Full-stack WordPress Developer

- 🌐 Website: [techsharevn.com](https://techsharevn.com)
- ✉️ Email: [thanh.web1001@gmail.com](mailto:thanh.web1001@gmail.com)
- 📞 Hotline: [0949 897 293](tel:0949897293)
- 📍 47 Tân Hoá, P.14, Q.6, TP.HCM

Hơn 8 năm kinh nghiệm phát triển web, chuyên WordPress/WooCommerce, thiết kế website doanh nghiệp, xây dựng hệ thống quản lý và plugin tuỳ chỉnh. Đã thực hiện nhiều dự án cho các doanh nghiệp trong và ngoài nước như SR Vietnam, Mind Connector, Global Tax, Q2 Legal, DongRealty, Life360.vn...

**Dịch vụ:** Thiết kế Website · WordPress Plugin · WooCommerce · Quản trị Web · SEO

## 📄 License

GPL-2.0-or-later

---

Made with ❤️ by [TechShare VN](https://techsharevn.com)
