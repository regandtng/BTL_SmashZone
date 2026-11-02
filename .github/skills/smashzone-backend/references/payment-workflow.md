# Luồng thanh toán SmashZone

## Luồng hiện tại

```text
GET /payment/{bookingId}
  → PaymentController::show()
  → yêu cầu user đăng nhập
  → tìm booking, kiểm tra booking.user_id khớp session
  → dọn pending hết hạn
  → confirmed thì chuyển trang success
  → không còn pending thì quay về branches
  → render bookings/payment.php cùng số giây còn lại

POST /payment/process
  → PaymentController::process()
  → yêu cầu user đăng nhập + csrfProtect()
  → whitelist method: qr, momo, cash
  → tìm booking và kiểm tra sở hữu
  → dọn pending hết hạn, chỉ tiếp tục nếu vẫn pending
  → tạo payment record
  → cập nhật booking thành confirmed
  → redirect success
```

`payments.status` trong schema: `pending`, `paid`, `refunded`. `bookings.status` là enum riêng. Không suy ra booking đã hết hạn chỉ từ payment pending, hoặc payment đã nhận tiền chỉ từ booking confirmed.

## Giới hạn hiện tại cần nói rõ

- QR/MoMo hiện chưa tích hợp gateway/callback thật. Controller hiện đánh dấu payment `paid` khi khách gửi form chọn QR/MoMo; đây là mô phỏng, không phải bằng chứng ngân hàng đã nhận tiền.
- Với cash, payment hiện `pending` nhưng booking vẫn `confirmed` để giữ sân.
- Payment record và cập nhật booking hiện chưa được thực hiện trong cùng transaction tại model; cần kiểm tra khả năng tạo payment nhưng update booking thất bại.
- Schema hiện không thể hiện idempotency key hoặc unique payment cho mỗi lần submit; submit lặp có thể tạo nhiều payment.

Không tự sửa các hành vi này khi task chỉ sửa UI, timeout hoặc trạng thái hiển thị. Muốn tích hợp gateway, đổi ý nghĩa paid/confirmed, thêm idempotency hay đổi transaction boundary cần yêu cầu rõ; đề xuất phương án, side effect và thay đổi schema/API để người dùng xác nhận trước.

## Bất biến cần kiểm tra khi sửa

- Chỉ chủ booking mới được xem/thanh toán booking.
- Booking đã `cancelled`, `completed` hoặc hết hạn không được xác nhận qua payment POST.
- Việc tạo payment và chuyển booking state phải nhất quán khi có lỗi một nửa; dùng transaction có điều kiện phù hợp ở model.
- Retry/double submit/callback lặp không được tự tạo giao dịch hoặc xác nhận nhiều lần nếu thiết kế hỗ trợ gateway.
- Redirect success không thay thế việc xác minh trạng thái được lưu trong DB.

## Kiểm thử hồi quy

- Payment GET: pending còn hạn, hết hạn, cancelled, confirmed, sai chủ sở hữu.
- Payment POST: method được whitelist, CSRF hợp lệ, booking hết hạn không thể xác nhận, booking của user khác bị từ chối.
- QR/MoMo giả lập và cash giữ đúng hành vi hiện tại nếu task không yêu cầu thay đổi chính sách.
- Lỗi khi tạo payment/update booking không để DB rơi vào trạng thái dở dang.
- Submit lặp và callback chỉ cần test theo cơ chế idempotency thực sự đã triển khai; không giả định hệ thống hiện có cơ chế này.
