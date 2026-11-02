# KẾ HOẠCH QUAY VIDEO DỰ PHÒNG — SmashZone

Nếu máy tính lỗi, mạng gián đoạn, hoặc trình diễn trực tiếp không thành công, sử dụng video quay màn hình trước.

## 1. Nội dung video cần quay
| Phần | Thời lượng | Mô tả |
|------|-----------|-------|
| Intro + Trang chủ | 30 giây | Mở trình duyệt, vào homepage SmashZone |
| Đăng ký/Đăng nhập | 45 giây | Tạo tài khoản mới hoặc đăng nhập mẫu |
| Chọn chi nhánh & sân | 1 phút | Truy cập /branches, chọn chi nhánh, chọn sân |
| Chọn khung giờ & đặt | 1 phút | Chọn giờ trống, nhập thông tin khách hàng |
| Thanh toán & thành công | 45 giây | Chọn phương thức, xem trang success |
| Quản trị Owner | 1 phút | Đăng nhập owner, dashboard, bookings, pricing |
| Cron job | 30 giây | Chạy `php cron.php --clean-pending` trên terminal |
| **Tổng cộng** | **~5 phút** | |

## 2. Công cụ quay màn hình đề xuất
- **OBS Studio** (miễn phí, multi-platform) — khuyên dùng.
- **ShareX** (Windows) — nhẹ, dễ dùng.
- **Camtasia** (trả phí) — nếu cần edit video chuyên nghiệp.

## 3. Cách lưu và lưu trữ
- Xuất video định dạng MP4, độ phân giải 1080p, bitrate trung bình.
- Đặt tên file: `SmashZone_Demo_Backup.mp4`.
- Lưu vào thư mục `demo/backup_video/` tại gốc repo.
- Đồng thời upload lên Google Drive / YouTube (không công khai) để có link dự phòng.

## 4. Cách trình bày khi dùng video dự phòng
- Mở video trên máy chiếu/máy tính bảo vệ.
- Dùng chuột trỏ vào các màn hình quan trọng khi narrator giải thích.
- Nếu video bị lỗi, chuẩn bị sẵn ảnh chụp màn hình (screenshot) của từng bước trên slide.

## 5. Checklist trước khi bảo vệ
- [ ] Video MP4 đã chạy thử trên máy bảo vệ.
- [ ] Link Google Drive/YouTube đã test mở được.
- [ ] Screenshot từng bước demo đã được đưa vào slide (fallback cuối cùng).
- [ ] Có sẵn dây cắm HDMI / chuyển màn hình nếu cần.
