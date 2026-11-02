---
name: smashzone-frontend
description: "Dùng khi xây dựng, cập nhật hoặc sửa lỗi giao diện SmashZone: PHP view, HTML, CSS, JavaScript, responsive, biểu mẫu và tương tác trình duyệt trong dự án PHP MVC này."
---

# Giao diện SmashZone

Triển khai thay đổi giao diện trong ứng dụng PHP MVC hiện có, không làm lệch thiết kế hoặc phá vỡ luồng render phía máy chủ.

## Quy trình

1. Kiểm tra view cần sửa, layout, CSS/JavaScript liên quan, route và dữ liệu controller truyền vào view.
2. Theo quy ước hiện có trong `app/Views/`, `public/assets/css/` và `public/assets/js/`. Tái sử dụng layout và tài nguyên dùng chung khi phù hợp; tránh thêm thư viện cho những thay đổi giao diện nhỏ.
3. Đảm bảo đầu ra PHP an toàn: escape nội dung do người dùng nhập hoặc lấy từ cơ sở dữ liệu bằng `htmlspecialchars()`. Giữ cách dùng `BASE_URL` cho liên kết và form của ứng dụng.
4. Đảm bảo form tương thích với route controller và tên trường đang được xử lý. Giữ trường/token CSRF nếu handler POST tương ứng yêu cầu.
5. Thiết kế tương tác dễ hiểu, sử dụng được trên điện thoại và máy tính. Khi cần, duy trì trạng thái tải, trống, xác thực và lỗi dễ nhận biết; dùng phần tử ngữ nghĩa và nhãn dễ tiếp cận.
6. Không thay dữ liệu thật từ controller bằng dữ liệu giả hoặc chuyển nghiệp vụ sang JavaScript trên trình duyệt. Nếu chức năng cần sửa backend, hãy cập nhật cả phần backend liên quan.
7. Kiểm tra luồng bị ảnh hưởng bằng bài test hoặc kiểm tra trình duyệt phù hợp, ở phạm vi hẹp nhất có thể. Nêu rõ những kiểm tra không chạy được và lý do.

## Quy ước dự án

- View là các mẫu PHP trong `app/Views/`, được controller render thông qua `Core\Controller::render()`.
- Tài nguyên giao diện dùng chung nằm trong `public/assets/`.
- Ứng dụng dùng PHP và không có quy trình build frontend. Đảm bảo thay đổi tương thích với CSS và JavaScript thuần hiện có.
- Nội dung hiển thị cho người dùng nên dùng tiếng Việt để thống nhất với trang hiện tại.
