# PRD — SmashZone: Hệ thống Đặt Sân Badminton Online

## 1. Mô tả đề tài
SmashZone là hệ thống đặt sân badminton trực tuyến, cho phép khách hàng tìm chi nhánh, chọn sân, đặt khung giờ và thanh toán online. Hệ thống cũng hỗ trợ giao diện quản trị cho chủ sân (owner) theo dõi đặt sân, quản lý giá và doanh thu.

## 2. Mục tiêu & phạm vi
- **Mục tiêu:** Giảm thời gian chờ đặt sân, tránh trùng giờ, tự động giải phóng sân khi hết hạn thanh toán.
- **Phạm vi:** Đặt sân khách hàng, thanh toán (QR/Momo/tiền mặt), lịch sử đặt sân, quản trị cơ sở sân và giá theo khung giờ (owner).
- **Ngoài phạm vi:** Thiết bị IoT khóa sân tự động, thanh toán ví điện tử tích hợp API ngân hàng thật, ứng dụng di động native.

## 3. Vai trò người dùng (Actor)
| Vai trò | Mô tả |
|---------|-------|
| Khách hàng | Đăng ký, đăng nhập, tìm sân, đặt sân, thanh toán, xem lịch sử. |
| Chủ sân (Owner) | Đăng nhập quản trị, quản lý chi nhánh under quyền sở hữu, đặt giá, xem doanh thu. |
| Super Admin | Quản trị toàn hệ thống, tạo owner, phân quyền chi nhánh. |

## 4. User Stories (Gherkin — Given–When–Then)

### Epic 1: Xác thực người dùng

**Story 1.1 — Đăng ký tài khoản**
```gherkin
Feature: Đăng ký tài khoản khách hàng
  Scenario: Đăng ký thành công với thông tin hợp lệ
    Given Trang đăng ký đang hiển thị
    When Người dùng nhập họ tên, email chưa tồn tại, số điện thoại 10-11 chữ số, mật khẩu ≥ 6 ký tự và xác nhận khớp
    Then Hệ thống tạo tài khoản mới, tự động đăng nhập và chuyển hướng về trang chủ

  Scenario: Đăng ký với email đã tồn tại
    Given Email "khach@gmail.com" đã được đăng ký trong hệ thống
    When Người dùng nhập cùng email đó vào form đăng ký
    Then Hệ thống báo lỗi "Email này đã được đăng ký" và không tạo tài khoản mới

  Scenario: Đăng ký với số điện thoại không hợp lệ
    Given Người dùng nhập số điện thoại "0123" (ít hơn 10 chữ số)
    When Nhấn nút Đăng ký
    Then Hệ thống báo lỗi "Số điện thoại không hợp lệ"
```

**Story 1.2 — Đăng nhập**
```gherkin
Feature: Đăng nhập hệ thống
  Scenario: Khách hàng đăng nhập thành công
    Given Tài khoản khách hàng tồn tại với email và mật khẩu đúng
    When Người dùng nhập email và mật khẩu đúng
    Then Hệ thống tạo session người dùng và chuyển hướng đến lịch sử đặt sân

  Scenario: Owner đăng nhập thành công
    Given Tài khoản owner tồn tại trong bảng owners
    When Người dùng nhập username và mật khẩu owner đúng
    Then Hệ thống tạo session owner và chuyển hướng đến /owner/dashboard

  Scenario: Sai mật khẩu nhiều lần
    Given Người dùng nhập sai mật khẩu 3 lần liên tiếp
    When Thử đăng nhập lần thứ 4
    Then Hệ thống vẫn cho phép thử (khóa tạm thời nếu cần triển khai sau)
```

### Epic 2: Tìm kiếm và đặt sân

**Story 2.1 — Xem danh sách chi nhánh**
```gherkin
Feature: Xem chi nhánh sân badminton
  Scenario: Xem danh sách tất cả chi nhánh đang hoạt động
    Given Có 13 chi nhánh trong cơ sở dữ liệu với is_active = TRUE
    When Người dùng truy cập trang /branches
    Then Hệ thống hiển thị danh sách 13 chi nhánh kèm địa chỉ, thành phố, số sân
```

**Story 2.2 — Chọn sân và khung giờ**
```gherkin
Feature: Chọn sân và khung giờ đặt
  Scenario: Xem khung giờ trống của một sân
    Given Sân A1 tại chi nhánh Cầu Giấy đang active vào hôm nay
    When Người dùng chọn sân A1 và ngày hôm nay
    Then Hệ thống hiển thị khung giờ từ 05:00 đến 22:00, đánh dấu những giờ đã được đặt là "booked"

  Scenario: Chọn khung giờ đã bị đặt
    Given Khung giờ 18:00-20:00 hôm nay của sân A1 đã có booking status confirmed
    When Người dùng chọn khung giờ 18:00-20:00
    Then Hệ thống báo lỗi "Khung giờ này đã được đặt. Vui lòng chọn khung giờ khác."
```

**Story 2.3 — Tạo đặt sân pending**
```gherkin
Feature: Tạo đơn đặt sân chờ thanh toán
  Scenario: Khách hàng đăng nhập chọn sân và xác nhận thông tin
    Given Khách hàng đã đăng nhập và chọn sân, ngày, khung giờ hợp lệ
    When Nhấn nút Xác nhận đặt sân
    Then Hệ thống tạo booking với status = 'pending', expires_at = NOW() + 3 phút, và chuyển hướng đến trang thanh toán

  Scenario: Khách hàng chưa đăng nhập cố gắng đặt sân
    Given Session không có user_id
    When Người dùng cố gắng POST /booking/store
    Then Hệ thống chuyển hướng đến trang đăng nhập với flash lỗi "Vui lòng đăng nhập để đặt sân."
```

### Epic 3: Thanh toán

**Story 3.1 — Thanh toán thành công (QR/Momo)**
```gherkin
Feature: Thanh toán đơn đặt sân
  Scenario: Thanh toán qua MoMo thành công
    Given Booking đang trong trạng thái pending và chưa hết hạn 3 phút
    When Người dùng chọn phương thức MoMo và xác nhận thanh toán
    Then Hệ thống tạo bản ghi payment status = 'paid', cập nhật booking status = 'confirmed', hiển thị trang thành công

  Scenario: Thanh toán tiền mặt tại sân
    Given Booking đang pending
    When Người dùng chọn phương thức Tiền mặt
    Then Hệ thống tạo payment status = 'pending', cập nhật booking status = 'confirmed', nhắc nhắc thanh toán tại sân
```

**Story 3.2 — Hết hạn thanh toán**
```gherkin
Feature: Hủy tự động đơn quá hạn thanh toán
  Scenario: Booking pending quá 3 phút chưa thanh toán
    Given Booking tạo vào 10:00:00 với expires_at = 10:03:00
    When Hiện tại là 10:04:00 và người dùng truy cập trang thanh toán
    Then Hệ thống tự động cập nhật booking status = 'cancelled' và giải phóng khung giờ

  Scenario: Cron job dọn dẹp pending hết hạn
    Given Có nhiều booking pending với expires_at < NOW()
    When Chạy cron.php --clean-pending
    Then Tất cả booking đó được cập nhật status = 'cancelled'
```

### Epic 4: Lịch sử và quản lý cá nhân

**Story 4.1 — Xem lịch sử đặt sân**
```gherkin
Feature: Xem lịch sử đặt sân của khách hàng
  Scenario: Khách hàng xem lịch sử của mình
    Given Khách hàng đã đăng nhập và có 3 đặt sân trong quá khứ
    When Truy cập trang /booking/history
    Then Hệ thống hiển thị danh sách 3 đặt sân kèm mã booking, trạng thái, tổng tiền

  Scenario: Xem lịch sử theo số điện thoại (không đăng nhập)
    Given Người dùng nhập số điện thoại đã từng đặt sân
    When Nhấn tìm kiếm trên trang lịch sử
    Then Hệ thống hiển thị các đặt sân liên kết với số điện thoại đó
```

### Epic 5: Quản trị Owner

**Story 5.1 — Dashboard owner**
```gherkin
Feature: Owner quản lý chi nhánh sở hữu
  Scenario: Owner xem dashboard của chi nhánh mình quản lý
    Given Owner được gán quản lý chi nhánh id 1 và id 2 qua bảng owner_branches
    When Owner đăng nhập và truy cập /owner/dashboard
    Then Hệ thống chỉ hiển thị thông tin, đặt sân và doanh thu của 2 chi nhánh đó

  Scenario: Owner cố gắng truy cập chi nhánh không sở hữu
    Given Owner không có quyền quản lý chi nhánh id 5
    When Owner truy cập trực tiếp URL /owner/dashboard?branch_id=5
    Then Hệ thống từ chối truy cập (403 Forbidden)
```

## 5. Edge Cases & Rủi ro (phản biện thầy cô)

| # | Edge case | Xử lý đề xuất |
|---|-----------|---------------|
| EC-1 | **Trùng giờ đặt sân:** Hai người chọn cùng sân, cùng giờ gần đồng thời | Double-check availability tại `processConfirm` trước khi insert booking; nếu trùng thì hủy pending và thông báo |
| EC-2 | **Hết hạn thanh toán (3 phút)** | Cron `--clean-pending` chạy định kỳ; controller kiểm tra `expires_at` trước khi hiển thị trang thanh toán |
| EC-3 | **Hủy sân trước khi thanh toán** | Cho phép hủy booking pending (trả lại khung giờ), không cho hủy booking confirmed đã thanh toán trừ khi owner can thiệp |
| EC-4 | **Booking vào quá khứ** | Controller chặn ngày `< today`; nếu người dùng cố tình sửa URL, hệ thống điều chỉnh về hôm nay |
| EC-5 | **Khung giờ ngoài giờ mở cửa** | Chỉ cho chọn từ 05:00 đến 22:00 theo `open_time`/`close_time` của chi nhánh |
| EC-6 | **Lỗi kết nối CSDL** | Wrap PDO vào try-catch, hiển thị trang lỗi 500 thân thiện, không rò râge stack trace |
| EC-7 | **CSRF / Session hijacking** | Thêm CSRF token vào tất cả form POST; session regeneration sau đăng nhập |
| EC-8 | **SQL Injection** | Sử dụng prepared statements qua PDO trên tất cả truy vấn |

## 6. Yêu cầu phi chức năng
- **Bảo mật:** Mật khẩu_hash (bcrypt), prepared statements, CSRF token, phân quyền owner/branch.
- **Hiệu năng:** Trang danh sách chi nhánh tải dưới 2 giây; API available-slots phản hồi dưới 500ms.
- **Khả dụng:** Hệ thống chạy 24/7, cron job dọn dẹp pending mỗi 5-10 phút.
- **Khả dụng trên thiết bị:** Giao diện responsive (mobile-friendly) vì khách hàng thường đặt sân bằng điện thoại.

## 7. Rang buộc kỹ thuật (hiện tại)
- Ngôn ngữ: PHP thuần (không framework), MySQL.
- Kiến trúc: MVC đơn giản (Controller → Model → View).
- Session: Native PHP session.
- Không có Composer, không có React/Vue.

---
*Tài liệu này sẽ được cập nhật sau mỗi giai đoạn thiết kế và triển khai.*