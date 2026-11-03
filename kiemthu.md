# Hướng dẫn kiểm thử dự án SmashZone

Tài liệu này giới thiệu từng file kiểm thử, chức năng nó kiểm tra và cách chạy test trên môi trường XAMPP/Windows.

Kết quả hiện tại: **76/76 unit test đạt**, **9/9 kịch bản E2E đạt**.
Báo cáo chi tiết theo ngày chạy nằm ở [`docs/TEST_REPORT.md`](./docs/TEST_REPORT.md).

---

## 1. Cấu trúc thư mục kiểm thử

```
tests/
├── bootstrap.php          # Nạp .env và ép kết nối sang database test
├── helpers.php            # Hàm test(), test_results(), test_reset(), test_unique()
├── setup_test_db.php      # Tạo database test + import schema/seed
├── run_tests.php          # Chạy toàn bộ unit test và tổng hợp kết quả
├── BookingTest.php
├── PricingRuleTest.php
├── BranchTest.php
├── CourtTest.php
├── UserTest.php
├── PaymentTest.php
├── SessionTest.php
├── RouterTest.php
└── e2e/
    └── playwright/
        ├── booking.spec.js       # 9 kịch bản E2E
        ├── playwright.config.js  # Cấu hình trình duyệt, timeout, trace
        └── package.json
```

---

## 2. Các file kiểm thử làm gì?

### Unit / Integration test (kiểm tra code bên trong)

| File | Số test | Nội dung kiểm tra |
|------|---------|-------------------|
| `tests/BookingTest.php` | 21 | Kiểm tra khung giờ trống: trùng hoàn toàn, chồng lấn một phần, sát trước, sát sau, khác sân, khác ngày. Pending còn hạn giữ slot, pending hết hạn được dọn. Đặt sân pending, thời hạn ~3 phút, mã booking `BB-YYYYMMDD-XXXXX`, hủy booking quá giờ, thống kê dashboard. |
| `tests/PricingRuleTest.php` | 10 | Giá ngày thường/cuối tuần, giờ cao điểm/thấp điểm; rule riêng của chi nhánh được ưu tiên hơn rule toàn hệ thống. |
| `tests/BranchTest.php` | 6 | Tìm chi nhánh đang hoạt động, theo thành phố, danh sách thành phố, tìm kiếm, chi nhánh nổi bật, đếm số sân active. |
| `tests/CourtTest.php` | 10 | Sân theo chi nhánh, tìm sân trống theo ngày/giờ, loại sân đã bị đặt, bỏ qua pending hết hạn, lưới trạng thái khung giờ. |
| `tests/UserTest.php` | 8 | Tạo user, hash mật khẩu (không lưu plaintext), đăng nhập đúng/sai, tìm theo email/SĐT, email không được trùng. |
| `tests/PaymentTest.php` | 6 | Tạo thanh toán, mặc định `pending`, tìm theo booking, đánh dấu đã thanh toán kèm mã giao dịch và thời gian. |
| `tests/SessionTest.php` | 11 | Session get/set/has/remove, flash message dùng một lần, CSRF token và hidden input. |
| `tests/RouterTest.php` | 4 | Route tĩnh, route động có tham số, dấu `/` cuối, ký tự hợp lệ trong tham số. |

Các test này chạy trên database riêng **`badminton_booking_test`**, không đụng database ứng dụng.

### E2E test (giả lập người dùng thật trên trình duyệt)

File `tests/e2e/playwright/booking.spec.js` gồm 9 kịch bản:

1. Đăng ký → chọn chi nhánh/sân → chọn giờ → xác nhận → thanh toán tiền mặt → xem lịch sử.
2. Chưa đăng nhập mà gửi đặt sân thì bị chuyển về trang đăng nhập.
3. Không cho đặt trùng khung giờ đang chờ thanh toán.
4. Chỉ chủ đơn đã đăng nhập mới xem được trang thanh toán.
5. Đăng ký email đã tồn tại bị từ chối.
6. Đăng ký số điện thoại không hợp lệ bị từ chối.
7. Đăng nhập sai mật khẩu bị từ chối, đúng mật khẩu thành công.
8. API `/api/available-slots` trả đủ 17 khung giờ và báo lỗi khi thiếu `court_id`.
9. Owner đăng nhập và truy cập dashboard quản trị.

E2E chạy trên **app + database ứng dụng trong `.env`** (hiện là `badminton_booking`), không phải database test. Test dùng email/SĐT/ngày ngẫu nhiên nên chạy lặp lại được, nhưng dữ liệu tạo ra sẽ còn trong DB ứng dụng.

---

## 3. Chuẩn bị môi trường

1. Mở **XAMPP Control Panel**.
2. Bấm **Start** cho **MySQL**. Riêng E2E bật thêm **Apache**.
3. Mở terminal tại thư mục dự án:

```powershell
cd D:\xampp\htdocs\BTL_SmashZone
```

4. Nếu `php` không có trong `PATH`, dùng đường dẫn đầy đủ `D:\xampp\php\php.exe` như các lệnh bên dưới.

---

## 4. Chạy Unit / Integration test

### Lần đầu hoặc khi muốn reset dữ liệu test

```powershell
D:\xampp\php\php.exe tests\setup_test_db.php --fresh
```

Lệnh này tạo database `badminton_booking_test` và import `database/schema.sql`.
Script tự từ chối nếu tên database không có hậu tố `_test`.

### Chạy toàn bộ

```powershell
D:\xampp\php\php.exe tests\run_tests.php
```

Kết quả mong đợi:

```
Tổng passed: 76
Tổng failed: 0
Lỗi hạ tầng/runner: 0
```

### Chạy một hoặc vài file

```powershell
D:\xampp\php\php.exe tests\run_tests.php BookingTest
D:\xampp\php\php.exe tests\run_tests.php BookingTest CourtTest
```

Tên file không cần `.php`, không phân biệt hoa thường.

### Lọc theo tên test

```powershell
$env:TEST_FILTER = "findAvailable"
D:\xampp\php\php.exe tests\run_tests.php CourtTest
Remove-Item Env:TEST_FILTER
```

Các test không khớp sẽ hiện `[SKIP]`. Dùng cách này để debug nhanh, không dùng thay cho việc chạy toàn bộ suite khi báo cáo.

### Cách đọc kết quả

- `[PASS]` — test đúng.
- `[FAIL]` — test sai; tên ngay sau đó cho biết hành vi bị lỗi.
- `Kết quả: X passed, Y failed` — tổng kết của từng file.
- `Tổng failed: 0` — điều kiện quan trọng nhất để kết luận toàn bộ đạt.
- `echo $LASTEXITCODE` — `0` là đạt, `1` là có test lỗi hoặc lỗi hạ tầng.

---

## 5. Chạy E2E Playwright

```powershell
cd tests\e2e\playwright

# Chỉ cần chạy lần đầu
npm install
npx playwright install chromium

# Chạy toàn bộ
npx playwright test
```

Mặc định test trỏ tới `http://localhost/BTL_SmashZone/public`. Nếu địa chỉ app khác:

```powershell
$env:BASE_URL = "http://localhost/BTL_SmashZone/public"
npx playwright test
Remove-Item Env:BASE_URL
```

### Chạy chọn lọc

```powershell
npx playwright test --list                 # xem danh sách kịch bản
npx playwright test -g "đăng nhập"         # lọc theo tên
npx playwright test booking.spec.js:209    # chạy kịch bản tại dòng 209
npx playwright test --headed               # mở trình duyệt để xem
npx playwright test --debug                # chạy từng bước có debugger
npx playwright test --last-failed          # chạy lại các test fail lần trước
```

### Cách đọc kết quả

- `✓` — kịch bản đạt.
- `✘` — kịch bản lỗi.
- Dòng cuối `9 passed` — tất cả đạt.

Khi fail, Playwright lưu screenshot và trace trong `tests/e2e/playwright/test-results/`:

```powershell
npx playwright show-trace test-results\<thư-mục-test>\trace.zip
```

Xem báo cáo HTML:

```powershell
npx playwright test --reporter=html
npx playwright show-report
```

---

## 6. Dọn dữ liệu E2E (tùy chọn)

Chỉ chạy trên database dev, không chạy trên dữ liệu thật:

```powershell
& "D:\xampp\mysql\bin\mysql.exe" -u root badminton_booking -e "DELETE FROM bookings WHERE customer_name='Khách E2E'; DELETE FROM users WHERE email LIKE 'e2e.%@example.test';"
```

Database test có thể reset sạch bằng:

```powershell
D:\xampp\php\php.exe tests\setup_test_db.php --fresh
```

---

## 7. Lỗi thường gặp

| Hiện tượng | Cách xử lý |
|------------|------------|
| `php` không được nhận diện | Dùng `D:\xampp\php\php.exe` thay cho `php` |
| Không kết nối được database test | Bật MySQL trong XAMPP; chạy `setup_test_db.php --fresh` |
| E2E báo không mở được trang | Bật Apache; kiểm tra `BASE_URL` trỏ đúng `/BTL_SmashZone/public` |
| Playwright thiếu trình duyệt | Chạy `npx playwright install chromium` |
| E2E lỗi do dữ liệu cũ | Chạy lại test (fixture đã unique) hoặc dọn dữ liệu E2E ở mục 6 |
| Muốn xem test fail chi tiết | Mở trace bằng `npx playwright show-trace ...` |

---

## 8. Giới hạn hiện tại

- Chưa cài Xdebug nên chưa có % coverage theo dòng code.
- E2E chưa tách database riêng; đang ghi vào database ứng dụng.
- Chưa có CI tự động chạy test khi push.
- Owner mới được kiểm thử luồng đăng nhập/dashboard/quản lý đặt sân, chưa có E2E CRUD chi nhánh/bảng giá/doanh thu.

---

## 9. Tài liệu liên quan

- [`docs/TEST_REPORT.md`](./docs/TEST_REPORT.md) — báo cáo kết quả, phạm vi và lỗi đã sửa.
- [`.github/skills/smashzone-testing/SKILL.md`](./.github/skills/smashzone-testing/SKILL.md) — quy ước khi viết/sửa test.
- [`docs/PRD.md`](./docs/PRD.md) — yêu cầu và scenario Gherkin.
