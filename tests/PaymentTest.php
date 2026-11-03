<?php
// tests/PaymentTest.php
// Integration test cho Payment model: tạo, tra cứu và đánh dấu thanh toán.

require_once __DIR__ . '/bootstrap.php';

use App\Models\Payment;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- PaymentTest ---\n";

$paymentModel = new Payment();
$db = \Core\Database::getInstance();

$bookingCode = test_unique('PAYMENTTEST');
$bookingId = null;

try {
    $db->query(
        "INSERT INTO bookings
            (booking_code, user_id, customer_name, customer_phone, court_id, branch_id,
             booking_date, start_time, end_time, duration_hours, total_price, status)
         VALUES (?, NULL, 'Payment Fixture', '0900000000', 1, 1, '2099-12-20', '09:00:00', '10:00:00', 1, 150000, 'confirmed')",
        [$bookingCode]
    );
    $bookingId = (int) $db->getConnection()->lastInsertId();

    test('findByBooking trả mảng rỗng khi chưa có thanh toán',
        $paymentModel->findByBooking($bookingId) === []);

    $paymentId = $paymentModel->createPayment([
        'booking_id' => $bookingId,
        'method' => 'cash',
        'amount' => 150000,
    ]);

    test('createPayment trả về id hợp lệ', (int) $paymentId > 0);

    $payments = $paymentModel->findByBooking($bookingId);
    test('createPayment lưu đúng booking, phương thức và số tiền',
        count($payments) === 1
        && (int) $payments[0]['booking_id'] === $bookingId
        && $payments[0]['method'] === 'cash'
        && (int) $payments[0]['amount'] === 150000);

    test('createPayment mặc định status = pending',
        $payments[0]['status'] === 'pending');

    $marked = $paymentModel->markAsPaid((int) $paymentId, 'TXN-TEST-001');
    test('markAsPaid trả về true', $marked === true);

    $paid = $paymentModel->findById((int) $paymentId);
    test('markAsPaid cập nhật status, transaction_id và paid_at',
        $paid['status'] === 'paid'
        && $paid['transaction_id'] === 'TXN-TEST-001'
        && !empty($paid['paid_at']));
} finally {
    if ($bookingId !== null) {
        $db->query("DELETE FROM bookings WHERE id = ?", [$bookingId]);
    }
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
