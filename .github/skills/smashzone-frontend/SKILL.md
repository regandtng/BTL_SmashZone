---
name: smashzone-frontend
description: "Dùng khi xây dựng, cập nhật hoặc sửa lỗi giao diện SmashZone: PHP view, HTML, CSS, JavaScript, responsive, biểu mẫu và tương tác trình duyệt trong dự án PHP MVC này."
---

# Giao diện SmashZone

Triển khai thay đổi giao diện trong ứng dụng PHP MVC hiện có, không làm lệch thiết kế hoặc phá vỡ luồng render phía máy chủ.

## Quy trình

1. Xác định trang/tương tác cần đổi và hành vi cần giữ nguyên. Kiểm tra view, layout, CSS/JavaScript, route và dữ liệu controller liên quan; xem trạng thái git để không ghi đè thay đổi có sẵn.
2. Khoanh vùng CSS/JS theo component hoặc trang. Ưu tiên class/phạm vi riêng thay vì sửa selector toàn cục hoặc layout dùng chung; không thay đổi trang khác hay refactor không liên quan. Nếu cần đụng stylesheet/layout toàn cục, kiểm tra các màn hình cùng dùng nó.
3. Dùng [bản đồ trang và hợp đồng view](./references/ui-patterns.md) để tìm đúng view, layout, route, field và selector trước khi sửa. Xác minh lại với code hiện tại vì bảng này mô tả code có thể thay đổi.
4. Đảm bảo đầu ra PHP an toàn: escape nội dung người dùng/cơ sở dữ liệu bằng `htmlspecialchars()`. Layout chính hiện render nội dung flash lỗi thô để hỗ trợ `<br>`; không truyền nội dung người dùng vào flash HTML. Tái sử dụng `BASE_URL`.
5. Tất cả form `method="POST"` phải có `<?= \Core\Session::csrfField() ?>` và khớp tên trường/route controller. Kiểm tra handler gọi `csrfProtect()`; nếu thiếu, bổ sung cả hai phía trong phạm vi cần thiết và kiểm tra CSRF không làm hỏng luồng hiện tại.
6. Mỗi trạng thái **Loading, Error, Empty, Success** chỉ cần triển khai nếu luồng thực tế có thể đi tới trạng thái đó; xác định nguồn dữ liệu và hành động hồi phục cho từng trạng thái. Không thêm trạng thái giả chỉ để đủ checklist.
7. Không thay dữ liệu thật từ controller bằng dữ liệu giả hoặc chuyển nghiệp vụ sang JavaScript trên trình duyệt. Nếu chức năng cần sửa backend, hãy cập nhật cả phần backend liên quan và theo skill backend.
8. Kiểm tra luồng bị ảnh hưởng và ít nhất các màn hình liền kề dùng chung CSS/layout ở viewport phù hợp. Chạy bài test trình duyệt tập trung trước; nếu sửa tài nguyên dùng chung, mở rộng kiểm tra hồi quy. Nêu rõ những kiểm tra không chạy được và lý do.

## Quy ước dự án

- View là các mẫu PHP trong `app/Views/`, được controller render thông qua `Core\Controller::render()`.
- Tài nguyên giao diện dùng chung nằm trong `public/assets/`.
- Ứng dụng dùng PHP và không có quy trình build frontend. Đảm bảo thay đổi tương thích với CSS và JavaScript thuần hiện có.
- Tôn trọng design token trong `public/assets/css/style.css` (giao diện khách sáng) và `public/assets/css/admin.css` (giao diện quản trị tối); đừng trộn hai bộ màu.
- Kiểm tra layout ở viewport rộng 375px; bảo đảm không tràn ngang, form và nút vẫn thao tác được.
- Nội dung hiển thị cho người dùng nên dùng tiếng Việt để thống nhất với trang hiện tại.

## Checklist trước khi hoàn tất

- [ ] Đã giữ đúng layout, stylesheet và design token cho khu vực khách/owner.
- [ ] Mọi form POST có CSRF field, route và tên input khớp controller.
- [ ] Dữ liệu động được escape; không đưa nội dung không tin cậy vào flash HTML.
- [ ] Trạng thái Loading, Error, Empty, Success được xử lý phù hợp với luồng.
- [ ] Đã kiểm tra responsive ở 375px và chạy kiểm tra/test giao diện liên quan.
- [ ] CSS/JS chỉ tác động phạm vi dự kiến; đã kiểm tra màn hình khác nếu dùng chung layout/tài nguyên.
- [ ] Đã xác minh DOM selector và hợp đồng field thực tế; không dựa vào selector trong test cũ chưa được kiểm chứng.
- [ ] Diff không chứa chỉnh sửa giao diện hoặc refactor ngoài yêu cầu.
