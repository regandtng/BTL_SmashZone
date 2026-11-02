<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Branch;
use App\Models\PricingRule;
use App\Models\Payment;
use Core\Session;

class BookingController extends Controller
{
    // GET /booking/{courtId}?date=...
    public function create($courtId)
    {
        $courtModel  = new Court();
        $branchModel = new Branch();

        $court = $courtModel->findById($courtId);
        if (!$court || $court['status'] !== 'active') {
            Session::flash('error', 'Sân không tồn tại hoặc đang bảo trì.');
            $this->redirect('/branches');
        }

        $branch  = $branchModel->findById($court['branch_id']);
        $date    = $_GET['date'] ?? date('Y-m-d');

        // Ensure date is not in the past
        if ($date < date('Y-m-d')) {
            $date = date('Y-m-d');
        }

        // Generate available time slots
        $bookingModel = new Booking();
        $bookedSlots  = $bookingModel->getBookedSlots($courtId, $date);

        $timeSlots = [];
        for ($h = 5; $h < 22; $h++) {
            $isPeak = ($h >= 17 || ($h >= 8 && $h < 14));
            $timeSlots[] = [
                'hour'   => $h,
                'time'   => sprintf('%02d:00', $h),
                'price'  => $isPeak ? 150000 : 80000,
                'status' => in_array($h, $bookedSlots) ? 'booked' : 'available',
            ];
        }

        $this->render('bookings/create', [
            'title'      => 'Đặt Sân ' . $court['name'] . ' - SmashZone',
            'court'      => $court,
            'branch'     => $branch,
            'date'       => $date,
            'timeSlots'  => $timeSlots,
            'courtId'    => $courtId,
            'courtName'  => $court['name'],
            'branchName' => $branch['name'] ?? '',
        ]);
    }

    // POST /booking/store
    public function store()
    {
        if (!Session::get('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập để đặt sân.');
            $this->redirect('/login');
        }

        $courtId    = $_POST['court_id'] ?? null;
        $date       = $_POST['booking_date'] ?? null;
        $startHour  = (int)($_POST['start_hour'] ?? 0);
        $endHour    = (int)($_POST['end_hour'] ?? 0);

        if (!$courtId || !$date || $startHour < 5 || $endHour > 22 || $startHour >= $endHour) {
            Session::flash('error', 'Thông tin đặt sân không hợp lệ.');
            $this->redirect('/branches');
        }

        $bookingModel = new Booking();
        $courtModel   = new Court();
        $pricingModel = new PricingRule();

        // Check availability
        if (!$bookingModel->isSlotAvailable($courtId, $date, $startHour, $endHour)) {
            Session::flash('error', 'Khung giờ này đã được đặt. Vui lòng chọn khung giờ khác.');
            $this->redirect("/booking/{$courtId}?date={$date}");
        }

        $court      = $courtModel->findById($courtId);
        $totalPrice = $pricingModel->getPrice($court['branch_id'], $date, $startHour, $endHour);

        // Store pending booking in session
        Session::set('pending_booking', [
            'court_id'     => $courtId,
            'court_name'   => $court['name'],
            'branch_id'    => $court['branch_id'],
            'booking_date' => $date,
            'start_hour'   => $startHour,
            'end_hour'     => $endHour,
            'total_price'  => $totalPrice,
        ]);

        $this->redirect('/booking/confirm');
    }

    // GET /booking/confirm
    public function confirm()
    {
        if (!Session::get('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập để đặt sân.');
            $this->redirect('/login');
        }

        $pending = Session::get('pending_booking');
        if (!$pending) {
            $this->redirect('/branches');
        }

        $branchModel = new Branch();
        $branch = $branchModel->findById($pending['branch_id']);

        $this->render('bookings/confirm', [
            'title'   => 'Xác Nhận Đặt Sân - SmashZone',
            'pending' => $pending,
            'branch'  => $branch,
            'user'    => [
                'name'  => Session::get('user_name') ?? '',
                'email' => Session::get('user_email') ?? '',
            ],
        ]);
    }

    // POST /booking/confirm
    public function processConfirm()
    {
        if (!Session::get('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập để đặt sân.');
            $this->redirect('/login');
        }

        $this->csrfProtect();

        $pending = Session::get('pending_booking');
        if (!$pending) {
            $this->redirect('/branches');
        }

        $customerName  = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');

        $errors = [];
        if (empty($customerName))  $errors[] = 'Vui lòng nhập họ tên.';
        if (empty($customerPhone) || !preg_match('/^[0-9]{10,11}$/', $customerPhone)) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        }
        if (!empty($customerEmail) && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/booking/confirm');
        }

        $bookingModel = new Booking();
        $db = \Core\Database::getInstance();
        $pdo = $db->getConnection();

        try {
            $pdo->beginTransaction();

            // Double-check availability với khóa SELECT ... FOR UPDATE (tránh race condition)
            $stmt = $pdo->prepare("
                SELECT id FROM bookings 
                WHERE court_id = ? AND booking_date = ? AND status IN ('confirmed', 'pending')
                AND ((start_time < ? AND end_time > ?) OR (start_time >= ? AND start_time < ?))
                FOR UPDATE
            ");
            $stmt->execute([
                $pending['court_id'], $pending['booking_date'], $pending['end_hour'] . ':00:00', $pending['start_hour'] . ':00:00',
                $pending['start_hour'] . ':00:00', $pending['end_hour'] . ':00:00'
            ]);
            $conflict = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($conflict) {
                $pdo->rollBack();
                Session::set('pending_booking', null);
                Session::flash('error', 'Rất tiếc! Sân vừa được đặt bởi người khác. Vui lòng chọn khung giờ khác.');
                $this->redirect('/branches');
            }

            // Tạo booking pending, hết hạn sau 3 phút
            $expiresAt = date('Y-m-d H:i:s', strtotime('+3 minutes'));

            $bookingId = $bookingModel->create([
                'booking_code'   => $bookingModel->generateBookingCode(),
                'user_id'        => Session::get('user_id'),
                'customer_name'  => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail,
                'court_id'       => $pending['court_id'],
                'branch_id'      => $pending['branch_id'],
                'booking_date'   => $pending['booking_date'],
                'start_time'     => sprintf('%02d:00:00', $pending['start_hour']),
                'end_time'       => sprintf('%02d:00:00', $pending['end_hour']),
                'duration_hours' => $pending['end_hour'] - $pending['start_hour'],
                'total_price'    => $pending['total_price'],
                'status'         => 'pending',
                'expires_at'     => $expiresAt,
                'notes'          => $notes,
            ]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            Session::set('pending_booking', null);
            Session::flash('error', 'Có lỗi xảy ra khi đặt sân. Vui lòng thử lại.');
            $this->redirect('/branches');
        }

        Session::set('pending_booking', null);
        $this->redirect("/payment/{$bookingId}");
    }

    // GET /payment/{bookingId}  — delegated to PaymentController, but also accessible here
    // (see PaymentController)

    // GET /booking/success/{bookingId}
    public function success($bookingId)
    {
        if (!Session::has('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập để xem đơn đặt sân.');
            $this->redirect('/login');
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->findById($bookingId);

        if (!$booking) {
            $this->redirect('/');
        }

        // Kiểm tra quyền sở hữu: user chỉ xem được đơn của mình
        if ((int)($booking['user_id'] ?? 0) !== (int)Session::get('user_id')) {
            Session::flash('error', 'Bạn không có quyền xem đơn này.');
            $this->redirect('/booking/history');
        }

        $paymentModel = new Payment();
        $payments = $paymentModel->findByBooking($bookingId);
        $payment  = !empty($payments) ? end($payments) : null;

        $this->render('bookings/success', [
            'title'   => 'Đặt Sân Thành Công - SmashZone',
            'booking' => $booking,
            'payment' => $payment,
        ]);
    }

    // GET /booking/detail/{id}
    public function detail($id)
    {
        if (!Session::has('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập để xem chi tiết đơn đặt sân.');
            $this->redirect('/login');
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->findDetailed($id);

        if (!$booking) {
            Session::flash('error', 'Không tìm thấy đặt sân.');
            $this->redirect('/history');
        }

        // Kiểm tra quyền sở hữu: user chỉ xem được đơn của mình
        if ((int)($booking['user_id'] ?? 0) !== (int)Session::get('user_id')) {
            Session::flash('error', 'Bạn không có quyền xem đơn này.');
            $this->redirect('/booking/history');
        }

        $paymentModel = new Payment();
        $payments = $paymentModel->findByBooking($id);
        $payment  = !empty($payments) ? end($payments) : null;

        $this->render('bookings/detail', [
            'title'   => 'Chi Tiết Đặt Sân - SmashZone',
            'booking' => $booking,
            'payment' => $payment,
        ]);
    }

    // GET /booking/history
    public function history()
    {
        $bookings = [];
        $bookingModel = new Booking();

        $userId = Session::get('user_id');
        $phone  = $_GET['phone'] ?? '';

        if ($userId) {
            $bookings = $bookingModel->findByUser($userId);
        } elseif (!empty($phone)) {
            $bookings = $bookingModel->findByPhone($phone);
        }

        $this->render('bookings/history', [
            'title'    => 'Lịch Sử Đặt Sân - SmashZone',
            'bookings' => $bookings,
            'phone'    => $phone,
        ]);
    }

    // GET /api/available-slots?court_id=&date=
    public function availableSlots()
    {
        $courtId = $_GET['court_id'] ?? null;
        $date    = $_GET['date'] ?? date('Y-m-d');

        if (!$courtId) {
            $this->json(['error' => 'court_id required'], 400);
        }

        $bookingModel = new Booking();
        $bookedHours  = $bookingModel->getBookedSlots($courtId, $date);

        $slots = [];
        for ($h = 5; $h < 22; $h++) {
            $slots[] = [
                'hour'   => $h,
                'label'  => sprintf('%02d:00', $h),
                'booked' => in_array($h, $bookedHours),
            ];
        }

        $this->json(['slots' => $slots, 'date' => $date, 'court_id' => $courtId]);
    }
}
