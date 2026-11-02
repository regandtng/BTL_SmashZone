<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Core\Session;

class PaymentController extends Controller
{
    // GET /payment/{bookingId}
    public function show($bookingId)
    {
        if (!Session::has('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập để xem đơn đặt sân.');
            $this->redirect('/login');
        }

        $bookingModel = new Booking();
        $bookingModel->cleanExpiredPending();
        $booking = $bookingModel->findById($bookingId);

        if (!$booking) {
            Session::flash('error', 'Không tìm thấy thông tin đặt sân.');
            $this->redirect('/');
        }

        // Kiểm tra quyền sở hữu: user chỉ xem được đơn của mình
        if ((int)($booking['user_id'] ?? 0) !== (int)Session::get('user_id')) {
            Session::flash('error', 'Bạn không có quyền xem đơn đặt sân này.');
            $this->redirect('/booking/history');
        }

        if ($booking['status'] === 'confirmed') {
            $this->redirect("/booking/success/{$bookingId}");
        }

        if ($booking['status'] !== 'pending') {
            Session::set('pending_booking', null);
            Session::flash('error', 'Đơn đặt sân đã hết hạn hoặc đã bị hủy. Vui lòng chọn sân và đặt lại.');
            $this->redirect('/branches');
        }

        $remainingSeconds = $bookingModel->getPendingSecondsRemaining($bookingId);

        $paymentMethods = [
            ['id' => 'qr',   'label' => 'Chuyển khoản QR',  'icon' => '🏦', 'desc' => 'Quét QR qua app ngân hàng'],
            ['id' => 'momo', 'label' => 'Ví MoMo',           'icon' => '💜', 'desc' => 'Thanh toán qua ví MoMo'],
            ['id' => 'cash', 'label' => 'Tiền mặt tại sân',  'icon' => '💵', 'desc' => 'Thanh toán khi đến sân'],
        ];

        $this->render('bookings/payment', [
            'title'          => 'Thanh Toán - SmashZone',
            'booking'        => $booking,
            'remainingSeconds' => $remainingSeconds,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    // POST /payment/process
    public function process()
    {
        if (!Session::has('user_id')) {
            Session::flash('error', 'Vui lòng đăng nhập trước khi thanh toán.');
            $this->redirect('/login');
        }

        $this->csrfProtect();

        $bookingId = $_POST['booking_id'] ?? null;
        $method    = $_POST['payment_method'] ?? null;

        $allowedMethods = ['qr', 'momo', 'cash'];
        if (!$bookingId || !in_array($method, $allowedMethods)) {
            Session::flash('error', 'Thông tin thanh toán không hợp lệ.');
            $this->redirect('/');
        }

        $bookingModel = new Booking();
        $bookingModel->cleanExpiredPending();
        $paymentModel = new Payment();

        $booking = $bookingModel->findById($bookingId);
        if (!$booking) {
            Session::flash('error', 'Không tìm thấy đặt sân.');
            $this->redirect('/');
        }

        // Kiểm tra quyền sở hữu: user chỉ thanh toán được đơn của mình
        if ((int)($booking['user_id'] ?? 0) !== (int)Session::get('user_id')) {
            Session::flash('error', 'Bạn không có quyền thanh toán đơn này.');
            $this->redirect('/booking/history');
        }

        if ($booking['status'] === 'confirmed') {
            $this->redirect("/booking/success/{$bookingId}");
        }

        if ($booking['status'] !== 'pending') {
            Session::set('pending_booking', null);
            Session::flash('error', 'Đơn đặt sân đã hết hạn hoặc đã bị hủy. Vui lòng chọn sân và đặt lại.');
            $this->redirect('/branches');
        }

        // Create payment record
        $transactionId = 'TXN-' . strtoupper(uniqid());
        $paymentModel->create([
            'booking_id'     => $bookingId,
            'method'         => $method,
            'amount'         => $booking['total_price'],
            'status'         => $method === 'cash' ? 'pending' : 'paid',
            'transaction_id' => $transactionId,
            'paid_at'        => $method !== 'cash' ? date('Y-m-d H:i:s') : null,
        ]);

        // Update booking status (slot is reserved in both cases)
        $bookingModel->update($bookingId, ['status' => 'confirmed']);

        if ($method === 'cash') {
            Session::flash('success', 'Đặt sân thành công! Vui lòng thanh toán tiền mặt tại sân khi đến nhận sân.');
        } else {
            Session::flash('success', 'Thanh toán thành công! Mã đặt sân của bạn đã được xác nhận.');
        }
        $this->redirect("/booking/success/{$bookingId}");
    }
}
