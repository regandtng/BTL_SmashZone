<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Core\Session;
use DateTime;

class PaymentController extends Controller
{
    // GET /payment/{bookingId}
    public function show($bookingId)
    {
        $bookingModel = new Booking();
        $booking = $bookingModel->findById($bookingId);

        if (!$booking) {
            Session::flash('error', 'Không tìm thấy thông tin đặt sân.');
            $this->redirect('/');
        }

        if ($booking['status'] === 'confirmed') {
            $this->redirect("/booking/success/{$bookingId}");
        }

        // Kiểm tra booking pending đã hết hạn (3 phút)
        if ($booking['status'] === 'pending' && $booking['expires_at']) {
            $now = new DateTime();
            $expires = new DateTime($booking['expires_at']);
            if ($now >= $expires) {
                // Auto cancel booking hết hạn
                $bookingModel->update($bookingId, [
                    'status' => 'cancelled',
                    'notes' => ($booking['notes'] ? $booking['notes'] . ' | ' : '') . 'Auto cancel: quá hạn thanh toán 3 phút'
                ]);
                Session::set('pending_booking', null);
                Session::flash('error', 'Đã quá 3 phút thanh toán. Đơn đặt sân đã bị hủy tự động, sân đã được giải phóng.');
                $this->redirect('/branches');
            }
        }

        $paymentMethods = [
            ['id' => 'qr',   'label' => 'Chuyển khoản QR',  'icon' => '🏦', 'desc' => 'Quét QR qua app ngân hàng'],
            ['id' => 'momo', 'label' => 'Ví MoMo',           'icon' => '💜', 'desc' => 'Thanh toán qua ví MoMo'],
            ['id' => 'cash', 'label' => 'Tiền mặt tại sân',  'icon' => '💵', 'desc' => 'Thanh toán khi đến sân'],
        ];

        $this->render('bookings/payment', [
            'title'          => 'Thanh Toán - SmashZone',
            'booking'        => $booking,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    // POST /payment/process
    public function process()
    {
        $this->csrfProtect();

        $bookingId = $_POST['booking_id'] ?? null;
        $method    = $_POST['payment_method'] ?? null;

        $allowedMethods = ['qr', 'momo', 'cash'];
        if (!$bookingId || !in_array($method, $allowedMethods)) {
            Session::flash('error', 'Thông tin thanh toán không hợp lệ.');
            $this->redirect('/');
        }

        $bookingModel = new Booking();
        $paymentModel = new Payment();

        $booking = $bookingModel->findById($bookingId);
        if (!$booking) {
            Session::flash('error', 'Không tìm thấy đặt sân.');
            $this->redirect('/');
        }

        // Kiểm tra booking pending đã hết hạn
        if ($booking['status'] === 'pending' && $booking['expires_at']) {
            $now = new DateTime();
            $expires = new DateTime($booking['expires_at']);
            if ($now >= $expires) {
                $bookingModel->update($bookingId, [
                    'status' => 'cancelled',
                    'notes' => ($booking['notes'] ? $booking['notes'] . ' | ' : '') . 'Auto cancel: quá hạn thanh toán 3 phút'
                ]);
                Session::flash('error', 'Đã quá 3 phút thanh toán. Đơn đặt sân đã bị hủy tự động.');
                $this->redirect('/branches');
            }
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
