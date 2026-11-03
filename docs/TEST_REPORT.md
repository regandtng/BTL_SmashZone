# Báo cáo Kiểm thử & Đảm bảo Chất lượng — SmashZone

> **Trạng thái (cập nhật 2026-11-03):** Unit test **76/76 passed**, E2E Playwright **9/9 passed**.
> Môi trường chạy: XAMPP PHP 8.1.25, MariaDB 10.4.32, Node 24.21.0, Playwright 1.64.1.
> Unit test dùng database riêng `badminton_booking_test`; không dùng database ứng dụng.

## 1. Cách chạy kiểm thử

### 1.1. Chuẩn bị database test

`tests/bootstrap.php` luôn ép `DB_DATABASE=badminton_booking_test`, nhưng database này phải được tạo và import schema trước:

```bash
php tests/setup_test_db.php --fresh
```

Script chỉ thao tác trên database có hậu tố `_test`, tự động từ chối nếu tên database không an toàn. Bỏ `--fresh` để giữ dữ liệu test hiện có.

### 1.2. Unit / integration test (PHP thuần)

```bash
php tests/run_tests.php
```

Hoặc chạy từng file:

```bash
php tests/BookingTest.php
php tests/PricingRuleTest.php
```

Nếu `php` chưa có trong `PATH` (XAMPP trên Windows), dùng đường dẫn đầy đủ, ví dụ:

```powershell
& "D:\xampp\php\php.exe" tests\setup_test_db.php --fresh
& "D:\xampp\php\php.exe" tests\run_tests.php
```

Runner tự kiểm tra:
- Database test có kết nối được không (preflight).
- Mỗi file con có in đúng một dòng `Kết quả: X passed, Y failed` không.
- Exit code, STDERR và lỗi fatal/parse error của từng file con.
- Runner trả exit code khác 0 nếu có test fail hoặc lỗi hạ tầng, không còn trường hợp "0 failed" giả khi file con crash.

#### Chạy chọn lọc

Chạy một hoặc vài file:

```bash
php tests/run_tests.php BookingTest CourtTest
```

Lọc theo tên test (không phân biệt hoa thường, khớp một phần tên):

```powershell
$env:TEST_FILTER = "findAvailable"
php tests/run_tests.php CourtTest
Remove-Item Env:TEST_FILTER
```

```cmd
set TEST_FILTER=findAvailable
php tests\run_tests.php CourtTest
set TEST_FILTER=
```

Các test không khớp bộ lọc hiện `[SKIP]`, vẫn chạy phần setup dữ liệu của file và không tính vào passed/failed. Kết quả cuối có thêm dòng `Tổng skipped` khi đang bật bộ lọc.

### 1.3. E2E Playwright

Yêu cầu: Apache/XAMPP đang chạy app. Mặc định test trỏ tới:

```
http://localhost/BTL_SmashZone/public
```

Chạy:

```bash
cd tests/e2e/playwright
npm install
npx playwright install chromium
npx playwright test
```

Đổi địa chỉ app bằng biến môi trường:

```bash
BASE_URL=http://localhost/BTL_SmashZone npx playwright test
```

Chạy chọn lọc E2E:

```bash
npx playwright test --list                      # xem danh sách kịch bản
npx playwright test -g "đăng nhập"              # lọc theo tên kịch bản
npx playwright test booking.spec.js:209         # chạy đúng kịch bản tại dòng 209
npx playwright test --headed                    # mở trình duyệt để xem
npx playwright test --debug                     # chạy từng bước có debugger
```

Lưu ý: E2E tạo tài khoản/booking mới trong database ứng dụng (mỗi lần chạy dùng email, SĐT và ngày tương lai ngẫu nhiên nên có thể chạy lặp lại). Nên chạy trên database dev/disposable, không chạy trên dữ liệu thật.

## 2. Unit / Integration Test

| File | Lớp / thành phần | Số test | Kết quả |
|------|------------------|---------|---------|
| `tests/BookingTest.php` | `App\Models\Booking` | 21 | 21/21 passed |
| `tests/PricingRuleTest.php` | `App\Models\PricingRule` | 10 | 10/10 passed |
| `tests/BranchTest.php` | `App\Models\Branch` | 6 | 6/6 passed |
| `tests/CourtTest.php` | `App\Models\Court` | 10 | 10/10 passed |
| `tests/UserTest.php` | `App\Models\User` | 8 | 8/8 passed |
| `tests/PaymentTest.php` | `App\Models\Payment` | 6 | 6/6 passed |
| `tests/SessionTest.php` | `Core\Session` (session + CSRF) | 11 | 11/11 passed |
| `tests/RouterTest.php` | `Core\Router` | 4 | 4/4 passed |
| **Tổng** | | **76** | **76 passed, 0 failed** |

### Phạm vi đã kiểm thử

**Booking**
- `isSlotAvailable`: sân trống; trùng hoàn toàn; chồng lấn một phần; sát trước; sát sau; khác sân; khác ngày.
- Pending còn hạn khóa slot; pending hết hạn được dọn và không khóa slot.
- `getBookedSlots` chỉ trả giờ bị khóa bởi booking còn hiệu lực.
- `cleanExpiredPending` không đụng pending còn hạn/confirmed.
- `autoExpireEndedSessions` hủy booking confirmed đã qua giờ chơi.
- `createPending` + `getPendingSecondsRemaining` dùng đồng hồ MySQL, hạn ~3 phút.
- `generateBookingCode`: đúng định dạng `BB-YYYYMMDD-XXXXX`, không trùng.
- `getDashboardStats`: đếm booking, doanh thu confirmed, booking confirmed trong ngày.

**PricingRule**
- Giá weekday off-peak/peak sáng, chiều, tối; weekend Thứ Bảy/Chủ Nhật.
- Rule riêng của chi nhánh ưu tiên hơn rule toàn hệ thống; chi nhánh khác vẫn dùng giá global.
- `getForBranch` trả cả rule global và rule riêng.

**Branch**
- `findActive`, `findByCity`, `getCities`, `getWithCourtsCount`, `search`, `getFeatured`.
- Chỉ trả chi nhánh/sân `active`; đếm đúng số sân active.

**Court**
- `findByBranch` chỉ trả sân active.
- `findAvailable` loại sân bị chiếm bởi confirmed/pending còn hạn, giữ sân có pending hết hạn, đúng biên sát trước/sát sau.
- `getAvailabilityGrid` đánh dấu đúng giờ bị đặt và bỏ qua pending hết hạn.

**User**
- `createUser` hash bcrypt, không lưu plaintext; `authenticate` đúng/sai mật khẩu; `findByEmail`/`findByPhone`; ràng buộc email UNIQUE.

**Payment**
- `createPayment` mặc định `pending`; `findByBooking`; `markAsPaid` cập nhật `status`, `transaction_id`, `paid_at`.

**Session / CSRF**
- get/set/has/remove; flash dùng một lần; CSRF token 64 hex, ổn định trong session; chấp nhận token đúng, từ chối token sai/rỗng; `csrfField` sinh hidden input.

**Router**
- Route tĩnh, route động truyền tham số, dấu `/` cuối, ký tự hợp lệ trong tham số.

### Kết quả chạy thực tế

```
Tổng passed: 76
Tổng failed: 0
Lỗi hạ tầng/runner: 0
Thời gian chạy: ~1.4s
```

## 3. E2E Test (Playwright)

File: `tests/e2e/playwright/booking.spec.js`  
Cấu hình: `tests/e2e/playwright/playwright.config.js`

| # | Kịch bản | Kết quả |
|---|----------|---------|
| 1 | Đăng ký → chọn chi nhánh/sân → chọn giờ → xác nhận → thanh toán tiền mặt → trang thành công → xem lịch sử theo tài khoản và theo SĐT | Passed |
| 2 | Chưa đăng nhập POST `/booking/store` → chuyển về `/login` kèm flash lỗi | Passed |
| 3 | Không cho đặt trùng khung giờ đang chờ thanh toán (slot bị disable + POST trực tiếp bị từ chối) | Passed |
| 4 | Phân quyền trang thanh toán: khách chưa đăng nhập và người dùng khác đều bị chặn | Passed |
| 5 | Đăng ký email đã tồn tại bị từ chối | Passed |
| 6 | Đăng ký số điện thoại không hợp lệ bị từ chối | Passed |
| 7 | Đăng nhập sai mật khẩu bị từ chối, đăng nhập đúng thành công | Passed |
| 8 | API `/api/available-slots`: trả đủ 17 khung giờ 05:00–21:00; thiếu `court_id` trả 400 | Passed |
| 9 | Owner đăng nhập (`admin`/`admin123`) và truy cập dashboard, trang quản lý đặt sân | Passed |

Kết quả chạy thực tế:

```
Running 9 tests using 1 worker
9 passed (24.7s)
```

Selector đã đối chiếu với DOM hiện tại:
- Chi nhánh: `a[href*="/branch/"]`
- Sân: `.court-card a[href*="/booking/"]`
- Khung giờ trống: `.time-slot:not(.disabled)`
- Thanh toán tiền mặt: `label:has(input[name="payment_method"][value="cash"])`

## 4. Lỗi phát hiện qua kiểm thử và đã sửa

| # | Vị trí | Lỗi | Cách sửa |
|---|--------|-----|----------|
| 1 | `app/Models/Booking.php` (lịch sử) | Truyền giờ dạng integer vào cột `TIME`, MySQL hiểu thành `00:00:09`, khiến kiểm tra trùng giờ sai | Format `HH:00:00` trước khi truy vấn |
| 2 | `app/Models/Court.php` — `findAvailable` | Cùng lỗi format giờ integer → không loại được sân đã bị đặt | Format `HH:00:00`; bổ sung điều kiện bỏ qua pending hết hạn |
| 3 | `app/Models/Court.php` — `getAvailabilityGrid` | Pending đã hết hạn vẫn chiếm slot | Thêm điều kiện `expires_at > NOW()` cho pending |
| 4 | `app/Views/bookings/create.php` | `?date=` chỉ dùng để render slot nhưng form luôn gửi ngày hôm nay; đổi ngày không tải lại khung giờ | Dùng `$date` cho input/hidden input/summary; đổi ngày sẽ reload trang với `?date=` |
| 5 | `tests/run_tests.php` | Bỏ qua exit code; file con fatal vẫn tổng kết `0 failed` và exit 0 | Kiểm tra preflight DB, exit code, STDERR, fatal error và đúng một dòng tổng kết |
| 6 | `tests/e2e/playwright/booking.spec.js` | Selector cũ `.branch-card`, `.slot.available`, `select[name="payment_method"]`; API `.or()` dùng sai; fixture cố định | Viết lại theo DOM hiện tại, dùng email/SĐT/ngày ngẫu nhiên, bỏ phụ thuộc booking có sẵn |

## 5. Giới hạn hiện tại

- Chưa cài Xdebug nên **chưa có line coverage %**; con số 76 là số test case, không phải % dòng code.
- E2E chạy trên app + database dev đang cấu hình trong `.env`; chưa tự động trỏ E2E sang `badminton_booking_test`.
- Chưa có CI (GitHub Actions) chạy test tự động.
- Owner mới được E2E kiểm tra luồng đăng nhập/dashboard/quản lý đặt sân; chưa E2E CRUD chi nhánh, bảng giá, doanh thu.
- `Core\Database::query()` hiện `die()` khi query lỗi, nên test không thể kiểm tra một số nhánh lỗi DB ở tầng model.

### Hướng phát triển tiếp
1. Cài Xdebug, xuất coverage HTML/XML và bổ sung cột coverage theo lớp.
2. Tách database E2E riêng (ví dụ chạy PHP built-in server với `DB_DATABASE=badminton_booking_test`).
3. Thêm GitHub Actions: setup PHP + MariaDB, chạy `setup_test_db.php --fresh` và `run_tests.php`.
4. Bổ sung E2E cho CRUD owner (chi nhánh, bảng giá, doanh thu) và các phương thức thanh toán QR/MoMo.

## 6. Bảo mật (tham chiếu)

Chi tiết OWASP Top 10 nằm trong `docs/OWASP_CHECKLIST.md`. Các điểm đã có kiểm thử hoặc xác minh:
- CSRF token: `Core\Session` (unit test) + form xác nhận đặt sân/thanh toán.
- Phân quyền trang thanh toán/đặt sân: E2E test 2 và 4.
- Mật khẩu bcrypt, email UNIQUE: `tests/UserTest.php`.
- Prepared statements: kiểm tra qua toàn bộ model khi chạy integration test.
