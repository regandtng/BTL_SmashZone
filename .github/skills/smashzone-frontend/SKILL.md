---
name: smashzone-frontend
description: "Dùng khi xây dựng, cập nhật hoặc sửa lỗi giao diện SmashZone: PHP view, HTML, CSS, JavaScript, responsive, accessibility, biểu mẫu và tương tác trình duyệt trong dự án PHP MVC này."
---

# Giao diện SmashZone

Triển khai thay đổi giao diện trong ứng dụng PHP MVC hiện có, giữ nguyên hợp đồng backend và nhận diện khách/owner. Không biến kiểm tra phía trình duyệt thành nguồn quyết định nghiệp vụ.

## Quy trình

1. **Khoanh vùng:** xác định trang, hành vi cần đổi, hành vi cần giữ, layout, controller/route và dữ liệu view. Kiểm tra trạng thái git, view, CSS/JS liên quan và các consumer dùng chung trước khi sửa.
2. **Lần theo luồng:** kiểm tra render PHP → DOM/field/selector → JavaScript event/state → route/controller/response. Dùng [bản đồ trang và hợp đồng view](./references/ui-patterns.md) làm chỉ dẫn, sau đó xác minh với code thực tế.
3. **Giữ hợp đồng:** bảo toàn URL, method, tên input, giá trị status/phương thức thanh toán, selector đang được JS hoặc test sử dụng và dữ liệu do controller cung cấp. Nếu yêu cầu cần đổi hợp đồng hay nghiệp vụ backend, cập nhật cả hai phía và theo skill backend.
4. **Triển khai đúng phạm vi:** ưu tiên CSS theo component/trang và JavaScript theo cấu trúc hiện có. Trước khi sửa layout, selector global hoặc tài nguyên dùng chung, xác định các màn hình bị ảnh hưởng. Không thêm framework/build pipeline hoặc dependency frontend nếu chưa được yêu cầu.
5. **Xử lý an toàn:** escape dữ liệu động trong HTML bằng `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`; tái sử dụng `BASE_URL`. Không đưa dữ liệu người dùng/DB vào flash HTML thô. Mọi form POST có `<?= \Core\Session::csrfField() ?>` và route POST phải kiểm tra CSRF; sửa cả view lẫn action nếu thiếu và nằm trong phạm vi.
6. **Xử lý trạng thái:** với tương tác thực tế có thể tải, thất bại, không có dữ liệu hoặc thành công, hiển thị trạng thái phù hợp. Lỗi phải giữ dữ liệu người dùng và có cách phục hồi khi hợp lý; chỉ báo thành công sau xác nhận từ server. Ngăn gửi lặp khi phù hợp.
7. **Kiểm chứng:** chạy test trình duyệt/E2E tập trung nếu môi trường sẵn sàng; xác minh luồng chính, lỗi/biên liên quan và các consumer của CSS/JS dùng chung. Kiểm tra responsive ở 375px và viewport khác nếu bố cục bị ảnh hưởng; kiểm tra keyboard/focus và label khi có tương tác/form liên quan. Phân biệt rõ đã xem code, đã chạy test và đã quan sát trình duyệt.
8. **Rà soát:** xem diff, xác nhận không có dữ liệu mock che lỗi, nghiệp vụ chuyển sang frontend hay thay đổi ngoài phạm vi. Nêu command, kết quả và giới hạn kiểm chứng; không khẳng định visual/browser verification nếu chưa thực hiện.

## Quy ước dự án

- View nằm trong `app/Views/`, controller render qua `Core\Controller::render()`, tài nguyên tĩnh nằm trong `public/assets/`.
- Ứng dụng dùng PHP và CSS/JavaScript thuần, không có frontend build pipeline.
- Khu vực khách dùng `app/Views/layouts/main.php` và `public/assets/css/style.css` (giao diện sáng); owner dùng `app/Views/layouts/admin.php` và `public/assets/css/admin.css` (giao diện tối). Không trộn design token giữa hai khu vực.
- Dùng HTML ngữ nghĩa: link cho điều hướng, button cho hành động, label gắn với input, heading theo thứ bậc và thông báo lỗi có thể nhận biết/đọc bằng công nghệ hỗ trợ.
- JavaScript phải xử lý rõ loading, validation/server error, network/unexpected response và success khi các trạng thái đó áp dụng. Không nuốt lỗi, giả response, dùng `setTimeout()` để che race hoặc cho response cũ ghi đè state mới.
- Không thay dữ liệu thật từ controller bằng mock/fallback giả để che lỗi; giữ đúng empty state và phân biệt lỗi tải dữ liệu với kết quả rỗng.
- Nội dung hiển thị cho người dùng nên dùng tiếng Việt thống nhất với giao diện hiện tại.

## Checklist trước khi hoàn tất

- [ ] Đã kiểm tra view, controller/route, CSS/JS và consumer dùng chung liên quan.
- [ ] Đã giữ đúng layout, design token, route, field, selector và giá trị contract.
- [ ] Form POST có CSRF field; action POST có kiểm tra CSRF.
- [ ] Dữ liệu động được escape; không đưa nội dung không tin cậy vào flash HTML.
- [ ] Loading/error/empty/success và phục hồi sau lỗi được xử lý theo luồng thực tế.
- [ ] Không gửi lặp hoặc hiển thị thành công trước xác nhận của server khi tương tác có rủi ro đó.
- [ ] Đã kiểm tra responsive, keyboard/focus và trang dùng chung khi phù hợp.
- [ ] Đã chạy kiểm tra liên quan hoặc ghi rõ phần không thể chạy; không mô tả kiểm tra chưa chạy là đạt.
- [ ] Diff chỉ gồm thay đổi cần thiết, không có mock hoặc nghiệp vụ frontend ngoài yêu cầu.
