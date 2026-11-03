# Quy ước kiểm thử SmashZone

## Ánh xạ test theo luồng

- `tests/BookingTest.php`: khả dụng slot, chồng lấn thời gian, pending còn hạn/hết hạn, mã booking, dọn booking quá giờ, thống kê dashboard.
- `tests/PricingRuleTest.php`: giá ngày thường/cuối tuần, giờ cao điểm/thấp điểm, rule riêng từng chi nhánh.
- `tests/BranchTest.php`: chi nhánh active, theo thành phố, tìm kiếm, đếm sân, chi nhánh nổi bật.
- `tests/CourtTest.php`: sân theo chi nhánh, sân trống theo giờ, lưới khả dụng và pending hết hạn.
- `tests/UserTest.php`: hash mật khẩu, đăng nhập, tìm theo email/SĐT, ràng buộc email UNIQUE.
- `tests/PaymentTest.php`: tạo thanh toán, tìm theo booking, đánh dấu đã thanh toán.
- `tests/SessionTest.php`: session, flash, CSRF token và hidden input.
- `tests/RouterTest.php`: route tĩnh/động, tham số, dấu `/` cuối.
- `tests/e2e/playwright/booking.spec.js`: luồng giao diện chính (đăng ký, đặt sân, thanh toán, trùng giờ, phân quyền, email/SĐT, đăng nhập, API slot, owner).
- `tests/run_tests.php`: chạy từng file unit trong tiến trình con, kiểm tra output/exit code và tổng hợp kết quả.

Đối chiếu requirement với `docs/PRD.md`: chọn sân (Story 2.2), tạo pending (Story 2.3), thanh toán/hết hạn (Story 3.1–3.2), lịch sử (Story 4.1), quyền owner (Story 5.1). Chọn test ở đúng tầng thay vì chỉ thêm E2E cho logic model.

## Database test

`tests/bootstrap.php` ép kết nối đến database riêng `badminton_booking_test`; không dùng database ứng dụng trong `.env`. Tạo/import schema bằng:

```bash
php tests/setup_test_db.php --fresh
```

- Script chỉ chấp nhận database có hậu tố `_test` và tự chặn nếu tên không an toàn.
- `--fresh` xóa và tạo lại database, sau đó import `database/schema.sql` (schema + seed).
- Không có `--fresh`: giữ database hiện có và bỏ qua import nếu đã có bảng.
- Kiểm tra cả host, port và database name; database đúng tên nhưng trỏ nhầm máy chủ vẫn không an toàn.
- Trên Windows/XAMPP, nếu `php` không có trong `PATH`, dùng đường dẫn đầy đủ như `D:\xampp\php\php.exe` cho mọi lệnh PHP bên dưới.

Bootstrap đọc host/user/password từ `.env`, sau đó ghi đè `DB_DATABASE` trước khi nạp model/khởi tạo `Core\Database`. Runner preflight sẽ dừng ngay nếu không kết nối được DB test.

## Unit test

- Dùng helper trong `tests/helpers.php`: `test()`, `test_results()`, `test_reset()`, `test_unique()`.
- File test in dòng `Kết quả: X passed, Y failed` để runner tổng hợp được.
- Đăng ký file mới trong danh sách `$tests` ở `tests/run_tests.php`.
- Bao fixture có ghi dữ liệu trong `try/finally` và xóa đúng fixture theo khóa duy nhất; dùng `test_unique()` cho tên/email/mã để tránh trùng giữa các lần chạy.
- Với khoảng giờ `[start, end)`, kiểm tra: `[9,11)` với `[9,11)` (trùng), `[9,11)` với `[10,12)` (chồng một phần), `[7,9)` (sát trước), và `[11,13)` (sát sau).
- Schema có khóa ngoại: fixture booking cần `court_id`/`branch_id` hợp lệ trong DB test đã import seed, hoặc tự tạo fixture cha rồi dọn theo thứ tự ngược trong `finally`.
- Mỗi file test được runner chạy trong process riêng; không dựa vào biến PHP/global giữa các file.
- Không dùng ngày/giờ thật dễ đổi trạng thái làm fixture; dùng ngày tương lai xa hoặc pending có `expires_at` do test tự đặt.
- `tests/BookingTest.php` đã dọn fixture trong `finally`; giữ nguyên nguyên tắc này khi thêm/sửa test.

## Chạy chọn lọc (unit)

```powershell
# Toàn bộ
php tests/run_tests.php

# Một hoặc vài file (không cần .php, không phân biệt hoa thường)
php tests/run_tests.php CourtTest BookingTest

# Lọc theo tên test, khớp một phần, không phân biệt hoa thường
$env:TEST_FILTER = "findAvailable"
php tests/run_tests.php CourtTest
Remove-Item Env:TEST_FILTER
```

- Test không khớp bộ lọc in `[SKIP]`; phần setup của file vẫn chạy.
- Kết quả cuối có thêm `Tổng skipped` khi bật `TEST_FILTER`.
- Chỉ dùng lọc để debug nhanh; trước khi báo cáo phải chạy lại toàn bộ suite liên quan.

## Độ tin cậy của runner

`tests/run_tests.php` hiện:

- Preflight kết nối DB test trước khi chạy bất kỳ file nào.
- Chạy từng file bằng `proc_open`, đọc STDOUT/STDERR và exit code.
- Yêu cầu đúng một dòng `Kết quả: X passed, Y failed`; thiếu hoặc trùng đều tính lỗi.
- Phát hiện `Fatal error`, `Parse error`, `Uncaught ...` và output STDERR.
- Trả exit code 1 nếu có test fail hoặc lỗi hạ tầng; in danh sách lỗi chi tiết.
- Nhận danh sách file qua `$argv` và hiển thị `TEST_FILTER` đang bật.

Vẫn nên đọc output từng file khi điều tra lỗi; không chỉ nhìn `Tổng failed: 0`.

## Playwright: selector và luồng hiện tại

Chạy từ `tests/e2e/playwright/`:

```bash
npm install
npx playwright install chromium
npx playwright test
```

Cấu hình `playwright.config.js`: chỉ chạy chromium, `workers: 1`, timeout 60s, trace giữ lại khi fail và screenshot khi fail; không bật video.

Mặc định trỏ tới `http://localhost/BTL_SmashZone/public`; đổi bằng `BASE_URL`:

```powershell
$env:BASE_URL = "http://localhost/BTL_SmashZone"
npx playwright test
Remove-Item Env:BASE_URL
```

Selector đúng với DOM hiện tại:

- Chi nhánh: `a[href*="/branch/"]` (không dùng `.branch-card`).
- Sân: `.court-card a[href*="/booking/"]`.
- Giờ trống: `.time-slot:not(.disabled)`.
- Phương thức tiền mặt: `label:has(input[name="payment_method"][value="cash"])` (không dùng `select`).

Luồng: `/branches` → `/branch/{id}` → `/booking/{courtId}?date=...` → `/booking/confirm` → `/payment/{bookingId}` → `/booking/success/{bookingId}`. Lịch sử: `/booking/history` và `/history?phone=...`. Owner: `/owner/login` với seed `admin`/`admin123`, sau đó `/owner/dashboard`, `/owner/bookings?branch_id=1`.

Chạy chọn lọc:

```bash
npx playwright test --list                 # danh sách kịch bản + dòng
npx playwright test -g "đăng nhập"         # lọc theo tên
npx playwright test booking.spec.js:209    # đúng kịch bản tại dòng 209
npx playwright test --headed               # xem trình duyệt chạy
npx playwright test --debug                # debug từng bước
```

## Dữ liệu E2E

- E2E chạy trên **app + database ứng dụng trong `.env`** (hiện là `badminton_booking`), không phải `badminton_booking_test`.
- Spec tạo email `e2e.<timestamp>@example.test`, SĐT ngẫu nhiên, ngày tương lai 30–270 ngày; không hard-code booking/slot.
- Spec **không tự dọn** user/booking đã tạo. Chỉ chạy trên DB dev/disposable, hoặc dọn thủ công sau khi chạy (ví dụ booking có `customer_name = 'Khách E2E'` và user có email `e2e.%@example.test`).
- Không dùng tài khoản owner seed cho môi trường thật.
- Khi fail, trace/screenshot nằm trong `test-results/`; xem bằng `npx playwright show-trace <đường-dẫn-trace.zip>`.
- Báo cáo HTML: `npx playwright test --reporter=html` rồi `npx playwright show-report`.

## Báo cáo kết quả

Chỉ ghi vào `docs/TEST_REPORT.md` sau khi thực sự chạy, kèm ngày, môi trường, command và phạm vi (toàn bộ hay chọn lọc). Không ghi kết quả lịch sử như kết quả mới, không báo test chưa chạy là đạt.

## Phạm vi hồi quy

Khi sửa `Booking`, chọn test theo nơi dùng chung:

- Đổi overlap/availability: test `isSlotAvailable`, `getBookedSlots`, tạo booking khách và tạo booking owner.
- Đổi `expires_at`/cleanup: test giờ MySQL, payment GET/POST, lịch sử, available slots, owner list và `cron.php --clean-pending`.
- Đổi status/payment: test lịch sử hiển thị, slot bị chiếm/được nhả và trang thành công.
- Đổi view/CSS/selector: kiểm tra DOM selector và viewport 375px; nếu stylesheet/layout dùng chung, kiểm tra cả một trang khách và một trang owner phù hợp.
