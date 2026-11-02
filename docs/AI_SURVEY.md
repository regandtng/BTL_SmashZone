# Tài liệu Khảo Sát Yêu Cầu Bằng AI — SmashZone

Tài liệu này ghi lại cách dùng **AI Agent** đóng vai trò khác nhau để rút ra yêu cầu hệ thống, phục vụ phần **Đề tài và Phạm vi Nghiên cứu** của thầy cô chấm bảo vệ.

## 1. Quy trình khảo sát tổng quát

Chúng tôi không chỉ đặt yêu cầu thô mà đã sử dụng nhiều AI Agent đóng vai trò khác nhau để khai thác ý tưởng, từ đó rút ra đầu vào/đầu ra và edge cases của hệ thống:

```
Ý tưởng thô (đặt sân badminton online)
   │
   ▼
AI đóng vai Senior BA  ──► Epic, Feature, User Stories (Gherkin)
   │
   ▼
AI đóng vai Người dùng hệ thống ──► Luồng thao tác, điểm đau, dễ dùng
   │
   ▼
AI đóng vai Edge-Case Finder ──► Rủi ro bảo mật, hiệu năng, nghiệp vụ (trùng giờ, hết hạn, hủy)
   │
   ▼
Người thật xác nhận (chủ sân, khách hàng)
   │
   ▼
PRD.md (đầu vào/đầu ra rõ ràng, không bỏ sót biên)
```

## 2. Các Prompt đã dùng

### Prompt 1 — AI đóng vai Senior Product Manager (PM)
```
Bạn là Senior Product Manager chuyên về hệ thống đặt chỗ theo lịch (booking system).
Tôi có ý tưởng xây dựng hệ thống đặt sân badminton online tên "SmashZone".
Hãy phân rã ý tưởng này thành:
1. Epic (các nhóm chức năng lớn)
2. Feature (tính năng chi tiết trong mỗi Epic)
3. User Stories (dạng As a ..., I want to ..., So that ...)

Hãy tập trung vào các tính năng cốt lõi: tìm chi nhánh, chọn sân, đặt khung giờ, thanh toán, lịch sử, quản trị owner.
```

### Prompt 2 — AI đóng vai Business Analyst (BA) viết Gherkin
```
Bạn là Business Analyst chuyên viết tài liệu yêu cầu phần mềm.
Dựa trên User Stories ở trên, hãy viết các kịch bản kiểm thử theo định dạng Gherkin (Given - When - Then) cho từng tính năng chính của SmashZone:
- Đăng ký / Đăng nhập
- Tìm chi nhánh, chọn sân
- Đặt sân (tạo booking pending)
- Thanh toán (QR / MoMo / Tiền mặt)
- Hủy tự động khi hết hạn thanh toán (3 phút)
- Quản trị owner (chi nhánh, giá, doanh thu)

Mỗi User Story cần có ít nhất 1 Scenario thành công và 1 Scenario lỗi/edge case.
```

### Prompt 3 — AI đóng vai Edge-Case Finder (phản biện)
```
Bạn là chuyên gia kiểm thử và phân tích rủi ro phần mềm.
Hãy xem xét hệ thống đặt sân badminton SmashZone và tìm ra:
1. Các edge case về nghiệp vụ (ví dụ: hai người đặt cùng sân cùng giờ, booking ngày quá khứ, khung giờ ngoài giờ mở cửa)
2. Các rủi ro bảo mật (SQL Injection, CSRF, phân quyền owner/truy cập sai chi nhánh)
3. Các rủi ro hiệu năng (N+1 query, cron job quá tải)

Hãy đưa ra cách xử lý đề xuất cho mỗi trường hợp.
```

### Prompt 4 — AI đóng vai Software Architect (thiết kế)
```
Bạn là Software Architect. Dựa trên PRD.md của SmashZone, hãy thiết kế:
1. Sơ đồ kiến trúc tổng thể (MVC, Client - Server - DB)
2. Sơ đồ ERD (các bảng: users, owners, branches, courts, bookings, payments, pricing_rules, owner_branches)
3. Sơ đồ tuần tu (Sequence) cho luồng đặt sân và thanh toán
4. Chiến lược Git Workflow (branching, Pull Request review)

Hãy xuất mã Mermaid.js để có thể chỉnh sửa sau này.
```

## 3. Kết quả đã đạt được

| Bước | AI Role | Output |
|------|---------|--------|
| Phân rã ý tưởng | Senior PM | Epic, Feature, User Stories |
| Viết Gherkin | BA | `docs/PRD.md` (User Stories Given-When-Then + edge cases EC-1 đến EC-8) |
| Tìm rủi ro | Edge-Case Finder | Bảng edge cases trong `docs/PRD.md` |
| Thiết kế kỹ thuật | Software Architect | `docs/erd.mermaid`, `docs/sequence.mermaid`, `docs/architecture.mermaid` |

### Ví dụ edge case từ AI đã đưa vào PRD
- **EC-1 Trùng giờ đặt sân:** Hai người chọn cùng sân, cùng giờ gần đồng thời → double-check availability tại `processConfirm` trước khi insert. (Đã triển khai trong `BookingController::processConfirm`)
- **EC-2 Hết hạn thanh toán (3 phút):** Cron `--clean-pending` chạy định kỳ; controller kiểm tra `expires_at` trước khi hiển thị trang thanh toán. (Đã triển khai trong `PaymentController::show`)
- **EC-3 Hủy sân trước khi thanh toán:** Cho phép hủy booking pending, không cho hủy booking confirmed đã thanh toán trừ khi owner can thiệp. (Một phần: chỉ owner có thể cập nhật trạng thái, khách chưa có chức năng tự hủy đơn)
- **EC-4 Booking vào quá khứ:** Controller chặn ngày `< today`; điều chỉnh về hôm nay nếu người dùng sửa URL. (Đã triển khai trong `BookingController::create`)
- **EC-7 CSRF / Session hijacking:** Thêm CSRF token vào tất cả form POST; session regeneration sau đăng nhập. (Một phần: đã thêm vào form xác nhận đặt sân, thanh toán, và các controller owner; form login/register/profile chưa có token)

### Quyết định của nhóm sau khi khảo sát AI
| Ý kiến AI ban đầu | Quyết định của nhóm | Lý do |
|-------------------|---------------------|-------|
| AI kiến trúc đề xuất dùng React + Tailwind/Shadcn | Giữ PHP thuần + MVC | Dự án nhỏ, deploy dễ trên shared hosting/XAMPP, không cần build tool |
| AI bảo mật đề xuất giới hạn 5 lần đăng nhập sai | Đánh dấu "ngoài phạm vi v1" | Không có thời gian triển khai rate limiter, ghi vào roadmap |
| AI DEV đề xuất dùng Composer + Laravel | Giữ PHP thuần | Tránh phụ thuộc externo, kiểm soát toàn bộ mã nguồn (tiêu chí chấm) |
| AI QA đề xuất xóa booking ngay khi hết hạn | Giữ status 'pending' + cron dọn sau 3 phút | Tránh mất đơn nếu khách đang trong quá trình thanh toán |


## 4. Làm sao khác với khảo sát truyền thống

| Tiêu chí | Khảo sát truyền thống | Khảo sát dùng AI (dự án này) |
|----------|----------------------|------------------------------|
| Người hỏi | Người thật phỏng vấn | AI đóng vai chuyên gia + người dùng |
| Số lượng câu hỏi | Hạn chế bởi thời gian người phỏng vấn | Có thể đặt hàng trăm câu hỏi phản biện nhanh |
| Edge case | Nhiều khi bỏ sót do chủ quan | AI được nhắc nhở tìm edge case hệ thống |
| Tốc độ | Chậm (lịch phỏng vấn, tổng hợp) | Nhanh (sinh PRD, Gherkin, UML trong phút) |
| Kết hợp | — | Dùng AI để tạo bản thảo, rồi người thật xác nhận |

## 5. Lưu ý khi bảo vệ

Khi thầy cô hỏi "bạn đã khảo sát như thế nào?", bạn có thể trả lời:

- Chúng tôi dùng **nhiều AI Agent** đóng vai khác nhau (Senior PM, BA, Edge-Case Finder, Software Architect) để khai thác ý tưởng từ nhiều góc nhìn.
- Mỗi AI role được cung cấp **prompt rõ vai trò** (ví dụ: "Bạn là Business Analyst, viết Gherkin...").
- Kết quả từ AI được **xác nhận lại bằng người thật** (chủ sân, khách hàng) trước khi đưa vào PRD.md.
- Các đầu vào/đầu ra (input/output) của hệ thống được rút ra từ User Stories Gherkin, không bỏ sót edge case (trùng giờ, hết hạn thanh toán, hủy sân).

Tất cả prompt và kết quả đã được ghi chép trong tài liệu này để tham khảo khi bảo vệ.
