# Mẫu triển khai backend SmashZone

## Bản đồ luồng nghiệp vụ hiện có

| Nghiệp vụ | Route/action | Thành phần chính |
|---|---|---|
| Chọn sân, ngày, giờ | `GET /booking/{courtId}` → `BookingController::create()` | `Booking::getBookedSlots()`, `bookings/create.php` |
| Gửi lựa chọn | `POST /booking/store` → `BookingController::store()` | Kiểm tra đăng nhập/khả dụng, lưu `pending_booking` trong session |
| Xác nhận thông tin | `GET/POST /booking/confirm` → `confirm()` / `processConfirm()` | POST kiểm tra CSRF, kiểm tra trùng lần cuối, tạo booking pending |
| Thanh toán | `GET /payment/{bookingId}` và `POST /payment/process` | `PaymentController`, `bookings/payment.php` |
| Lịch sử/chi tiết | `/booking/history`, `/history`, `/booking/detail/{id}` | Booking phải thuộc khách đang đăng nhập |
| Cập nhật slot | `GET /api/available-slots` | JSON từ `BookingController::availableSlots()` |
| Dọn booking hết hạn | `php cron.php --clean-pending` | Chuyển pending hết hạn sang cancelled |

Khi sửa đặt sân, lần theo toàn bộ chuỗi trên thay vì chỉ sửa trang đang hiển thị lỗi. `POST /booking/store` mới lưu lựa chọn vào session; bản ghi DB được tạo sau bước `POST /booking/confirm`.

## Trạng thái và thời hạn booking

- `bookings.status`: `pending` (chờ thanh toán), `confirmed` (đã xác nhận/giữ chỗ), `cancelled` (đã hủy, không giữ chỗ), `completed` (đã hoàn tất).
- `payments.status`: `pending`, `paid`, `refunded`; trạng thái booking và payment là hai trường riêng, không dùng thay thế lẫn nhau.
- Luồng khách: tạo `pending` kèm `expires_at` 3 phút → thanh toán hợp lệ thì `confirmed` → hết hạn thì `cancelled`.
- Hàm kiểm tra slot và giao diện phải loại pending hết hạn khỏi slot bị chiếm. Nguồn thời gian hết hạn hiện tính bằng `NOW()` của MySQL để tránh lệch múi giờ PHP/MySQL.
- Owner tạo booking trực tiếp thì status `confirmed`, `expires_at = NULL`; không áp dụng quy tắc 3 phút cho booking này.
- Dùng `Booking::createPending()` và `Booking::getPendingSecondsRemaining()` cho thời hạn; không tự tính một đồng hồ khác ở controller/view.

## Ranh giới trách nhiệm

- Controller đọc request, xác thực dữ liệu, kiểm tra session/quyền và gọi model.
- Model giữ truy vấn PDO và transaction; không tạo truy vấn SQL trực tiếp trong controller.
- Dùng `Core\Database` thông qua model/cơ chế hiện có. Trong namespace cần tham chiếu PDO, viết `\PDO::FETCH_ASSOC`.
- Một số controller hiện tại còn truy cập `Database` trực tiếp; đây là nợ kỹ thuật cũ, không phải mẫu để mở rộng. Khi chạm vào luồng liên quan, chuyển truy vấn/transaction cần thiết vào model theo phạm vi an toàn.

## POST, CSRF và quyền sở hữu

Trong mỗi action POST, gọi `csrfProtect()` trước khi xử lý dữ liệu thay đổi:

```php
public function update()
{
    $this->csrfProtect();
    $this->auth();

    // Validate input, authorize resource, call model.
}
```

Mọi form POST phải chứa:

```php
<?= \Core\Session::csrfField() ?>
```

Với tài nguyên của khách, luôn so sánh chủ sở hữu trước khi hiển thị hoặc thay đổi:

```php
if ((int)($booking['user_id'] ?? 0) !== (int)\Core\Session::get('user_id')) {
    \Core\Session::flash('error', 'Bạn không có quyền xem đơn này.');
    $this->redirect('/booking/history');
}
```

Không coi ID trong URL/form là bằng chứng quyền truy cập. Owner phải được kiểm tra quyền trên chi nhánh gắn với bản ghi.

## PDO, thời gian và transaction

- Bind giá trị bằng prepared statement; nếu tên cột/bảng cần động, ánh xạ từ whitelist cố định.
- Cột MySQL `TIME` nhận chuỗi định dạng như `'09:00:00'`, không truyền số nguyên `9`.
- Khi bắt lỗi có thể phát sinh từ PHP hoặc PDO, dùng `catch (\Throwable $e)`. Rollback nếu transaction còn hoạt động và không trả phản hồi thành công giả.
- Giữ transaction tại model để controller không cần gọi `Database::getInstance()` hay PDO trực tiếp.
- Dùng JOIN/GROUP BY hoặc query theo lô thay vì query trong vòng lặp để tránh N+1.

## Phạm vi thay đổi và tương thích

- Với bug ở booking, payment, lịch sử hoặc slot, kiểm tra tối thiểu các nơi tiêu thụ chung `Booking`: `BookingController`, `PaymentController`, `OwnerController`, cron và API available slots.
- Giữ nguyên URL, tên field form, session key, status và cấu trúc response hiện có, trừ khi yêu cầu nêu rõ cần đổi. Nếu đổi hợp đồng, cập nhật tất cả caller cùng lúc.
- Không sửa cách tính giá, quyền owner, hoặc các trạng thái booking khác khi chỉ sửa thời hạn thanh toán.
- Sau khi sửa, xem diff để chắc chắn chỉ có file/behavior cần thiết; chạy test đích và hồi quy cho các luồng cùng dùng model/helper vừa chạm.

## Đồng bộ tài liệu

- Đổi bảng/cột/quan hệ: cập nhật `database/schema.sql`, các truy vấn liên quan và `docs/erd.mermaid`.
- Đổi route hoặc hợp đồng endpoint: cập nhật `routes/web.php` và `openapi.yaml`.
