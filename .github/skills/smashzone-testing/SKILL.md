---
name: smashzone-testing
description: "Dùng khi viết, chạy hoặc sửa lỗi kiểm thử SmashZone: unit test PHP, tình huống đặt sân và giá, luồng end-to-end Playwright, kiểm thử hồi quy và báo cáo test."
---

# Kiểm thử SmashZone

Xác minh chức năng được yêu cầu bằng các bài kiểm thử tập trung, có thể chạy lặp lại; chỉ báo cáo những gì đã thực sự thực hiện.

## Quy trình

1. Kiểm tra phần triển khai, các test hiện có và cấu hình kiểm thử trước khi chọn loại test.
2. Bổ sung kiểm thử hồi quy tập trung cho chức năng bị ảnh hưởng, bao gồm các trường hợp biên và luồng lỗi phù hợp. Với hành vi model, ưu tiên mở rộng `tests/BookingTest.php` hoặc `tests/PricingRuleTest.php`; dùng Playwright cho luồng hiển thị trên trình duyệt.
3. Trước khi chạy test, kiểm tra `tests/bootstrap.php` và môi trường đã cấu hình để xác nhận test sẽ dùng cơ sở dữ liệu và ứng dụng nào. Không chạy test làm thay đổi dữ liệu trên production hoặc cơ sở dữ liệu dùng chung chưa rõ môi trường.
4. Khi các điều kiện cơ sở dữ liệu an toàn và sẵn sàng, chạy bộ PHP unit test từ thư mục gốc bằng `php tests/run_tests.php`.
5. Chỉ chạy test Playwright trong `tests/e2e/playwright/` bằng `npm test` khi ứng dụng và cơ sở dữ liệu test có thể làm mới đã được cấu hình. Bộ test có thể tạo tài khoản và booking.
6. Nếu không chạy được lệnh do thiếu PHP, Node, dependency, cơ sở dữ liệu hoặc ứng dụng, hãy nêu rõ trở ngại. Không báo test chưa chạy là thành công.
7. Báo cáo chính xác các lệnh đã chạy, kết quả đạt/trượt và những phần còn thiếu kiểm thử.

## Hướng dẫn thiết kế test

- Kiểm tra cả trường hợp thành công và bị từ chối, đặc biệt là dữ liệu không hợp lệ, ranh giới phân quyền, khung giờ chồng lấn và booking chờ thanh toán đã hết hạn khi phù hợp.
- Cô lập dữ liệu mẫu và dọn các bản ghi do test tạo ra. Tránh phụ thuộc vào dữ liệu seed có thể thay đổi hoặc giống dữ liệu production.
- Với xử lý đồng thời khi đặt sân hoặc hành vi phụ thuộc thời gian, nêu rõ điều kiện mong đợi thay vì phụ thuộc vào trạng thái tình cờ lúc chạy.
- Dùng các helper PHP gọn nhẹ và cấu hình Playwright sẵn có của repository; không thêm framework kiểm thử mới nếu không cần thiết.
