---
name: smashzone-testing
description: "Dùng khi viết, chạy, sửa lỗi hoặc đánh giá độ tin cậy kiểm thử SmashZone: unit test PHP, booking/giá, database test, Playwright E2E, hồi quy và báo cáo test."
---

# Kiểm thử SmashZone

Thiết kế và chạy kiểm thử tập trung, có thể lặp lại, an toàn với dữ liệu và có kết quả kiểm chứng được. Test xanh chỉ giảm rủi ro; không thay thế review hay chứng minh tuyệt đối không có lỗi.

## Quy trình

1. **Khoanh vùng:** ghi rõ yêu cầu, hành vi ngoài phạm vi, tầng kiểm thử phù hợp và các caller/luồng có thể bị ảnh hưởng. Đọc trạng thái git, test hiện có, implementation và hợp đồng liên quan trước khi sửa test.
2. **Chọn tầng nhỏ nhất phù hợp:** unit/model cho logic nghiệp vụ; integration cho tương tác PDO/transaction/route; Playwright cho DOM và luồng thực tế người dùng. Không thêm E2E để kiểm tra logic đã kiểm chứng tốt ở unit nếu không cần xác minh tích hợp UI.
3. **Thiết kế test có ý nghĩa:** bao gồm kết quả mong đợi, lỗi/từ chối và biên có liên quan; giữ assertion cho hành vi liền kề. Dùng fixture riêng, dữ liệu có định danh duy nhất và cleanup bằng `try/finally`; không dựa vào seed biến đổi, thứ tự test hoặc giờ chạy tình cờ.
4. **Bảo vệ database:** trước bất kỳ test nào có ghi/xóa, đọc `tests/bootstrap.php`, xác minh database là `badminton_booking_test` và xác minh host là môi trường test dự kiến. Bootstrap chỉ ép tên database, không chứng minh host/schema an toàn. Nếu chưa xác minh được môi trường/schema, chỉ chạy lint hoặc test không ghi dữ liệu.
5. **Chạy theo phạm vi:** chạy PHP lint cho file PHP sửa; test đích trước; sau đó chạy `php tests/run_tests.php` khi thay đổi ảnh hưởng rộng. Chỉ chạy Playwright nếu app, DB test, dependencies và fixture đã sẵn sàng; xác minh selector với DOM hiện tại trước khi dùng.
6. **Đọc bằng chứng:** xem output từng test, summary, lỗi PHP/STDERR và exit code tiến trình con. Runner hiện tại có thể báo tổng kết xanh dù một file con lỗi fatal hoặc thiếu summary, nên không dựa riêng vào exit code tổng.
7. **Báo cáo:** nêu command, phạm vi, kết quả thực tế, môi trường/điều kiện và test chưa chạy cùng lý do. Chỉ cập nhật `docs/TEST_REPORT.md` bằng kết quả vừa chạy; không trình bày kết quả lịch sử như lần chạy hiện tại.
8. **Rà soát:** kiểm tra diff và đảm bảo test không làm thay đổi nghiệp vụ, bỏ assertion, ghi/xóa ngoài database test hoặc để lại fixture. Sửa runner riêng chỉ khi yêu cầu bao gồm độ tin cậy của runner.

## Quy ước dự án

- Unit test PHP dùng helper nhẹ trong `tests/helpers.php`; file test cần in đúng một dòng `Kết quả: X passed, Y failed` để runner tổng hợp. Đăng ký file test mới trong `$tests` của `tests/run_tests.php`.
- Mỗi unit test chạy trong process riêng qua runner; không dựa vào global/biến PHP từ file test khác.
- Cấu hình database test ở `tests/bootstrap.php`; `.env` cung cấp thông tin kết nối nhưng bootstrap ép `DB_DATABASE=badminton_booking_test`. Không tự import schema, xóa DB hoặc chạy migration trên môi trường chưa xác minh.
- Playwright nằm trong `tests/e2e/playwright/`; dùng package/config có sẵn. Xác minh route, DOM selector, session, CSRF, seed và tác động dữ liệu trước khi chạy.
- Kiểm tra ngày/giờ dựa trên clock nào (PHP hay MySQL), timezone và khoảng biên rõ ràng. Kiểm tra booking overlap theo quy tắc nửa mở `[start, end)` nếu code/nghiệp vụ hiện hành dùng quy tắc đó.
- Không sửa test chỉ để làm suite xanh; cập nhật expectation khi có yêu cầu nghiệp vụ được xác nhận và kiểm tra được.
- Không cài test framework mới nếu runner hiện có đủ cho thay đổi.

## Checklist trước khi hoàn tất

- [ ] Đã chọn tầng test phù hợp và kiểm tra hành vi mới lẫn hồi quy liên quan.
- [ ] DB ghi/xóa đã xác minh đúng host và `badminton_booking_test`; fixture cô lập và dọn bằng `try/finally`.
- [ ] Test mới có summary đúng định dạng và đã đăng ký runner.
- [ ] Test time, quyền truy cập, booking conflict hoặc expiry bao phủ biên phù hợp với thay đổi.
- [ ] Selector và flow Playwright được đối chiếu với DOM/controller thực tế.
- [ ] Đã xem output từng tiến trình, summary, STDERR và exit code; không tin riêng exit code tổng.
- [ ] Báo đúng command, kết quả, phạm vi và trở ngại; không coi test chưa chạy là đạt.
- [ ] Không sửa assertion/runner/docs ngoài yêu cầu; diff chỉ gồm thay đổi liên quan.

## Tài liệu tham chiếu

- [Quy ước test, fixture, runner và Playwright](./references/testing-patterns.md)
