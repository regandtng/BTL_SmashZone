# SmashZone — Hệ thống Đặt Sân Badminton Online

## Giới thiệu
SmashZone là hệ thống đặt sân badminton trực tuyến, cho phép khách hàng tìm chi nhánh, chọn sân, đặt khung giờ và thanh toán online. Hệ thống cũng hỗ trợ giao diện quản trị cho chủ sân (owner) theo dõi đặt sân, quản lý giá và doanh thu.

Dự án được xây dựng bằng **PHP thuần** (không framework), **MySQL**, kiến trúc **MVC đơn giản**.

## Cấu trúc thư mục

```
SmashZone/
├── app/
│   ├── Controllers/    # Điều khiển luồng xử lý (Auth, Booking, Payment, Branch, Home, Owner)
│   ├── Models/         # Tương tác cơ sở dữ liệu (User, Booking, Court, Payment, ...)
│   └── Views/          # Giao diện (auth, bookings, branches, errors, home, layouts, owner)
├── config/
│   └── database.php    # Cấu hình kết nối MySQL
├── core/
│   ├── Controller.php  # Lớp cơ sở cho Controller (bao gồm csrfProtect)
│   ├── Database.php    # Wrapper PDO (Singleton)
│   ├── Installer.php   # Cài đặt cơ sở dữ liệu tự động
│   ├── Model.php       # Lớp cơ sở cho Model
│   ├── Router.php      # Định tuyến URL
│   └── Session.php     # Quản lý session PHP (csrf_token, flash)
├── database/
│   └── schema.sql      # Sơ đồ cơ sở dữ liệu (có status 'completed', cột completed_at)
├── routes/
│   └── web.php         # Các định tuyến ứng dụng
├── public/             # Tài nguyên tĩnh (CSS, JS, ảnh)
├── tests/              # Unit test (BookingTest, PricingRuleTest) + E2E (Playwright)
├── cron.php            # Script chạy định kỳ (auto-complete, clean pending)
├── .github/
│   └── skills/          # Copilot Skills riêng cho frontend, backend và testing
├── .htaccess           # Cấu hình Apache
├── .gitignore
├── .cursorrules        # Quy tắc bắt buộc AI khi sinh code
├── .env.example        # Mẫu biến môi trường (copy thành .env để dùng)
├── Dockerfile          # Image PHP 8.2 + Apache + pdo_mysql
├── docker-compose.yml  # PHP 8.2 + MySQL 8.0 + phpMyAdmin
├── openapi.yaml        # Tài liệu API (Swagger/OpenAPI 3.0)
├── README.md           # Tài liệu này
└── docs/               # Tất cả tài liệu thiết kế, báo cáo, hướng dẫn demo
    ├── README.md
    ├── PRD.md                  # Yêu cầu sản phẩm (User Stories Gherkin, edge cases)
    ├── erd.mermaid             # Sơ đồ ERD (mã Mermaid, chỉnh sửa được tại mermaid.live)
    ├── sequence.mermaid        # Sơ đồ tuần tự luồng đặt sân và thanh toán
    ├── architecture.mermaid    # Sơ đồ kiến trúc tổng thể hệ thống
    ├── GIT_WORKFLOW.md         # Chiến lược nhánh và Pull Request
    ├── TEST_REPORT.md          # Báo cáo kiểm thử (unit, E2E, OWASP, coverage)
    ├── OWASP_CHECKLIST.md      # Checklist bảo mật OWASP Top 10
    ├── SLIDE_OUTLINE.md        # Nội dung slide bảo vệ
    ├── DEMO_SCRIPT.md          # Kịch bản live demo từng bước
    ├── BACKUP_VIDEO_PLAN.md    # Kế hoạch quay video dự phòng
    └── DEFENSE_QA.md           # Câu hỏi phản biện & câu trả lời chuẩn
```

## Yêu cầu hệ thống
- PHP 8.0+ (Docker sử dụng PHP 8.2)
- MySQL 5.7+ / MariaDB 10.x
- Apache (XAMPP, WAMP, hoặc Linux)
- Docker (tùy chọn, qua `docker-compose.yml`)
- Composer (không bắt buộc, hiện tại không sử dụng)

## Cài đặt nhanh (XAMPP)

1. **Clone repository**
   ```bash
   git clone <repo-url>
   cd BTL_SmashZone
   ```

2. **Cấu hình môi trường**
   - Copy `.env.example` thành `.env` và chỉnh sửa:
     ```
     cp .env.example .env
     ```
   - Mặc định: `DB_HOST=localhost`, `DB_DATABASE=badminton_booking`, `DB_USERNAME=root`, `DB_PASSWORD=` (trống)

3. **Import cơ sở dữ liệu**
   ```bash
   mysql -u root -p badminton_booking < database/schema.sql
   ```

4. **Chạy ứng dụng**
   - Đặt thư mục vào `htdocs` (XAMPP) hoặc căn document root về `public/`
   - Truy cập `http://localhost/BTL_SmashZone/`

## Cài đặt qua Docker (tùy chọn)

```bash
docker-compose up -d
```
- Ứng dụng: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`

## Tài khoản mẫu

| Vai trò | Username / Email | Mật khẩu | Ghi chú |
|---------|------------------|----------|---------|
| Super Admin (Owner) | `admin` | `admin123` | Bảng `owners`, is_super_admin = TRUE |
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
- Đăng nhập quản trị (`/owner/login`)
- Xem dashboard chi nhánh dưới quyền sở hữu
- Quản lý chi nhánh (thêm, sửa, xóa)
- Quản lý quy giá theo khung giờ
- Xem doanh thu

## Bảo mật
- Mật khẩu được hash bằng `password_hash` (bcrypt)
- Sử dụng prepared statements qua PDO (chống SQL Injection)
- Phân quyền theo `owner_branches` (owner chỉ xem chi nhánh mình quản lý)
- `session_regenerate_id(true)` sau đăng nhập
- CSRF token đã được tích hợp vào form xác nhận đặt sân và các controller owner
- `cron.php` chỉ chạy qua CLI, không cho truy cập HTTP

## Sơ đồ UML (Mermaid)
Tất cả sơ đồ nằm trong thư mục `docs/`, lưu dưới dạng mã Mermaid để chỉnh sửa dễ dàng:
- `docs/erd.mermaid` — Sơ đồ thực thể quan hệ cơ sở dữ liệu
- `docs/sequence.mermaid` — Sơ đồ tuần tự luồng đặt sân và thanh toán
- `docs/architecture.mermaid` — Sơ đồ kiến trúc tổng thể hệ thống

Xem trước tại: https://mermaid.live

## Cron Job (chạy nền)
`cron.php` hỗ trợ các tùy chọn:
- `--auto-complete`: Cập nhật booking confirmed khi kết thúc giờ đặt (đặt status = 'completed', ghi completed_at)
- `--clean-pending`: Hủy tự động booking pending quá hạn thanh toán (3 phút)
- `--all`: Chạy cả hai lệnh trên

Ví dụ chạy trên Windows Task Scheduler hoặc Linux cron:
```bash
php cron.php --all
```

## Unit Test
Các bài test dùng database riêng `badminton_booking_test`, không dùng database ứng dụng. Tạo database test và import schema vào database này trước khi chạy:

```bash
mysql -u root -p -e "CREATE DATABASE badminton_booking_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p badminton_booking_test < database/schema.sql
```

`tests/bootstrap.php` lấy host/tài khoản từ `.env` nhưng ép tên database sang `badminton_booking_test`. Không cấu hình database test trỏ đến production hoặc database dùng chung.

```bash
php tests/run_tests.php
```
Các bài test có thể tạo và xóa dữ liệu mẫu trong database test.

## Đóng góp
1. Tạo nhánh `feature/ten-tinh-nang` từ `main`
2. Commit thay đổi
3. Mở Pull Request và yêu cầu review
4. Sau khi duyệt, merge vào `main`

## Liên hệ
Đội ngũ phát triển SmashZone — BTL năm học.
