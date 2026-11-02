# Transaction và đồng thời trong SmashZone

## Luồng đặt chỗ hiện tại

`BookingController::processConfirm()` hiện mở transaction trực tiếp, chạy `SELECT ... FOR UPDATE` trên các booking đang overlap, tạo pending bằng model rồi commit. Đây là implementation hiện hữu và còn lệch quy ước model giữ transaction; không nhân rộng mẫu controller-PDO này trong thay đổi mới.

`SELECT ... FOR UPDATE` chỉ khóa các row truy vấn tìm được. Khi slot còn trống và không có row overlap, câu SELECT có thể không khóa một resource đại diện cho slot; không được khẳng định nó đã loại bỏ mọi race condition giữa hai request. Hành vi gap lock còn tùy InnoDB, index, isolation level và query plan.

## Khi thay đổi logic cùng lúc

Trước khi chọn giải pháp, lập lịch xen kẽ hai request:

```text
Request A: đọc availability → ...
Request B: đọc availability → ...
Request A: tạo booking → ...
Request B: tạo booking → ...
```

Kiểm tra schema/index, isolation level, loại storage engine và resource thực sự bị lock. `isSlotAvailable()` là check trước; check đó không tự bảo đảm uniqueness khi có concurrency.

Các chiến lược như khóa row `courts` ổn định theo court/date, slot inventory, unique constraint theo slot chuẩn hóa, hoặc transaction với locking phù hợp đều có trade-off. Đừng tự thêm bảng/index/schema hoặc thay thời gian chờ lock nếu yêu cầu chỉ là bug nhỏ. Nếu cần chọn/thay thiết kế chống race, giải thích phương án và migration/side effect rồi xin xác nhận.

## Ranh giới transaction và lỗi

- Đặt các thao tác cần commit/rollback nguyên tử cùng nhau trong model/service phù hợp; tránh controller nắm PDO transaction.
- Kiểm tra transaction thực sự bao phủ những ghi nào (booking, payment, audit/side effect); không cho rằng gọi hai model đồng nghĩa một transaction.
- Khi lỗi, rollback nếu transaction còn hoạt động, giữ thông báo lỗi phù hợp, không nuốt exception để giả thành công. Log chi tiết chỉ ở nơi an toàn, không đưa dữ liệu nhạy cảm ra UI.
- Không gửi email/gọi gateway trước commit trừ khi có cơ chế outbox/retry phù hợp; xác định side effect có thể chạy lặp.
- Cân nhắc double-submit và retry: update có điều kiện theo status cũ có thể cần thiết, nhưng phải giữ tương thích với trạng thái nghiệp vụ hiện tại.

## Checklist kiểm chứng

- Test hai request cạnh tranh trên cùng court/date/overlap bằng MySQL/InnoDB thật nếu task khẳng định bảo đảm concurrency.
- Test hai slot không overlap cùng court và slot giống nhau ở court khác.
- Test rollback không để booking/payment một nửa.
- Ghi rõ giới hạn nếu chỉ có unit test tuần tự; không diễn giải nó là test race condition.
