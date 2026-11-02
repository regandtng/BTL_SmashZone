# Báo cáo Kiểm thử & Đảm bảo Chất lượng — SmashZone

## 1. Unit Test

| File test | Lớp kiểm thử | Phương thức | Kết quả |
|-----------|--------------|-------------|---------|
| `tests/BookingTest.php` | `App\Models\Booking` | `isSlotAvailable`, `generateBookingCode` | 3/3 passed |
| `tests/PricingRuleTest.php` | `App\Models\PricingRule` | `getPrice` | 4/4 passed |

### Cách chạy
```bash
php tests/run_tests.php
```

Hoặc chạy từng file:
```bash
php tests/BookingTest.php
php tests/PricingRuleTest.php
```

### Lưu ý coverage
Dự án hiện chưa có Composer/PHPUnit. Để có báo cáo coverage chính xác, cài **Xdebug** và chạy:
```bash
php -d xdebug.mode=coverage tests/run_tests.php
```
Sau đó phân tích file coverage XML/HTML từ Xdebug.

## 2. E2E Test (Playwright)

Thư mục: `tests/e2e/playwright/`

| File | Kịch bản |
|------|----------|
| `booking.spec.js` | Đăng ký → Đăng nhập → Xem chi nhánh → Chọn sân → Đặt giờ → Nhập thông tin → Thanh toán tiền mặt → Xem trang thành công |
| `booking.spec.js` | Kiểm tra trùng giờ đặt sân (double-check) |
| `booking.spec.js` | Kiểm tra hủy tự động booking pending quá 3 phút |

### Cách chạy
```bash
cd tests/e2e/playwright
npm install
npx playwright test
```

Cấu hình `BASE_URL` qua biến môi trường nếu cần:
```bash
BASE_URL=http://localhost/BTL_SmashZone npx playwright test
```

## 3. Báo cáo Coverage (Kết quả thực)

Chạy toàn bộ unit test qua `tests/run_tests.php`:

| Chỉ số | Giá trị |
|--------|---------|
| Tổng test case | 7 |
| Passed | 7 |
| Failed | 0 |
| Thời gian chạy | ~350ms |

Các method đã kiểm thử:
- `Booking::isSlotAvailable()` — 3 test case
- `Booking::generateBookingCode()` — 2 test case (định dạng + duy nhất)
- `PricingRule::getPrice()` — 4 test case (weekday off-peak, weekday peak, weekend, giá dương)

Để có báo cáo coverage chi tiết từng dòng code, cài **Xdebug** và chạy:
```bash
php -d xdebug.mode=coverage tests/run_tests.php
```
Sau đó phân tích file coverage XML/HTML từ Xdebug.

## 4. Quét Bảo mật (OWASP Top 10)

| STT | Lỗ hổng OWASP | Vị trí trong code | Trạng thái | Hành động khắc phục |
|-----|--------------|-------------------|------------|---------------------|
| 1 | **Broken Access Control** | `OwnerController::bookings`, `updateBooking`, `pricing` | Có xử lý `requireBranchAccess` | Kiểm tra lại tất cả route owner |
| 2 | **Cryptographic Failures** | Mật khẩu bcrypt trong `User::createUser`, `AuthController::login` | Đã hash bcrypt | Kiểm tra `password_verify` đúng chỗ |
| 3 | **Injection** | Tất cả truy vấn qua PDO prepared statements | Đã dùng prepared statements | Kiểm tra các query động trong `Model::findBy` |
| 4 | **Insecure Design** | CSRF token trên form POST | Đã xử lý (partial) | `Session::generateCsrfToken()` đã có sẵn; đã thêm vào form `/booking/confirm` và validate trong `processConfirm()`, `process()`, các controller owner |
| 5 | **Security Misconfiguration** | `.htaccess`, session cookie secure/httponly | Một phần (session đã secure) | Bật `secure` cookie trên HTTPS |
| 6 | **Identification & Authentication Failures** | Session regeneration sau login | Đã xử lý | Thêm `session_regenerate_id(true)` vào `AuthController::login()` cho cả user và owner |
| 7 | **Software & Data Integrity Failures** | Không kiểm tra tính toàn vẹn file | Chưa có | Thêm hash kiểm tra nếu cần |
| 8 | **Logging & Monitoring Failures** | `cron.php` ghi log ra console | Có log cơ bản | Lưu log vào file, theo dõi lỗi |
| 9 | **SSRF** | Không có | Không phát hiện | - |
| 10 | **N+1 Query** | `BranchController::show` gọi `Court::findByBranch` riêng | Có rủi low | Gộp query hoặc eager loading |

### N+1 Query trong BookingController
- `BookingController::create` gọi `Court::findById`, `Branch::findById`, `Booking::getBookedSlots` — 3 query riêng, có thể tối ưu bằng JOIN nếu cần.
- `OwnerController::dashboard` lặp qua mỗi branch để COUNT bookings → N+1. Cần gộp vào 1 query với GROUP BY.

## 5. Kết luận
- Unit test: đã viết cho 2 model quan trọng (Booking, PricingRule), tổng 7 test case,全部 passed.
- E2E: đã viết kịch bản Playwright cho luồng đặt sân chính.
- Bảo mật: đã xác định 10 mục OWASP, xử lý một phần (prepared statements, bcrypt, CSRF, session_regenerate_id), còn cần kiểm tra lại `Model::findBy()` (tên cột động) và N+1 query trong `OwnerController::dashboard`.
- N+1: điểm lưu ý trong `OwnerController::dashboard` (lặp COUNT bookings cho mỗi branch).
