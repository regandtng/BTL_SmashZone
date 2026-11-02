---
name: smashzone-backend
description: "Dùng khi triển khai, mở rộng hoặc sửa lỗi backend SmashZone: controller PHP MVC, model PDO, route, schema MySQL, session, xác thực, đặt sân, thanh toán và cron job."
---

# Backend SmashZone

Triển khai thay đổi backend theo kiến trúc PHP MVC của SmashZone, đồng thời giữ nguyên các route, hợp đồng dữ liệu và quy tắc đặt sân hiện có.

## Quy trình

1. Trước khi sửa, lần theo luồng chức năng qua `routes/web.php`, controller, model, view, helper trong `core/` và `database/schema.sql` có liên quan.
2. Làm theo cấu trúc controller/model hiện có và dùng kết nối `Core\Database`/PDO dùng chung. Giữ cú pháp PHP tương thích với phiên bản tối thiểu PHP 7.4 được ghi trong tài liệu dự án.
3. Xác thực dữ liệu yêu cầu ở phía máy chủ. Dùng prepared statement cho các giá trị; tuyệt đối không nối dữ liệu không đáng tin cậy trực tiếp vào câu SQL.
4. Giữ đúng ranh giới phân quyền: phân biệt session khách hàng và chủ sân; giới hạn dữ liệu chủ sân trong các chi nhánh họ quản lý.
5. Bảo vệ các luồng POST làm thay đổi dữ liệu bằng cơ chế CSRF hiện có khi phù hợp. Không làm yếu xác thực hoặc phân quyền chỉ để luồng chạy được.
6. Khi sửa đặt sân hoặc thanh toán, giữ các chuyển trạng thái hợp lệ, quy tắc hết hạn và kiểm tra giờ bị chồng lấn. Dùng transaction và kiểm tra lại tình trạng sân khi có thể xảy ra yêu cầu đồng thời đặt cùng một khung giờ.
7. Nếu cần đổi schema, cập nhật `database/schema.sql` cùng mọi truy vấn và thành phần sử dụng bị ảnh hưởng. Xử lý lỗi rõ ràng; không che giấu lỗi cơ sở dữ liệu hoặc xác thực bằng phản hồi báo thành công.
8. Thêm hoặc cập nhật test phù hợp và chạy bước kiểm tra hẹp nhất liên quan. Tóm tắt thay đổi và kết quả kiểm thử.

## Cấu trúc dự án

- Route: `routes/web.php`
- Controller: `app/Controllers/`
- Model sử dụng PDO: `app/Models/`
- Thành phần MVC, cơ sở dữ liệu và session dùng chung: `core/`
- Schema: `database/schema.sql`
- Tác vụ định kỳ dọn booking: `cron.php`
- Lệnh chạy unit test: `php tests/run_tests.php`

## Yêu cầu an toàn

- Không để lộ thông tin đăng nhập, giá trị session hoặc dữ liệu nhạy cảm của khách hàng trong phản hồi hay log.
- Không chạy thao tác phá hủy dữ liệu trên cơ sở dữ liệu chưa rõ môi trường hoặc cơ sở dữ liệu production.
- Giữ quy tắc nghiệp vụ ở backend; kiểm tra phía client không thể thay thế xác thực phía máy chủ.
