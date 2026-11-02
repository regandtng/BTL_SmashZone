# Checklist Bảo mật OWASP — SmashZone

## 1. SQL Injection
- [x] Tất cả truy vấn dùng PDO prepared statements (`$this->db->prepare` + `execute([params])`)
- [ ] Kiểm tra lại `Model::findBy($column, ...)` — cột `$column` được truyền trực tiếp vào SQL, rủi ro nếu không whitelist
- [ ] Kiểm tra `ORDER BY` động nếu có truyền từ user input

## 2. Broken Access Control (Phân quyền)
- [x] `OwnerController::requireOwner()` — chặn truy cập nếu không phải owner
- [x] `OwnerController::requireBranchAccess()` — owner chỉ xem chi nhánh mình sở hữu
- [ ] Kiểm tra `BookingController::history` — cho phép tìm theo số điện thoại (có thể rò rỉ dữ liệu người khác)
- [ ] Kiểm tra `PaymentController::show` — không kiểm tra xem booking có thuộc về user hiện tại không

## 3. CSRF (Cross-Site Request Forgery)
- [x] `Session::generateCsrfToken()` đã có sẵn trong `Core\Session`
- [x] Đã thêm token CSRF vào form `/booking/confirm`
- [x] Đã validate CSRF trong `BookingController::processConfirm()`, `PaymentController::process()`, và tất cả method POST của `OwnerController`
- [ ] Cần bổ sung CSRF vào các form còn lại (`/booking/store`, `/owner/login`, `/auth/*`) nếu cần bảo vệ cao hơn

## 4. Session Management
- [x] `session_regenerate_id(true)` sau đăng nhập thành công (đã thêm vào `AuthController::login()`)
- [x] Session cookie: `httponly=true`, `samesite=Lax`, `secure` nếu HTTPS
- [ ] Session timeout xử lý rõ ràng (hiện có `SESSION_LIFETIME`)

## 5. Password & Authentication
- [x] Mật khẩu hash bằng `password_hash` (bcrypt)
- [x] `password_verify` khi đăng nhập
- [ ] Không có giới hạn số lần đăng nhập sai (brute force)
- [ ] Không có multi-factor authentication

## 6. Error Handling & Information Disclosure
- [ ] `Database::query` catch `PDOException` và `die("Query failed: ...")` — có thể rò rỉ thông tin CSDB
- [ ] Cấu hình hiển thị lỗi PHP (`display_errors`) nên tắt trên production

## 7. File Upload (nếu có)
- [ ] Không có tính năng upload file trong repo hiện tại
- [ ] Nếu sau này thêm upload ảnh chi nhánh/sân, cần kiểm tra MIME type, tên file, đường dẫn lưu trữ

## 8. N+1 Query (Hiệu năng)
- [ ] `OwnerController::dashboard` — lặp qua mỗi branch để chạy COUNT bookings (N+1). Gộp thành 1 query GROUP BY.
- [ ] `BranchController::show` — gọi riêng `Branch::findById` và `Court::findByBranch`. Có thể JOIN.

## 9. Cron Job Security
- [ ] `cron.php` chạy qua CLI, cần hạn chế chỉ cho phép chạy từ localhost/cron job (không cho truy cập qua HTTP)
- [ ] Không có authentication cho cron endpoint nếu có thể gọi qua web

## 10. Logging & Monitoring
- [x] `cron.php` ghi log ra stdout
- [ ] Không có logging cho các hành động quan trọng (login, đặt sân, hủy sân, thay đổi giá)
