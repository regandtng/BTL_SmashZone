---
name: smashzone-backend
description: "Dùng khi triển khai, mở rộng hoặc sửa lỗi backend SmashZone: controller PHP MVC, model PDO, route, schema MySQL, session, xác thực, đặt sân, thanh toán và cron job."
---

# Backend SmashZone

Thực hiện thay đổi backend có phạm vi rõ ràng, bảo toàn hợp đồng và nghiệp vụ SmashZone, đồng thời kiểm chứng độc lập quyền truy cập, dữ liệu, transaction và tác động phụ.

## Quy trình bắt buộc

1. **Khoanh vùng:** ghi nhận hành vi cần sửa, hành vi ngoài phạm vi phải giữ nguyên và trạng thái git. Không ghi đè hoặc dọn thay đổi có sẵn.
2. **Lập chuỗi phụ thuộc trước khi sửa:** mô tả ngắn theo dạng `route → action → model/SQL → session/DB state → view/API/cron/consumer`. Theo dõi cả đường thành công, lỗi, hết hạn và tác vụ nền liên quan. Dùng [phân tích tác động](./references/change-impact.md).
3. **Đọc đúng tài liệu tham chiếu:** chọn tài liệu theo nghiệp vụ ở bảng bên dưới; chỉ đọc thêm khi luồng cắt qua nhiều miền. Đây là hướng dẫn để lần theo code, không thay thế việc xác minh hành vi code thực tế.

| Công việc | Đọc trước |
|---|---|
| MVC, PDO, thay đổi backend nói chung | [backend-patterns.md](./references/backend-patterns.md) |
| Chọn sân, giữ slot, hết hạn, lịch sử | [booking-workflow.md](./references/booking-workflow.md) |
| Tạo payment, xác nhận, callback/gateway | [payment-workflow.md](./references/payment-workflow.md) |
| Khách hàng, owner, quyền truy cập bản ghi | [authorization.md](./references/authorization.md) |
| Race condition, transaction, lock/schema | [database-transactions.md](./references/database-transactions.md) |
| Chạm nhiều caller, refactor, regression/scope | [change-impact.md](./references/change-impact.md) |

4. **Quyết định phạm vi:** ưu tiên thay đổi nhỏ tại đúng lớp và giữ URL, tên field, session key, status, response cùng nghiệp vụ hiện có. Không tự đổi chính sách thanh toán, chuyển trạng thái, schema, chiến lược khóa hoặc quyền truy cập. Hỏi người dùng trước nếu yêu cầu còn mơ hồ hoặc giải pháp buộc phải thay đổi các hợp đồng/chính sách đó.
5. **Triển khai theo quy ước repo:** PHP tối thiểu 8.0 (Docker PHP 8.2); validate phía server; prepared statements cho giá trị; whitelist cho tên cột động; mọi action POST gọi `csrfProtect()` và mọi form POST có `<?= \Core\Session::csrfField() ?>`. Xem [backend-patterns.md](./references/backend-patterns.md).
6. **Kiểm chứng độc lập sau khi sửa:** lần lượt rà SQL/kiểu dữ liệu; quyền trên từng đường đọc/ghi; transaction và race condition; thay đổi status/side effect; các caller trong chuỗi phụ thuộc. Không kết luận an toàn chỉ vì trang chính hoạt động hoặc test đơn vị xanh.
7. **Kiểm thử:** chạy test đích trước, rồi regression test cho các consumer liên quan. Không chạy test ghi/xóa dữ liệu nếu DB đích không phải database test riêng đã xác minh. Nêu rõ command, output, giới hạn và test chưa chạy.
8. **Cập nhật kiến thức dự án:** chỉ sửa reference/PRD/ERD/OpenAPI khi quy tắc lâu dài, schema, route hoặc hợp đồng API thực sự thay đổi; không biến một fix cục bộ thành tuyên bố nghiệp vụ mới. Cập nhật reference khi code đã xác minh khác hướng dẫn.
9. **Rà diff và báo cáo:** loại bỏ thay đổi ngoài yêu cầu; nêu file/phạm vi, hành vi được giữ, kiểm tra đã chạy và rủi ro còn lại. Không che lỗi DB/xác thực bằng phản hồi thành công.

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

## Checklist trước khi hoàn tất

- [ ] Đã lập và kiểm tra chuỗi phụ thuộc từ route tới mọi consumer/side effect liên quan.
- [ ] Giải pháp không âm thầm đổi chính sách booking/payment, schema, API hoặc quyền truy cập; thay đổi đáng kể đã được xác nhận.
- [ ] Đã kiểm tra quyền sở hữu booking theo session, kể cả với endpoint chỉ đọc.
- [ ] Mọi POST action có `csrfProtect()` và mọi form POST có `csrfField()`.
- [ ] SQL dùng prepared statement cho giá trị và whitelist cho tên cột động.
- [ ] Đã rà riêng SQL, authorization, transaction/concurrency và side effects/status transition.
- [ ] Đã rà soát N+1 và cập nhật ERD/OpenAPI nếu schema/route/hợp đồng thay đổi.
- [ ] Diff chỉ chứa thay đổi cần cho yêu cầu; đã kiểm tra các hành vi liền kề và không có refactor ngoài phạm vi.
- [ ] Đã chạy test phù hợp hoặc nêu rõ lý do không chạy; không báo test chưa chạy là đạt.
