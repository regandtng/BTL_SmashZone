---
name: smashzone-testing
description: "Dùng khi viết, chạy hoặc sửa lỗi kiểm thử SmashZone: unit test PHP, tình huống đặt sân và giá, luồng end-to-end Playwright, kiểm thử hồi quy và báo cáo test."
---

# Kiểm thử SmashZone

Xác minh chức năng được yêu cầu bằng các bài kiểm thử tập trung, có thể chạy lặp lại; chỉ báo cáo những gì đã thực sự thực hiện.

## Quy trình

1. Ghi rõ hành vi cần thay đổi, hành vi ngoài phạm vi và các luồng có thể bị ảnh hưởng do dùng chung model/helper/view. Kiểm tra test hiện có, test liên quan và trạng thái git trước khi thêm/sửa test.
2. Thiết kế test để khóa cả hành vi mới lẫn các hành vi liền kề cần được giữ nguyên; không sửa test hiện có chỉ để làm cho test xanh nếu không chứng minh yêu cầu đã đổi. Mỗi file test phải in `Kết quả: X passed, Y failed`; đăng ký file mới trong `tests/run_tests.php`. Dùng `try/finally` để dọn mọi fixture đã tạo, kể cả khi assertion/query lỗi. Xem [quy ước test và Playwright](./references/testing-patterns.md).
3. Trước khi chạy, đọc `tests/bootstrap.php` và xác minh kết nối trỏ đúng `badminton_booking_test`; bootstrap ép tên DB này, nhưng schema/seed phải được khởi tạo riêng. Không chạy test ghi/xóa dữ liệu nếu DB test chưa sẵn sàng. Không dùng production hay DB chia sẻ.
4. Chạy PHP lint cho file PHP vừa sửa. Khi DB test an toàn, chạy test đích trước; sau đó dùng `php tests/run_tests.php` cho unit suite nếu phạm vi chung đủ rộng.
5. Chỉ chạy Playwright từ `tests/e2e/playwright/` khi app và DB test đã được chuẩn bị, selector đã đối chiếu với DOM, và biết test sẽ tạo/thay đổi dữ liệu nào. Xem [quy ước test và Playwright](./references/testing-patterns.md).
6. Không tin riêng exit code/output của runner: `tests/run_tests.php` hiện thu thập `$exitCode` từ process con nhưng không dùng để quyết định tổng kết; nếu child không in được dòng kết quả, runner có thể bỏ qua lỗi mà vẫn trả exit code thành công. Xác minh từng dòng output và cân nhắc sửa runner riêng khi task là về độ tin cậy của test.
7. Nếu không chạy được do thiếu PHP, Node, dependency, DB hoặc ứng dụng, nêu rõ trở ngại; không báo test chưa chạy là đạt. Báo command, output, phạm vi test và khoảng trống. Test xanh chỉ giảm rủi ro, không chứng minh tuyệt đối không có hồi quy.

## Hướng dẫn thiết kế test

- Kiểm tra cả trường hợp thành công và bị từ chối, đặc biệt là dữ liệu không hợp lệ, ranh giới phân quyền, khung giờ chồng lấn và booking chờ thanh toán đã hết hạn khi phù hợp.
- Với kiểm tra giờ đặt, có đủ bốn biên: trùng hoàn toàn, chồng lấn một phần, khung mới sát ngay trước khung cũ, khung mới sát ngay sau khung cũ. Hai trường hợp sát biên không được coi là chồng lấn nếu nghiệp vụ dùng khoảng nửa mở `[start, end)`.
- Cô lập dữ liệu mẫu và dọn các bản ghi do test tạo ra. Tránh phụ thuộc vào dữ liệu seed có thể thay đổi hoặc giống dữ liệu production.
- Với xử lý đồng thời khi đặt sân hoặc hành vi phụ thuộc thời gian, nêu rõ điều kiện mong đợi thay vì phụ thuộc vào trạng thái tình cờ lúc chạy.
- Đối chiếu scenario Gherkin trong `docs/PRD.md` với unit/E2E test; chỉ cập nhật `docs/TEST_REPORT.md` bằng output thực sự vừa chạy.
- Dùng các helper PHP gọn nhẹ và cấu hình Playwright sẵn có của repository; không thêm framework kiểm thử mới nếu không cần thiết.
- Với test phụ thuộc dữ liệu seed, đăng ký người dùng, trạng thái session hoặc giờ hiện tại, tạo dữ liệu riêng có thể lặp lại và dọn sau test; không dựa vào giả định “đã có booking” trong DB.

## Checklist trước khi hoàn tất

- [ ] Đã xác minh test dùng database riêng, không dùng production/database chia sẻ.
- [ ] Test mới có cả đường thành công và lỗi/biên phù hợp; fixture được dọn bằng `try/finally`.
- [ ] Test file mới đã được thêm vào `tests/run_tests.php` và in đúng dòng kết quả.
- [ ] Các biên giờ đặt và scenario Gherkin liên quan đã được đối chiếu.
- [ ] Selector Playwright đã được đối chiếu với DOM hiện tại; báo cáo chỉ ghi kết quả thực.
- [ ] Đã nêu lệnh chạy, kết quả và test chưa thể chạy (nếu có).
- [ ] Đã kiểm tra các test/hành vi liền kề có nguy cơ bị ảnh hưởng; không đổi assertion ngoài phạm vi yêu cầu.
- [ ] Đã xem output từng tiến trình test và không dựa riêng vào exit code tổng của runner.
