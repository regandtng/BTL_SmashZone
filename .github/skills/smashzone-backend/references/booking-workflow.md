# Luồng đặt sân SmashZone

## Lần theo luồng

```text
GET /booking/{courtId}
  → BookingController::create()
  → Booking::getBookedSlots()
  → bookings/create.php

POST /booking/store
  → BookingController::store()
  → kiểm tra đăng nhập, input, Booking::isSlotAvailable(), PricingRule
  → lưu lựa chọn vào session key pending_booking
  → redirect /booking/confirm

GET /booking/confirm
  → BookingController::confirm()
  → đọc pending_booking từ session
  → bookings/confirm.php

POST /booking/confirm
  → BookingController::processConfirm()
  → kiểm tra đăng nhập + CSRF + thông tin khách
  → kiểm tra trùng lần cuối trong transaction
  → tạo bookings.status=pending và expires_at=NOW()+3 phút
  → xóa pending_booking khỏi session
  → redirect /payment/{bookingId}
```

Các consumer cần lần theo khi sửa availability/status/expiry:

- `Booking::isSlotAvailable()` — kiểm tra xung đột khi khách/owner tạo booking.
- `Booking::getBookedSlots()` và `BookingController::availableSlots()` — giờ bị đánh dấu bận trên UI/API.
- `Booking::findByUser()`, `findByPhone()`, `findDetailed()` — lịch sử/chi tiết.
- `Booking::getByBranch()`, `getAll()` — các truy vấn model dùng chung; `OwnerController::bookings()` hiện có truy vấn SQL riêng, nên sửa danh sách owner phải kiểm tra trực tiếp controller đó.
- `PaymentController::show()` / `process()` — đọc và chuyển trạng thái booking.
- `cron.php --clean-pending` / `--all` — dọn booking pending quá hạn.
- `OwnerController::storeBooking()` / `updateBooking()` — tạo và cập nhật booking trực tiếp của owner.

Không giả định các consumer này cùng dùng một query hoặc cùng quy tắc hiển thị. Xác minh code từng caller trước khi sửa.

## Trạng thái và bất biến cần giữ

Schema `bookings.status` cho phép `pending`, `confirmed`, `cancelled`, `completed`.

- Đặt qua luồng khách: tạo `pending`, có hạn thanh toán 3 phút.
- Thanh toán hợp lệ trước hạn: chuyển sang `confirmed`.
- Pending hết hạn: chuyển thành `cancelled`; đơn cancelled không chiếm slot.
- Booking confirmed kết thúc giờ chơi: cron hiện chuyển thành `completed`, đặt `completed_at`.
- Owner tạo trực tiếp: hiện tạo `confirmed`, `expires_at = NULL`; không áp dụng timeout 3 phút.
- Trạng thái trong history lấy từ `bookings.status`; `payments.status` là trạng thái riêng.
- Booking confirmed tiếp tục chiếm slot; không giải phóng chỉ vì payment cash có trạng thái pending.

Đây là mô tả code hiện tại, không phải cho phép mọi chuyển trạng thái. Xác minh actor, trạng thái cũ/mới và side effect trước khi thêm chuyển trạng thái. Owner hiện được chọn các trạng thái trong allowlist ở `OwnerController::updateBooking()`; nếu thay đổi quy tắc này, cần kiểm tra authorization, nghiệp vụ và báo cáo owner.

## Thời hạn

- Tạo pending bằng `Booking::createPending()`, thời điểm hết hạn do MySQL tính bằng `DATE_ADD(NOW(), INTERVAL 3 MINUTE)`.
- Countdown lấy giây còn lại qua `Booking::getPendingSecondsRemaining()`.
- Dọn hết hạn qua `Booking::cleanExpiredPending()`, được gọi bởi các luồng model và cron.
- `PaymentController::show()` / `process()` gọi cleanup toàn cục trước khi đọc booking; cleanup có thể đổi các pending hết hạn của nhiều khách, không chỉ booking trên URL.
- Khi sửa thời hạn, kiểm tra cả tạo DB, thanh toán POST, trang payment, availability, history, owner list và cron. Không dùng giờ PHP song song với giờ MySQL cho phép so sánh `TIMESTAMP`.

## Phạm vi test tối thiểu

- Slot trống/bận, booking khách và owner.
- Overlap hoàn toàn, overlap một phần, sát biên trước/sau.
- Pending mới có xấp xỉ 180 giây; pending hết hạn bị huỷ và slot được nhả.
- Thanh toán trước hạn xác nhận; quá hạn không xác nhận.
- Lịch sử thể hiện đúng pending/confirmed/cancelled/completed.
