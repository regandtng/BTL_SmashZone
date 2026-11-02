# SmashZone — Hệ thống Đặt Sân Badminton Online

## Giới thiệu
SmashZone là hệ thống đặt sân badminton trực tuyến, cho phép khách hàng tìm chi nhánh, chọn sân, đặt khung giờ và thanh toán online. Hệ thống cũng hỗ trợ giao diện quản trị cho chủ sân (owner) theo dõi đặt sân, quản lý giá và doanh thu.

Dự án được xây dựng bằng **PHP thuần** (không framework), **MySQL**, kiến trúc **MVC đơn giản**.

## Cấu trúc thư mục

```
SmashZone/
├── app/
│   ├── Controllers/    # Điều khiển luồng xử lý (Auth, Booking, Payment, Branch, Home)
│   ├── Models/         # Tương tác cơ sở dữ liệu (User, Booking, Court, Payment, ...)
│   └── Views/          # Giao diện (auth, bookings, branches, errors, home, layouts)
├── config/
│   └── database.php    # Cấu hình kết nối MySQL
├── core/
│   ├── Controller.php  # Lớp cơ sở cho Controller
│   ├── Database.php    # Wrapper PDO
│   ├── Installer.php   # Cài đặt cơ sở dữ liệu tự động
│   ├── Model.php       # Lớp cơ sở cho Model
│   ├── Router.php      # Định tuyến URL
│   └── Session.php     # Quản lý session PHP
├── database/
│   └── schema.sql      # Sơ đồ cơ sở dữ liệu
├── routes/
│   └── web.php         # Các định tuyến ứng dụng
├── public/             # Tài nguyên tĩnh (CSS, JS, ảnh)
├── cron.php            # Script chạy định kỳ (auto-complete, clean pending)
├── .htaccess           # Cấu hình Apache
├── .gitignore
├── PRD.md              # Yêu cầu sản phẩm (User Stories Gherkin)
├── erd.mermaid         # Sơ đồ ERD (Mermaid)
├── sequence.mermaid    # Sơ đồ tuần tự (Mermaid)
├── architecture.mermaid # Sơ đồ kiến trúc tổng thể (Mermaid)
├── GIT_WORKFLOW.md     # Chiến lược nhánh và Pull Request
└── README.md           # Tài liệu này
```

## Yêu cầu hệ thống
- PHP 7.4+ (khuyên dùng PHP 8.x)
- MySQL 5.7+ / MariaDB 10.x
- Apache (XAMPP, WAMP, hoặc Linux)
- Composer (không bắt buộc, hiện tại không sử dụng)

## Cài đặt nhanh

1. **Clone repository**
   ```bash
   git clone <repo-url>
   cd BTL_SmashZone
   ```

2. **Cấu hình môi trường**
   - Tạo file `.env` tại gốc dự án (tham khảo các biến trong `config/database.php` và `cron.php`):
     ```
     DB_HOST=localhost
     DB_PORT=3306
     DB_DATABASE=badminton_booking
     DB_USERNAME=root
     DB_PASSWORD=
     DB_CHARSET=utf8mb4
     ```

3. **Import cơ sở dữ liệu**
   ```bash
   mysql -u root -p badminton_booking < database/schema.sql
   ```
   Hoặc truy cập `/install` nếu có Installer được kích hoạt.

4. **Chạy ứng dụng**
   - Đặt thư mục vào `htdocs` (XAMPP) hoặc căn document root về `public/`
   - Truy cập `http://localhost/BTL_SmashZone/`

## Tài khoản mẫu

| Vai trò | Username / Email | Mật khẩu | Ghi chú |
|---------|------------------|----------|---------|
| Super Admin | `admin` | `admin123` | Bảng `owners`, is_super_admin = TRUE |
| Khách hàng | `khach@gmail.com` | (mã hóa bcrypt) | Bảng `users`, mẫu trong `schema.sql` |

## Tính năng chính

### Khách hàng
- Đăng ký / Đăng nhập / Đăng xuất
- Xem danh sách chi nhánh và sân badminton
- Chọn khung giờ trống, đặt sân
- Thanh toán (QR / MoMo / Tiền mặt)
- Xem lịch sử đặt sân
- Cập nhật hồ sơ cá nhân, đổi mật khẩu

### Chủ sân (Owner)
- Đăng nhập quản trị
- Xem dashboard chi nhánh dưới quyền sở hữu
- Quản lý chi nhánh (thêm, sửa, xóa)
- Quản lý quy giá theo khung giờ
- Xem doanh thu

## Bảo mật
- Mật khẩu được hash bằng `password_hash` (bcrypt)
- Sử dụng prepared statements qua PDO (chống SQL Injection)
- Phân quyền theo `owner_branches` (owner chỉ xem chi nhánh mình quản lý)
- Cần bổ sung CSRF token cho tất cả form POST (đang lên kế hoạch)

## Sơ đồ UML (Mermaid)
Tất cả sơ đồ được lưu dưới dạng mã Mermaid để chỉnh sửa dễ dàng:
- `erd.mermaid` — Sơ đồ thực thể quan hệ cơ sở dữ liệu
- `sequence.mermaid` — Sơ đồ tuần tự luồng đặt sân và thanh toán
- `architecture.mermaid` — Sơ đồ kiến trúc tổng thể hệ thống

Xem trước tại: https://mermaid.live

## Cron Job (chạy nền)
`cron.php` hỗ trợ các tùy chọn:
- `--auto-complete`: Cập nhật booking confirmed khi kết thúc giờ đặt
- `--clean-pending`: Hủy tự động booking pending quá hạn thanh toán (3 phút)
- `--all`: Chạy cả hai lệnh trên

## Đóng góp
1. Tạo nhánh `feature/ten-tinh-nang` từ `main`
2. Commit thay đổi
3. Mở Pull Request và yêu cầu review
4. Sau khi duyệt, merge vào `main`

## Liên hệ
Đội ngũ phát triển SmashZone — BTL năm học.