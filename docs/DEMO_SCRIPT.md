# KỊCH BẢN LIVE DEMO — SmashZone

Thời gian demo: 5-7 phút. Môi trường: XAMPP (Apache + MySQL), trình duyệt Chrome.

---

## Chuẩn bị trước demo
- [ ] XAMPP chạy, MySQL đã import `database/schema.sql`.
- [ ] File `.env` đã cấu hình DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD.
- [ ] Truy cập được `http://localhost/BTL_SmashZone/`.
- [ ] Tài khoản demo: owner `admin` / `admin123`, khách hàng `khach@gmail.com` / (mật khẩu mẫu).
- [ ] Chuẩn bị video dự phòng (xem phần sau) trong trường hợp máy tính lỗi/lỗi mạng.

---

## Bước 1: Mở trang chủ (30 giây)
**Hành động:** Truy cập `http://localhost/BTL_SmashZone/`.
**Kết quả mong đợi:** Trang chủ hiển thị danh sách chi nhánh nổi bật, tổng số sân, tổng đặt sân.
**Nói:** "Đây là trang chủ SmashZone, khách hàng có thể xem tổng quan các chi nhánh và số sân hiện có."

---

## Bước 2: Đăng ký / Đăng nhập khách hàng (1 phút)
**Hành động:** 
- Nhấn "Đăng ký", nhập tên, email, SĐT, mật khẩu.
- Hoặc đăng nhập với tài khoản mẫu đã có sẵn.
**Kết quả mong đợi:** Đăng nhập thành công, chuyển hướng về trang chủ hoặc lịch sử đặt sân.
**Nói:** "Khách hàng dễ dàng tạo tài khoản và đăng nhập để bắt đầu đặt sân."

---

## Bước 3: Tìm chi nhánh và chọn sân (1 phút)
**Hành động:** 
- Vào /branches, chọn thành phố hoặc tìm kiếm.
- Nhấn vào một chi nhánh, xem danh sách sân (A1..A10).
- Chọn một sân, chọn ngày hôm nay.
**Kết quả mong đợi:** Trang chọn khung giờ hiển thị, các khung giờ đã đặt màu đỏ ("booked"), các khung trống màu xanh ("available").
**Nói:** "Hệ thống hiển thị rõ khung giờ nào trống, nào đã bị đặt để khách hàng tránh trùng lịch."

---

## Bước 4: Đặt sân và xác nhận thông tin (1 phút)
**Hành động:** 
- Chọn một khung giờ trống (ví dụ 18:00-20:00).
- Nhấn "Đặt sân", nhập họ tên, SĐT, email.
- Nhấn "Xác nhận".
**Kết quả mong đợi:** Chuyển đến trang thanh toán, hiển thị mã booking, tổng tiền, thời gian hết hạn (3 phút).
**Nói:** "Sau khi chọn sân, hệ thống tạo đơn đặt sân chờ thanh toán trong 3 phút."

---

## Bước 5: Thanh toán (1 phút)
**Hành động:** 
- Chọn phương thức: Tiền mặt (hoặc MoMo/QR demo).
- Nhấn "Thanh toán".
**Kết quả mong đợi:** Chuyển đến trang thành công, hiển thị mã đặt sân, trạng thái "Confirmed".
**Nói:** "Khách hàng chọn tiền mặt tại sân, đơn hàng được xác nhận và khung giờ được giữ."

---

## Bước 6: Quản trị Owner (1 phút)
**Hành động:** 
- Đăng xuất khách hàng, đăng nhập owner (`admin` / `admin123`).
- Vào `/owner/dashboard`, xem tổng số đặt sân, doanh thu.
- Vào `/owner/bookings`, xem danh sách đặt sân của chi nhánh mình quản lý.
- Vào `/owner/pricing`, xem/sửa quy giá.
**Kết quả mong đợi:** Owner chỉ thấy dữ liệu của chi nhánh mình sở hữu (phân quyền).
**Nói:** "Giao diện quản trị giúp chủ sân theo dõi đặt sân và doanh thu, với phân quyền chặt chẽ theo chi nhánh."

---

## Bước 7: Kiểm tra Cron Job (30 giêu)
**Hành động:** 
- Mở terminal, chạy `php cron.php --clean-pending`.
- Hoặc trình diễn qua booking pending đã hết hạn (nếu có).
**Kết quả mong đợi:** Cron job hủy tự động booking pending quá hạn, giải phóng khung giờ.
**Nói:** "Cron job chạy định kỳ để dọn dẹp đơn quá hạn thanh toán, đảm bảo sân không bị bỏ giữia."

---

## Lưu ý khi demo
- Nếu trang load chậm, tắt các extension trình duyệt không cần thiết.
- Nếu lỗi kết nối DB, kiểm tra XAMPP MySQL đã chạy và `.env` đúng.
- Nếu OwnerController lỗi 404, kiểm tra file `app/Controllers/OwnerController.php` đã tồn tại.
