# Mẫu giao diện SmashZone

## Bản đồ trang và template

| Trang/luồng | Route/controller | View |
|---|---|---|
| Trang chủ | `/` → `HomeController::index()` | `app/Views/home/index.php` |
| Danh sách chi nhánh | `/branches` → `BranchController::index()` | `app/Views/branches/index.php` |
| Chi tiết chi nhánh, sân | `/branch/{id}` → `BranchController::show()` | `app/Views/branches/show.php` |
| Chọn giờ | `/booking/{courtId}` → `BookingController::create()` | `app/Views/bookings/create.php` |
| Xác nhận thông tin | `/booking/confirm` → `BookingController::confirm()` | `app/Views/bookings/confirm.php` |
| Thanh toán | `/payment/{bookingId}` → `PaymentController::show()` | `app/Views/bookings/payment.php` |
| Lịch sử/chi tiết/thành công | `/booking/history`, `/booking/detail/{id}`, `/booking/success/{id}` | Các view cùng tên trong `app/Views/bookings/` |
| Quản trị owner | `/owner/*` → `OwnerController` | `app/Views/owner/` và layout admin |

Trang khách dùng `app/Views/layouts/main.php`; khu vực quản trị dùng `app/Views/layouts/admin.php`. Xác định đúng layout trước khi sửa header, flash message hoặc stylesheet.

## Hợp đồng cụ thể của các view

- `branches/index.php`: form lọc dùng GET với `search`, `city`; liên kết chi tiết dùng `/branch/{id}`. Empty state có nút xóa bộ lọc. View hiện có fallback chi nhánh mẫu khi `$branches` không được truyền, nên khi sửa empty state cần phân biệt thiếu dữ liệu đầu vào với mảng rỗng thực sự.
- `branches/show.php`: link đặt sân dùng `/booking/{courtId}?date=...`; query date được đưa từ controller. Court cards dùng `.court-card`; không gọi chúng là `.branch-card`.
- `bookings/create.php`: slot dùng `.time-slot`, slot đã đặt thêm `.disabled`; click slot và cập nhật summary được xử lý trong script cùng view. Form `#booking-form` POST `/booking/store` với `court_id`, `booking_date`, `start_hour`, `end_hour`. Date picker cập nhật hidden field `form_booking_date`; không đổi một phía mà quên đồng bộ JS/controller.
- `bookings/confirm.php`: form POST `/booking/confirm` gửi thông tin khách; kiểm tra field và CSRF cùng controller trước khi đổi template.
- `bookings/payment.php`: form POST `/payment/process`; `payment_method` là radio `qr`, `momo`, `cash`, không phải `<select>`. Trang nhận `remainingSeconds` từ controller; timer hết hạn chuyển hướng về endpoint payment để server hủy booking. Không reset timer thành 3 phút bằng JavaScript.
- `bookings/history.php`: status map dùng `pending`, `confirmed`, `cancelled`, `completed`; khi đổi status cần cập nhật nhãn/badge có liên quan, nhưng không đổi logic nghiệp vụ trong view.

## Hai bộ giao diện

- Trang khách hàng dùng layout chính và `public/assets/css/style.css` (nền sáng, token như `--bg-primary`, `--accent`, `--accent-hover`).
- Trang owner dùng `public/assets/css/admin.css` cùng layout quản trị tối. Không dùng token riêng của admin trong trang khách.
- Tái sử dụng token hiện có thay vì thêm màu hard-code mới khi đã có token tương ứng.
- Trước khi thêm style, tìm component/token hiện có; ưu tiên class theo component/trang, tránh selector rộng, độ ưu tiên CSS tăng dần và `!important` không cần thiết.
- Khi sửa selector, layout hoặc script dùng chung, tìm tất cả caller và kiểm tra một màn hình đại diện cho mỗi khu vực bị ảnh hưởng.

## JavaScript và tích hợp dữ liệu

- Kiểm tra script và listener hiện có trước khi thêm handler; tránh bind lặp, giữ state hiển thị đồng bộ với field gửi backend và dùng event delegation khi phần tử được tạo động.
- Với request bất đồng bộ, xử lý trạng thái loading, lỗi validation/server, lỗi mạng/response không hợp lệ và success theo khả năng thực tế của luồng. Ngăn gửi lặp khi cần, giữ dữ liệu người dùng khi lỗi và không báo thành công trước response thành công.
- Nếu có thể đổi bộ lọc/lựa chọn khi request trước chưa xong, ngăn kết quả cũ ghi đè state mới. Không dùng `setTimeout()` tùy tiện để đồng bộ giao diện.
- Phân biệt API trả danh sách rỗng với request thất bại; không biến lỗi thành dữ liệu rỗng hoặc mock fallback. Frontend không được tự quyết định quyền truy cập, giá cuối cùng, khả dụng sân hay trạng thái thanh toán.
- Xem route/controller/response hiện hành trước khi dùng field, status code hoặc endpoint; không đoán response shape.

## Accessibility và responsive

- Dùng phần tử HTML ngữ nghĩa trước khi thêm ARIA; gắn `label` với input, giữ focus dễ thấy, hỗ trợ keyboard và hiển thị lỗi gần trường liên quan khi phù hợp.
- Nút hành động dùng `<button>`, điều hướng dùng `<a>`. Không dựa chỉ vào màu để truyền trạng thái; bảo đảm tên nút/ảnh và nội dung lỗi có ý nghĩa.
- Kiểm tra giao diện ở viewport rộng 375px: không tràn ngang, nội dung/nút không bị cắt, form vẫn dùng được. Nếu thay đổi ảnh hưởng bố cục đáng kể, kiểm tra thêm tablet/desktop theo breakpoint hiện có.
- Với modal, kiểm tra đóng/mở bằng keyboard, focus và tương tác nền; không tạo modal khi hiển thị inline đơn giản hơn.

## Trạng thái tương tác

Mỗi thao tác bất đồng bộ hoặc form nên có trạng thái phù hợp:

- **Loading:** khóa nút gửi, hiện nhãn “Đang xử lý...” và tránh gửi lặp.
- **Error:** nêu lỗi cạnh trường hoặc bằng thông báo dễ nhận biết; không xóa dữ liệu người dùng vừa nhập.
- **Empty:** giải thích chưa có dữ liệu và đưa hành động tiếp theo, như xóa bộ lọc hoặc tạo mới.
- **Success:** xác nhận kết quả và cung cấp bước tiếp theo, như xem booking vừa tạo.

Không cần hiển thị cả bốn trạng thái cùng lúc; bảo đảm trạng thái có thể xảy ra trong luồng đã được xử lý.

## DOM và biểu mẫu đặt sân

Trong `app/Views/bookings/create.php`, slot có dạng `.time-slot`, slot đã đặt thêm `.disabled`; lưới là `#slot-grid`. Form `#booking-form` gửi `court_id`, `booking_date`, `start_hour`, `end_hour` đến `/booking/store`. Không đổi tên field hoặc selector mà không cập nhật JavaScript và controller cùng lúc.

Trong `app/Views/bookings/payment.php`, phương thức thanh toán là radio `input[name="payment_method"]` với giá trị `qr`, `momo`, `cash`; form POST gửi `booking_id` và có CSRF field. Khi đổi timer, tiếp tục dựa trên số giây do backend truyền vào, không khởi động lại đủ 3 phút mỗi lần tải lại trang.

Các view chi nhánh có dữ liệu mock dự phòng khi controller không truyền dữ liệu. Tránh thêm fallback mock mới hoặc dùng fallback đó để che lỗi dữ liệu/controller; nếu sửa luồng dữ liệu, giữ trạng thái empty thật.

## Sai khác hiện có cần kiểm tra khi sửa form

Form `#booking-form` trong `bookings/create.php` hiện POST nhưng chưa có `Session::csrfField()`, và `BookingController::store()` hiện chưa gọi `csrfProtect()`. Đây là thiếu sót hiện hữu, không phải lý do để bỏ CSRF ở form mới. Nếu chạm luồng này, thêm CSRF ở cả view và handler, rồi xác minh các test/browser flow dùng session token hợp lệ.

## An toàn template và responsive

- Escape dữ liệu động bằng `htmlspecialchars()` trước khi đưa vào HTML.
- `app/Views/layouts/main.php` hiện render flash lỗi chưa escape để giữ định dạng `<br>`. Chỉ truyền thông báo do ứng dụng kiểm soát; không ghép input hoặc dữ liệu DB vào HTML flash. Cân nhắc chuyển validation errors thành danh sách đã escape khi sửa luồng đó.
- Kiểm tra viewport 375px: không có tràn ngang, form không bị ép quá hẹp, nút chính vẫn dễ bấm.
