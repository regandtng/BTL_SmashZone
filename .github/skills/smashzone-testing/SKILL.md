---
name: smashzone-testing
description: "Dùng khi viết, chạy hoặc sửa lỗi kiểm thử SmashZone: unit test PHP, tình huống đặt sân và giá, luồng end-to-end Playwright, kiểm thử hồi quy và báo cáo test."
---

# Kiểm thử SmashZone

Xác minh chức năng được yêu cầu bằng các bài kiểm thử tập trung, có thể chạy lặp lại; chỉ báo cáo những gì đã thực sự thực hiện.

## Quy trình

1. Ghi rõ hành vi cần thay đổi, hành vi ngoài phạm vi và các luồng có thể bị ảnh hưởng do dùng chung model/helper/view. Kiểm tra test hiện có, test liên quan và trạng thái git trước khi thêm/sửa test.
2. Thiết kế test để khóa cả hành vi mới lẫn các hành vi liền kề cần được giữ nguyên; không sửa test hiện có chỉ để làm cho test xanh nếu không chứng minh yêu cầu đã đổi. Mỗi file test phải in `Kết quả: X passed, Y failed`; đăng ký file mới trong `tests/run_tests.php`. Dùng `try/finally` để dọn mọi fixture đã tạo, kể cả khi assertion/query lỗi; dùng `test_unique()` cho dữ liệu cần tránh trùng. Xem [quy ước test và Playwright](./references/testing-patterns.md).
3. Khởi tạo database test bằng `php tests/setup_test_db.php --fresh` khi mới setup hoặc cần dữ liệu sạch. Script chỉ thao tác trên DB có hậu tố `_test`; không chạy test ghi/xóa dữ liệu nếu DB test chưa sẵn sàng. Không dùng production hay DB chia sẻ.
4. Chạy PHP lint cho file PHP vừa sửa. Chạy test đích trước (`php tests/run_tests.php CourtTest`, hoặc lọc bằng biến môi trường `TEST_FILTER`), sau đó chạy toàn bộ `php tests/run_tests.php` nếu phạm vi ảnh hưởng đủ rộng.
5. Chỉ chạy Playwright từ `tests/e2e/playwright/` khi Apache đang chạy và selector đã đối chiếu với DOM. E2E hiện dùng app/DB ứng dụng trong `.env` (không phải `badminton_booking_test`), tạo user/booking unique nhưng không tự dọn dữ liệu; chỉ chạy khi chấp nhận dữ liệu phát sinh hoặc trên DB disposable. Xem [quy ước test và Playwright](./references/testing-patterns.md).
6. Runner hiện đã preflight DB test, kiểm tra exit code, STDERR, fatal/parse error và yêu cầu đúng một dòng tổng kết. Vẫn đọc output từng file để xác nhận test thực sự chạy; không coi exit code 0 là bằng chứng duy nhất.
7. Nếu không chạy được do thiếu PHP, Node, dependency, DB hoặc ứng dụng, nêu rõ trở ngại; không báo test chưa chạy là đạt. Báo command, output, phạm vi test và khoảng trống. Test xanh chỉ giảm rủi ro, không chứng minh tuyệt đối không có hồi quy.

## Hướng dẫn thiết kế test

- Kiểm tra cả trường hợp thành công và bị từ chối, đặc biệt là dữ liệu không hợp lệ, ranh giới phân quyền, khung giờ chồng lấn và booking chờ thanh toán đã hết hạn khi phù hợp.
- Với kiểm tra giờ đặt, có đủ bốn biên: trùng hoàn toàn, chồng lấn một phần, khung mới sát ngay trước khung cũ, khung mới sát ngay sau khung cũ. Hai trường hợp sát biên không được coi là chồng lấn nếu nghiệp vụ dùng khoảng nửa mở `[start, end)`.
- Cô lập dữ liệu mẫu và dọn các bản ghi do test tạo ra. Tránh phụ thuộc vào dữ liệu seed có thể thay đổi hoặc giống dữ liệu production.
- Với xử lý đồng thời khi đặt sân hoặc hành vi phụ thuộc thời gian, nêu rõ điều kiện mong đợi thay vì phụ thuộc vào trạng thái tình cờ lúc chạy.
- Đối chiếu scenario Gherkin trong `docs/PRD.md` với unit/E2E test; chỉ cập nhật `docs/TEST_REPORT.md` bằng output thực sự vừa chạy (ngày, môi trường, command, số test).
- Dùng các helper PHP gọn nhẹ và cấu hình Playwright sẵn có của repository; không thêm framework kiểm thử mới nếu không cần thiết.
- Với test phụ thuộc dữ liệu seed, đăng ký người dùng, trạng thái session hoặc giờ hiện tại, tạo dữ liệu riêng có thể lặp lại và dọn sau test; không dựa vào giả định “đã có booking” trong DB. E2E dùng email `e2e.<timestamp>@example.test`, SĐT ngẫu nhiên và ngày tương lai 30–270 ngày.
- Không hard-code booking/slot có sẵn trong E2E; mở sân đầu tiên từ `/branches` và chọn slot trống đầu tiên để test chạy lặp lại được.
- Khi báo kết quả chọn lọc, ghi rõ file/kịch bản đã chạy và số `[SKIP]` (nếu dùng `TEST_FILTER`); không trình bày kết quả lọc như toàn bộ suite.

## Checklist trước khi hoàn tất

- [ ] Đã xác minh unit/integration dùng `badminton_booking_test`, không dùng production/database chia sẻ.
- [ ] Đã chạy `tests/setup_test_db.php --fresh` hoặc xác nhận DB test đã có schema/seed.
- [ ] Test mới có cả đường thành công và lỗi/biên phù hợp; fixture được dọn bằng `try/finally`; dùng `test_unique()` khi cần.
- [ ] Test file mới đã được thêm vào `tests/run_tests.php` và in đúng dòng kết quả.
- [ ] Các biên giờ đặt và scenario Gherkin liên quan đã được đối chiếu.
- [ ] Selector Playwright đã được đối chiếu với DOM hiện tại; E2E dùng fixture unique và đã xác nhận dữ liệu sẽ ghi vào DB ứng dụng.
- [ ] Đã nêu lệnh chạy, kết quả, phạm vi chọn lọc (nếu có) và test chưa thể chạy.
- [ ] Đã kiểm tra các test/hành vi liền kề có nguy cơ bị ảnh hưởng; không đổi assertion ngoài phạm vi yêu cầu.
- [ ] Đã xem output từng tiến trình test và không dựa riêng vào exit code tổng của runner.
