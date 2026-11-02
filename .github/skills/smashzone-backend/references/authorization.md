# Phân quyền backend SmashZone

## Khách hàng

- Session khách dùng `user_id`; controller hiện kiểm tra bằng `Core\Session`.
- Mọi booking lấy qua ID URL/form phải được kiểm tra `booking.user_id` khớp `user_id` session trước khi render chi tiết, payment, success hoặc cập nhật.
- Kiểm tra quyền ở từng action, không dựa vào việc link không hiển thị hoặc nút bị ẩn ở view.
- Trường hợp không tồn tại và không thuộc quyền phải có phản hồi theo chuẩn app, không trả dữ liệu booking/payment.

## Owner

- Session owner dùng `owner_id`, `owner_is_super`, `owner_branches`.
- Owner thường chỉ truy cập chi nhánh trong `owner_branches`; Super Admin có phạm vi riêng. Dùng `requireOwner()`/`requireBranchAccess()` hiện có thay vì tự tạo check lệch chuẩn.
- Khi form gửi đồng thời `booking_id` và `branch_id`, không tin rằng branch ID thuộc booking chỉ vì user được quyền vào branch đó. Nếu sửa action, tải booking và xác minh `booking.branch_id` tương ứng trước update.
- Mọi query danh sách owner phải scope theo allowed branch IDs; không lấy danh sách toàn hệ thống rồi chỉ ẩn trên giao diện.

## Tra cứu theo số điện thoại

`BookingController::history()` cho phép khách chưa đăng nhập tra cứu bằng phone qua `Booking::findByPhone()`. Đây là hành vi hiện tại và không tương đương xác thực danh tính mạnh. Không mở rộng dữ liệu trả về (ví dụ email/ghi chú/payment nhạy cảm) khi sửa trang lịch sử; nếu đổi chính sách truy cập lịch sử theo phone, cần xác nhận rõ vì sẽ đổi UX/quyền truy cập.

## CSRF và methods

- Tất cả action POST phải gọi `$this->csrfProtect()`; form POST gửi `Session::csrfField()`.
- GET không được dùng để thay đổi trạng thái hoặc tạo dữ liệu.
- Kiểm thử riêng quyền truy cập khi user hợp lệ nhưng sở hữu resource khác, owner đúng nhưng sai branch, và unauthenticated.
