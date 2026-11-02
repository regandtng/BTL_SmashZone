# Quy ước kiểm thử SmashZone

## Ánh xạ test theo luồng

- `tests/BookingTest.php`: khả dụng slot, giao nhau thời gian, mã booking, hết hạn và giải phóng slot.
- `tests/PricingRuleTest.php`: giá theo ngày thường/cuối tuần và khung giờ.
- `tests/e2e/playwright/booking.spec.js`: luồng giao diện; hiện có các scenario đăng ký/đặt sân, đặt trùng và đơn hết hạn. Đối chiếu selector với view trước khi tin các scenario này.
- `tests/run_tests.php` chạy từng file unit trong tiến trình con và tổng hợp dòng `Kết quả: X passed, Y failed`.

Đối chiếu requirement với `docs/PRD.md`: chọn sân (Story 2.2), tạo pending (Story 2.3), thanh toán/hết hạn (Story 3.1–3.2), lịch sử (Story 4.1), quyền owner (Story 5.1). Chọn test ở đúng tầng thay vì chỉ thêm E2E cho logic model.

## Database test

`tests/bootstrap.php` phải ép kết nối đến database riêng `badminton_booking_test`; không dùng database ứng dụng trong `.env`. Database test cần được tạo/import schema trước khi chạy. Không xóa database hoặc chạy schema trên môi trường chưa xác minh.

Bootstrap đọc host/user/password từ `.env`, sau đó ghi đè `DB_DATABASE` thành `badminton_booking_test` trước khi nạp model/khởi tạo `Core\Database`. Khi xác minh môi trường, kiểm tra cả host và database name; database có đúng tên nhưng trỏ nhầm máy chủ vẫn không an toàn.

## Unit test

- Dùng helper trong `tests/helpers.php`.
- File test in dòng `Kết quả: X passed, Y failed` để runner tổng hợp được.
- Đăng ký file mới trong danh sách `$tests` ở `tests/run_tests.php`.
- Bao fixture có ghi dữ liệu trong `try/finally` và xóa đúng fixture theo khóa duy nhất.
- Với khoảng giờ `[start, end)`, kiểm tra: `[9,11)` với `[9,11)` (trùng), `[9,11)` với `[10,12)` (chồng một phần), `[7,9)` (sát trước), và `[11,13)` (sát sau).
- Schema có khóa ngoại: fixture booking cần `court_id`/`branch_id` hợp lệ trong DB test đã import seed, hoặc tự tạo fixture cha rồi dọn theo thứ tự ngược trong `finally`.
- Mỗi test file được runner chạy trong process riêng; không dựa vào biến PHP/global giữa các file. Bootstrap phải đặt `DB_DATABASE=badminton_booking_test` trước khi `Core\Database` được khởi tạo.
- `tests/BookingTest.php` hiện có fixture conflict cũ được dọn sau nhánh test thay vì trong `finally`; khi sửa/di chuyển test này, đặt cleanup vào `finally`. Không giả định test hiện có đã tuân thủ đầy đủ quy ước chỉ vì file mới phải tuân thủ.

## Độ tin cậy của runner hiện tại

`tests/run_tests.php` đọc `$exitCode = proc_close($process)` nhưng hiện không dùng giá trị này để cộng lỗi hoặc làm runner thất bại. Runner chỉ parse dòng `Kết quả: ...`; nếu file con lỗi fatal trước dòng đó, có thể tổng kết `0 failed` dù test chưa chạy. Khi thực thi:

1. Đọc output cho từng test file và xác nhận có đúng một dòng kết quả.
2. Xác nhận không có fatal error/STDERR gây test bỏ dở.
3. Không coi exit code 0 của runner là bằng chứng đầy đủ nếu output thiếu test.
4. Chỉ sửa `run_tests.php` để kiểm tra exit code/thiếu summary khi task bao gồm độ tin cậy của test runner; đó là thay đổi shared test infrastructure, cần test runner riêng.

## Playwright: selector đúng với DOM hiện tại

Không dùng các selector cũ `.branch-card`, `.slot.available` hoặc `select[name="payment_method"]`; chúng không khớp view hiện tại.

- Chi nhánh list: view hiện tại không gắn class `.branch-card`; dùng link `a[href*="/branch/"]`.
- Sân: `.court-card a[href*="/booking/"]`.
- Giờ trống: `.time-slot:not(.disabled)`.
- Phương thức tiền mặt: `input[name="payment_method"][value="cash"]` (radio nằm trong thẻ `label`).

Luồng trang hiện tại là `/branches` → `/branch/{id}` → `/booking/{courtId}` → `/booking/confirm` → `/payment/{bookingId}` → `/booking/success/{bookingId}`. Trước khi viết test, xác minh từng bước có trạng thái đăng nhập, seed data, ngày tương lai, CSRF field và slot trống; không dựa vào dữ liệu DB biến đổi hoặc selector không tồn tại.

`booking.spec.js` hiện có các selector không khớp DOM: `.branch-card`, `.slot.available`, và `select[name="payment_method"]`. Scenario trùng giờ dùng `newPage()` trên cùng context (chia sẻ session), nhưng không thiết lập cạnh tranh cùng khung giờ một cách tin cậy; scenario timeout truy cập ID cố định `999999` nên không tự chứng minh việc một booking thực sự hết hạn được dọn. Xem đây là test cần sửa trước khi dùng làm bằng chứng regression, không ghi nhận là coverage đã xác thực.

Scenario đăng ký dùng email/số điện thoại cố định, có thể trùng sau lần chạy trước. Thiết kế fixture duy nhất cho mỗi run hoặc dọn trong DB test; không chạy trên database ứng dụng.

Chỉ ghi kết quả test vào `docs/TEST_REPORT.md` sau khi thực sự chạy và nhận output; ghi ngày/môi trường/command để phân biệt kết quả mới với output lịch sử.

## Phạm vi hồi quy

Khi sửa `Booking`, chọn test theo nơi dùng chung:

- Đổi overlap/availability: test `isSlotAvailable`, `getBookedSlots`, tạo booking khách và tạo booking owner.
- Đổi `expires_at`/cleanup: test giờ MySQL, payment GET/POST, lịch sử, available slots, owner list và `cron.php --clean-pending`.
- Đổi status/payment: test lịch sử hiển thị, slot bị chiếm/được nhả và trang thành công.
- Đổi view/CSS/selector: kiểm tra DOM selector và viewport 375px; nếu stylesheet/layout dùng chung, kiểm tra cả một trang khách và một trang owner phù hợp.
