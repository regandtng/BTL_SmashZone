# CÂU HỎI PHẢN BIỆN & CÂU TRẢ LỜI CHUẨN — SmashZone

Danh sách câu hỏi thầy cô dễ hỏi khi bảo vệ, cùng câu trả lời chuẩn để bạn tự tin phản biện.

---

## 1. Về kỹ thuật & kiến trúc

### Câu 1: Tại sao chọn PHP thuần thay vì framework (Laravel/Symfony) hay React?
**Trả lời:**
- Dự án quy mô nhỏ trung, không cần tính năng phức tạp của framework lớn.
- PHP thuần giúp kiểm soát toàn bộ mã nguồn, dễ hiểu cho mọi thành viên nhóm (đáp ứng tiêu chí "hiểu toàn bộ mã nguồn").
- Deploy rất dễ trên shared hosting/XAMPP, không cần cài Composer, Node.js, hay build tool.
- Nếu dự án mở rộng sau này, có thể refactor sang framework mà không mất dữ liệu (CSDL MySQL giữ nguyên).

### Câu 2: Hệ thống có áp dụng thiết kế chuẩn (Clean Architecture, MVC) không?
**Trả lời:**
- Áp dụng MVC đơn giản: `Router` phân luồng, `Controller` xử lý yêu cầu, `Model` tương tác DB, `View` hiển thị.
- Có phân tầng rõ ràng trong `core/` (Controller, Model, Database, Session, Router).
- Hiện tại chưa tách hoàn toàn Service/Repository do quy mô nhỏ, nhưng có thể mở rộng sau này mà không ảnh hưởng cấu trúc hiện có.

### Câu 3: Làm sao biết code không có lỗi import vòng tròn (circular dependency)?
**Trả lời:**
- Dự án PHP thuần không sử dụng autoload phức tạp, mỗi classRequire_once trực tiếp.
- Không có thư viện bên ngoài phụ thuộc lẫn nhau, do đó không có hiện tượng import vòng tròn.
- Có thể sử dụng Dependency Cruiser (nếu có JS/TS) hoặc kiểm tra thủ công đồ thị phụ phục trong `core/` và `app/`.

---

## 2. Về cơ sở dữ liệu & Cron Job

### Câu 4: Tại sao bảng `bookings` cần cột `completed_at` và status `completed`?
**Trả lời:**
- Ban đầu schema chỉ có `pending`, `confirmed`, `cancelled`.
- Cron job `cron.php --auto-complete` cần cập nhật booking từ `confirmed` sang `completed` khi giờ chơi kết thúc, đồng thời ghi thời điểm hoàn thành vào `completed_at`.
- Nếu không có cột này, cron job sẽ lỗi khi chạy SQL `SET status='completed', completed_at=NOW()`. Đã sửa bằng cách ALTER TABLE thêm cột và giá trị ENUM.

### Câu 5: Cron job chạy thế nào? Ai kích hoạt nó?
**Trả lời:**
- `cron.php` chạy qua command line (Windows Task Scheduler hoặc Linux cron).
- Lệnh: `php cron.php --all` (chạy cả auto-complete và clean-pending).
- Tần suất đề xuất: mỗi 5-10 phút một lần.
- Có tham số `--auto-complete` (kết thúc giờ chơi) và `--clean-pending` (hủy đơn quá hạn thanh toán 3 phút).

### Câu 6: Làm sao xử lý trùng giờ khi hai người đặt cùng lúc?
**Trả lời:**
- Có double-check trong `BookingController::processConfirm()`: trước khi insert booking, gọi lại `isSlotAvailable()`.
- Nếu khung giờ đã bị người khác đặt giữa thời điểm chọn và thời điểm xác nhận, hệ thống hủy pending và thông báo "Sân vừa được đặt bởi người khác".
- Cơ sở dữ liệu cũng có ràng buộc UNIQUE trên `booking_code`, nhưng việc kiểm tra trùng giờ nằm ở tầng ứng dụng.

---

## 3. Về bảo mật (OWASP)

### Câu 7: Hệ thống có bảo vệ SQL Injection không?
**Trả lời:**
- Tất cả truy vấn dùng PDO prepared statements (`prepare` + `execute([params])`).
- Không ghép chuỗi SQL trực tiếp từ input người dùng trong các controller/model chính.
- Lưu ý: `Model::findBy($column, ...)` truyền tên cột trực tiếp vào SQL — cần whitelist tên cột nếu cho phép input động từ user.

### Câu 8: Có bảo vệ CSRF không?
**Trả lời:**
- `Core\Session` đã có sẵn `generateCsrfToken()` và `validateCsrfToken()`.
- Hiện tại các form POST chưa tích hợp token CSRF trên giao diện — đây là điểm cần bổ sung trước khi đưa vào production.

### Câu 9: Phân quyền owner có chặt chẽ không?
**Trả lời:**
- Owner chỉ xem dữ liệu chi nhánh mình sở hữu qua bảng `owner_branches`.
- `OwnerController::requireBranchAccess()` kiểm tra trước mọi tác vụ trên chi nhánh.
- Super Admin (`is_super_admin = TRUE`) có quyền xem toàn bộ.

### Câu 10: Mật khẩu được lưu thế nào?
**Trả lời:**
- Sử dụng `password_hash()` (bcrypt) khi đăng ký/đổi mật khẩu.
- Sử dụng `password_verify()` khi đăng nhập.
- Không lưu mật khẩu dạng văn bản thô.

---

## 4. Về Frontend (PHP thuần)

### Câu 11: Frontend không có component React/Vue, thế nào gọi là "frontend chuẩn hóa"?
**Trả lời:**
- Frontend sử dụng PHP Views + HTML/CSS thuần, chia layout chung (`layouts/`), partial cho header/footer.
- Các trạng thái mạng được xử lý qua flash message (Session::flash): Success, Error, Empty state.
- Lý do không dùng React: dự án PHP thuần không có build step, thêm React sẽ tăng độ phức tạp không cần thiết cho quy mô này. Nếu muốn hiện đại hóa sau này, có thể tách API ra và kết nối với frontend React riêng.

### Câu 12: Xử lý trạng thái Loading/Error/Empty thế nào?
**Trả lời:**
- **Loading:** Hiện tại hệ thống PHP thuần render trang hoàn toàn trước khi gửi về trình duyệt, nên không có spinner/skeleton động trên từng trang. Trang thanh toán có đồng hồ đếm ngược 3 phút (JavaScript) để nhấn mạnh thời gian chờ. Nếu cần loading spinner, có thể bổ sung JS đơn giản trên form đặt sân.
- **Error:** Flash message qua `Session::flash()` hiển thị lỗi (ví dụ "Khung giờ đã được đặt", "Sai mật khẩu", "Quá hạn thanh toán").
- **Empty:** Trang danh sách chi nhánh/trống, lịch sử trống hiển thị thông báo "Không có dữ liệu" thay vì trang trắng (xem `branches/index.php`, `bookings/history.php`).

---

## 5. Về quy trình & Git

### Câu 13: Lịch sử commit và Git Workflow thế nào?
**Trả lời:**
- Repo đã có 32 commit trên `main` (không chỉ 1 commit ban đầu).
- Đã tạo tài liệu `GIT_WORKFLOW.md` mô tả chiến lược nhánh (`main`, `feature/*`, `fix/*`), quy trình Pull Request review, branch protection.
- Từ lúc nhận góp ý, mọi thay đổi mới được thực hiện trên nhánh `fix/*` và merge vào `main` qua Pull Request thật (chụp màn hình PR làm bằng chứng khi bảo vệ).
- Quy ước commit message: `feat:`, `fix:`, `docs:` (áp dụng cho các commit mới).

### Câu 14: Có tài liệu kỹ thuật gì để dự án dễ duy trì sau này?
**Trả lời:**
- `PRD.md`: yêu cầu, user stories Gherkin, edge cases.
- `erd.mermaid`, `sequence.mermaid`, `architecture.mermaid`: sơ đồ UML (mã Mermaid, chỉnh sửa được).
- `README.md`: hướng dẫn cài đặt, tài khoản mẫu, cấu trúc thư mục.
- `GIT_WORKFLOW.md`: quy trình làm việc nhóm.
- `TEST_REPORT.md`, `OWASP_CHECKLIST.md`: kiểm thử và bảo mật.

---

## 6. Về AI Agent đã sử dụng

### Câu 15: Dùng AI Agent gì, và để làm gì trong dự án?
**Trả lời:**
- Công cụ AI Agent đã dùng: **OpenCode, Claude, GitHub Copilot**
- Vai trò AI Agent đã đóng trong dự án:
  - Senior PM: phân rã ý tưởng thành Epic, Feature, User Stories (gốc cho `docs/PRD.md`).
  - BA & Edge-Case Finder: viết User Stories Gherkin, tìm rủi ro bảo mật và nghiệp vụ (gốc cho bảng EC-1 đến EC-8 trong `docs/PRD.md`).
  - Software Architect: thiết kế MVC, ERD, Sequence diagram (gốc cho `docs/erd.mermaid`, `docs/sequence.mermaid`, `docs/architecture.mermaid`).
  - Backend Engineer: sinh mã Controller, Model, Cron job (`app/Controllers/OwnerController.php`, `cron.php`).
  - QA Engineer: viết unit test, kịch bản Playwright, quét OWASP (`tests/`, `docs/TEST_REPORT.md`, `docs/OWASP_CHECKLIST.md`).
- Mọi đoạn code AI sinh ra đều được nhóm đọc lại, kiểm tra và sửa lỗi trước khi commit (không mù quen copy-paste).

---

## Mẹo bảo vệ
- Nếu không nhớ câu trả lời chi tiết, hãy trả lời theo hướng: "Đây là cách chúng tôi đã xử lý, và nếu có thời gian hơn chúng tôi sẽ cải thiện như sau..."
- Luôn tham chiếu đến file tài liệu/code cụ thể (ví dụ: "xem chi tiết tại `OWNERController.php:45`").
- Thể hiện sự chủ động trong việc sửa lỗi (OwnerController, cron.php) — thầy cô đánh giá cao thái độ này.
