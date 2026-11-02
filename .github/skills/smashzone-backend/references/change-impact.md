# Phân tích tác động thay đổi backend

## Sơ đồ phụ thuộc trước khi sửa

Trước khi chạm code, ghi một sơ đồ ngắn vào phân tích làm việc (không cần tạo file kế hoạch trong repo):

```text
Trigger/route
  → Controller/action + auth/CSRF
  → Model/query + transaction
  → DB/session state transition
  → View/JSON/redirect/flash
  → Other readers/writers: owner, history, cron, API, payment
```

Ghi thêm:

- File và method dự định sửa.
- Hợp đồng phải giữ: URL, HTTP method, request fields, response/status, session keys, schema.
- Hành vi ngoài phạm vi phải giữ.
- Các điểm đọc/ghi cùng model hoặc cột DB.
- Side effects: thanh toán, notification, redirect, cron, cache/session, log.

## Danh sách cần dò theo loại thay đổi

### `Booking` / availability / status

Kiểm tra `BookingController`, `PaymentController`, `OwnerController`, `Booking` model, booking views, `/api/available-slots`, `cron.php`, history/detail/success và các báo cáo doanh thu.

### `Payment` / xác nhận

Kiểm tra payment GET/POST, `Payment` model, `Booking.status`, payment status, expiry, retry/double-submit, lịch sử/chi tiết/thành công và tích hợp gateway nếu có.

### Authorization/session

Kiểm tra customer owner checks ở mọi GET/POST theo ID; owner branch scope; login/logout/session key; view chỉ hiển thị dữ liệu đã scope; tra cứu history theo phone.

### Schema/route/response

Schema: `database/schema.sql`, tất cả query/model/controller, fixture trong test, `docs/erd.mermaid`.

Route/HTTP contract: `routes/web.php`, form/link/fetch callers, `openapi.yaml`, Playwright selectors/expectations.

## Xác minh độc lập sau thay đổi

1. **SQL/data:** placeholders cho giá trị, whitelist identifier, đúng kiểu (`TIME` là `HH:MM:SS`), NULL/status/index/foreign key.
2. **Authorization:** đọc và ghi đều kiểm tra principal-resource-branch; thử ID người khác và nhánh khác.
3. **Atomicity/concurrency:** xác định các ghi cùng transaction, điều kiện rollback, hai request cùng thời điểm và idempotency.
4. **State/side effects:** so sánh trước/sau status booking/payment; kiểm tra slot, history, cron, revenue, redirect/flash và side effects.
5. **Scope/regression:** xem `git diff`; chạy test mục tiêu rồi test consumer dùng chung. Không mở rộng refactor chỉ để dọn code cũ.

## Khi nào phải hỏi trước

Hỏi người dùng trước khi triển khai nếu cần:

- Đổi trạng thái/ý nghĩa “đã thanh toán”, timeout, chính sách giữ slot hoặc hủy booking.
- Thay đổi schema, route/field/response, API công khai hoặc cần migration.
- Chọn một chiến lược khóa/unique constraint có trade-off concurrency.
- Đổi chính sách xem history bằng phone, quyền customer/owner hoặc phạm vi dữ liệu.
- Thực hiện thao tác có thể xóa/sửa dữ liệu thật, hoặc yêu cầu không nêu rõ hành vi kỳ vọng.

Không cần hỏi nếu thay đổi nhỏ, có thể đảo ngược, chỉ trong phạm vi đã yêu cầu và giữ nguyên hợp đồng/nghiệp vụ; hãy triển khai rồi báo phạm vi kiểm chứng.

## Cập nhật tài liệu kiến thức

- Chỉ cập nhật file reference này khi xác minh quy tắc dự án lâu dài thay đổi hoặc phát hiện hướng dẫn đã sai.
- Cập nhật `docs/PRD.md` khi yêu cầu/nghiệp vụ được xác nhận thay đổi; không ghi bug fix đơn lẻ thành yêu cầu mới.
- Cập nhật `docs/erd.mermaid` và `database/schema.sql` đồng bộ khi schema thay đổi.
- Cập nhật `openapi.yaml` khi route hoặc hợp đồng API thay đổi.
- Cập nhật `docs/TEST_REPORT.md` chỉ bằng output test thực tế, kèm môi trường/lệnh phù hợp.
- Không tạo/cập nhật tài liệu chỉ để mô tả một diff cục bộ nếu không giúp duy trì kiến thức dài hạn.
