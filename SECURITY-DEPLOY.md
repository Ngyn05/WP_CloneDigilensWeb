# HƯỚNG DẪN BẢO MẬT & QUY TRÌNH DEPLOY / RESTORE

Tài liệu này chứa các quy tắc bảo mật được tích hợp trong theme DigiLens cùng với các bước bắt buộc khi khôi phục (restore) hoặc triển khai (deploy) website nhằm triệt tiêu hoàn toàn mã độc và ngăn chặn nguy cơ bị hack/xâm nhập trở lại.

---

## 1. CÁC LỚP BẢO VỆ TÍCH HỢP TRONG THEME (`inc/security-hardening.php`)

Theme đã kích hoạt tự động các cơ chế bảo vệ sau:

1. **Khóa trình sửa code trong WP Admin (`DISALLOW_FILE_EDIT`)**:
   - Vô hiệu hóa tính năng Theme Editor và Plugin Editor trong Admin Dashboard để ngăn hacker sửa file trực tiếp nếu chiếm được tài khoản admin.
2. **Vô hiệu hóa XML-RPC & Application Passwords**:
   - Chặn đứng mọi request tới `xmlrpc.php`, tắt hoàn toàn XML-RPC pingback và brute-force attacks.
   - Vô hiệu hóa tính năng Application Passwords.
3. **Chặn dò quét thông tin người dùng (Username Enumeration)**:
   - Chặn các truy vấn dò username qua URL (`/?author=1`).
   - Ẩn trang tác giả đối với khách (`author archive` trả về 404).
   - Chặn endpoint `/wp/v2/users` của REST API đối với người dùng chưa đăng nhập hoặc không có quyền `list_users`.
   - Chuẩn hóa thông báo lỗi đăng nhập (không phân biệt sai username hay sai password).
4. **Ẩn phiên bản WordPress & Dấu hiệu nhận diện hệ thống**:
   - Xóa thẻ `generator` trong `<head>` và RSS/Atom feeds.
   - Loại bỏ query string `ver=x.x.x` của WordPress core khỏi scripts/styles.
5. **Thêm các Security HTTP Headers chuẩn**:
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: SAMEORIGIN`
   - `X-XSS-Protection: 1; mode=block`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: geolocation=(), microphone=(), camera=()`
6. **Tự động tạo `.htaccess` chặn thực thi PHP trong `wp-content/uploads`**:
   - Chặn chạy bất kỳ file script nào (`.php`, `.phtml`, `.phar`, `.inc`, `.sh`, v.v.) bên trong thư mục upload hình ảnh/tài liệu.
7. **Cảnh báo Admin khi gặp sự cố phân quyền**:
   - Hiển thị thông báo trên Admin Dashboard nếu hệ thống không thể tự động ghi file `.htaccess` vào thư mục uploads.

---

## 2. NGUYÊN TẮC BẮT BUỘC TRƯỚC KHI RESTORE HOẶC DEPLOY

> [!CAUTION]
> **TUYỆT ĐỐI KHÔNG RESTORE ĐÈ LÊN MÔI TRƯỜNG ĐÃ BỊ NHIỄM MÃ ĐỘC!**

1. **Làm sạch Document Root**:
   - Xóa sạch toàn bộ file/thư mục tại thư mục gốc (Document Root / `public_html`), không để lại bất kỳ file cũ nào (kể cả `.htaccess`, `index.php`, `wp-config.php`).
2. **Cơ sở dữ liệu mới (Fresh Database)**:
   - Xóa sạch các bảng cũ hoặc tạo database mới hoàn toàn trước khi import.
3. **Chọn bản sao lưu (Backup) an toàn**:
   - Chỉ sử dụng bản backup được tạo **trước thời điểm website bị tấn công/xâm nhập**.
4. **Kiểm tra file upload**:
   - Quét sạch toàn bộ thư mục `wp-content/uploads`, đảm bảo **không chứa bất kỳ file PHP** nào hoặc file thực thi bị ngụy trang.

---

## 3. CÁC BƯỚC CẦN THỰC HIỆN SAU KHI RESTORE / DEPLOY

Sau khi hoàn tất quá trình Restore (bằng Prime Mover, Duplicator, WP CLI hoặc thủ công):

### Bước 1: Đổi mật khẩu & Đổi Security Salts trong `wp-config.php`
1. Đổi mật khẩu tất cả các tài khoản Administrator sang mật khẩu mạnh (16+ ký tự ngẫu nhiên).
2. Tạo mới Authentication Keys and Salts tại [WordPress Salt Generator](https://api.wordpress.org/secret-key/1.1/salt/) và thay thế vào `wp-config.php` để buộc đăng xuất tất cả các phiên đăng nhập cũ.

### Bước 2: Cấu hình bổ sung trong `wp-config.php`
Thêm các dòng sau vào file `wp-config.php` (trước dòng `/* That's all, stop editing! */`):

```php
// Khóa cài đặt/cập nhật theme, plugin từ admin (nếu muốn khóa chặt môi trường Production)
define( 'DISALLOW_FILE_MODS', true );

// Khóa chỉnh sửa file mã nguồn
define( 'DISALLOW_FILE_EDIT', true );

// Giới hạn số lượng bản lưu nháp bài viết
define( 'WP_POST_REVISIONS', 5 );

// Buộc sử dụng SSL/HTTPS cho trang Admin
define( 'FORCE_SSL_ADMIN', true );
```

### Bước 3: Kiểm tra phân quyền thư mục & tệp tin (File Permissions)
Đảm bảo quyền chuẩn trên server Linux/Production:
- Tất cả thư mục: `755` (hoặc `750`)
- Tất cả tệp tin: `644` (hoặc `640`)
- File `wp-config.php`: `600` hoặc `400`

### Bước 4: Kiểm tra rule chặn PHP trong thư mục uploads
Đảm bảo file `wp-content/uploads/.htaccess` đã tồn tại với nội dung:

```apache
# BEGIN DIGILENS_UPLOAD_PROTECTION
# Chặn thực thi mọi tệp mã nguồn PHP và script trong thư mục uploads
<FilesMatch "(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|pht|phar|inc|pl|cgi|py|sh|bash)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>
# END DIGILENS_UPLOAD_PROTECTION
```

---

## 4. TỔNG KẾT DANH MỤC FILE LIÊN QUAN

- `inc/security-hardening.php`: Mã nguồn xử lý các lớp bảo vệ bảo mật theme.
- `functions.php`: Nạp tự động module bảo mật.
- `SECURITY-DEPLOY.md`: Hướng dẫn vận hành và quy trình khôi phục an toàn.
