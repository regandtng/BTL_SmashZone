# SLIDE OUTLINE — SmashZone: Hệ thống Đặt Sân Badminton Online

Tổng sliden: 10-12 trang. Thời gian trình bày: 7-10 phút.

---

## Slide 1: Titolo / Giới thiệu đề tài
- Tên đề tài: **SmashZone — Hệ thống Đặt Sân Badminton Online**
- Đội ngũ: [Tên các thành viên]
- Mã lớp / Mã môn học
- Ngày bảo vệ

**Nội dung chính:**
- SmashZone giải quyết bài toán: khách hàng khó tìm sân trống, dễ trùng giờ, mất thời gian gọi điện đặt sân.
- Mục tiêu: đặt sân online trong 3 phút, tự động giải phóng sân nếu hết hạn thanh toán.

---

## Slide 2: Bài toán & Phạm vi (Scope)
**Bài toán thực tế:**
- Sân badminton chất lượng cao nhưng kênh đặt vẫn truyền thống (telegram/Zalo/điện thoại).
- Trùng giờ đặt sân xảy ra thường xuyên khi nhiều người gọi cùng lúc.
- Khách hàng không biết chi nhánh nào gần nhà, giá cả không minh bạch.

**Phạm vi hệ thống:**
| Bao gồm | Không bao gồm |
|---------|---------------|
| Đặt sân khách hàng, thanh toán, lịch sử | Ứng dụng di động native |
| Quản trị owner (chi nhánh, giá, doanh thu) | Thiết bị IoT khóa sân tự động |
| Cron job dọn dẹp đơn hết hạn | Tích hợp API ngân hàng thật |

---

## Slide 3: Phương pháp khảo sát yêu cầu (AI + Truyền thống)
**Quy trình khảo sát:**
1. AI đóng vai **Senior BA** → rút ra Epic, Feature, User Stories.
2. AI đóng vai **Người dùng hệ thống** → kiểm tra giao diện, luồng thao tác.
3. AI đóng vai **Edge-Case Finder** → đặt câu hỏi phản biện (trùng giờ, hết hạn, hủy sân).
4. Nhân thật phỏng vấn chủ sân, khách hàng để xác nhận đầu vào/đầu ra.

**Đầu vào:** ý tưởng thô, luồng đặt sân, dữ liệu chi nhánh.
**Đầu ra:** PRD.md, user stories Gherkin, edge cases, ERD, sequence diagram.

---

## Slide 4: Sơ đồ Kiến trúc (System Architecture)
Hình: `architecture.mermaid` (trình duyệt Mermaid hoặc ảnh đã xuất).

**Các thành phần chính:**
- Trình duyệt/Mobile Web (Client)
- Apache/Nginx (Web Server)
- PHP MVC (Router → Controller → Model → View)
- MySQL Database
- Cron Job (background, chạy định kỳ)

**Lý do chọn kiến trúc MVC PHP thuần:**
- Không cần build tool, deploy dễ trên shared hosting/XAMPP.
- Dễ hiểu, kiểm soát toàn bộ mã nguồn (đáp ứng tiêu chí chấm "hiểu toàn bộ mã nguồn").
- Phù hợp dự án nhỏ trung, không quá thiết kế.

---

## Slide 5: Sơ đồ ERD (Cơ sở dữ liệu)
Hình: `erd.mermaid`.

**Các bảng chính:**
- `users`, `owners`, `owner_branches` (xác thực & phân quyền)
- `branches`, `courts` (chi nhánh & sân)
- `bookings`, `payments` (đặt sân & thanh toán)
- `pricing_rules` (quy giá theo khung giờ)

**Lưu ý:** Đã bổ sung `completed_at` và status `completed` vào bảng `bookings` để cron job auto-complete chạy đúng.

---

## Slide 6: Sơ đồ Tuần tự (Sequence Diagram)
Hình: `sequence.mermaid`.

**Luồng chính:** Khách hàng chọn sân → Kiểm tra trùng giờ → Tạo booking pending (3 phút) → Thanh toán → Xác nhận → Cron dọn dẹp đơn hết hạn.

**Điểm nhấn:** Double-check availability trước khi xác nhận booking để tránh trùng giờ (race condition).

---

## Slide 7: Git Workflow & Quản lý mã nguồn
- Chiến lược nhánh: `main`, `feature/*`, `fix/*`, `hotfix/*`
- Pull Request review ít nhất 1 người trước khi merge.
- Branch protection: không push trực tiếp vào `main`.
- Tài liệu: `GIT_WORKFLOW.md`

---

## Slide 8: Kỹ năng & AI Agent đã sử dụng
| Skill | Vai trò | AI Agent / Công cụ |
|-------|---------|-------------------|
| Senior Product Manager | Phân rã Epic, Feature, User Stories | AI đóng vai Senior PM |
| Business Analyst | Viết Gherkin, tìm edge cases | AI đóng vai BA + Edge-Case Finder |
| Software Architect | Thiết kế MVC, ERD, Sequence | AI đóng vai Architect |
| Backend Engineer | PHP Controller, Model, Cron | AI đóng vai Backend Engineer |
| Frontend | PHP Views, trạng thái Loading/Error/Empty | AI đóng vai Frontend Developer |
| QA Engineer | Unit test, E2E Playwright, OWASP | AI đóng vai QA + Security Auditor |

**Công cụ AI đã dùng:** [Tên AI Agent, ví dụ: Cursor, Copilot, v.v.]

---

## Slide 9: Kết quả đạt được
- Hệ thống đặt sân chạy được: đăng ký → chọn sân → thanh toán → lịch sử.
- Giao diện quản trị owner: dashboard, chi nhánh, giá, doanh thu.
- Tài liệu kỹ thuật đầy đủ: PRD.md, ERD, Sequence, Architecture, Git Workflow, README.
- Test: Unit test (Booking, PricingRule), E2E Playwright, Checklist OWASP.
- Sửa lỗi quan trọng: `OwnerController` thiếu, `cron.php` lệch schema (`completed_at`).

---

## Slide 10: Kết luận & Hướng phát triển
- Hệ thống đáp ứng nhu cầu đặt sân badminton cơ bản.
- Hướng phát triển: tích hợp MoMo/QR thật, ứng dụng di động, IoT khóa sân, thông báo email/SMS.

**Cảm ón quan ý!**
