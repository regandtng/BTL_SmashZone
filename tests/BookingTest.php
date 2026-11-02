<?php
// tests/BookingTest.php
// Unit test cho Booking model (isSlotAvailable, generateBookingCode, cleanExpiredPending)

require_once __DIR__ . '/bootstrap.php';

use App\Models\Booking;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- BookingTest ---\n";

$booking = new Booking();

// Test 1: isSlotAvailable trả về true cho khung giờ trống (giả sử court_id=999 không tồn tại)
$courtId = 999;
$date    = '2026-11-05';
$result  = $booking->isSlotAvailable($courtId, $date, 9, 11);
test('isSlotAvailable trả về true cho sân không có booking', $result === true);

// Test 4: isSlotAvailable trả về false khi khung giờ đã bị đặt (chồng lấn)
$db = \Core\Database::getInstance();
try {
    $db->query("DELETE FROM bookings WHERE booking_code = 'TEST-CONFLICT-001'");
    $db->query(
        "INSERT INTO bookings (booking_code, user_id, customer_name, customer_phone, court_id, branch_id, booking_date, start_time, end_time, duration_hours, total_price, status)
         VALUES ('TEST-CONFLICT-001', NULL, 'Test Conflict', '0900000000', 1, 1, '2026-11-05', '09:00:00', '11:00:00', 2.0, 300000, 'confirmed')"
    );
    $insertOk = true;
} catch (\Exception $e) {
    $insertOk = false;
    echo "  [DEBUG] Insert conflict booking failed: " . $e->getMessage() . "\n";
}
if ($insertOk) {
    $resultConflict = $booking->isSlotAvailable(1, '2026-11-05', 9, 11);
    test('isSlotAvailable trả về false khi khung giờ đã bị đặt', $resultConflict === false);
    // Dọn dẹp booking test sau khi kiểm tra
    $db->query("DELETE FROM bookings WHERE booking_code = 'TEST-CONFLICT-001'");
} else {
    test('isSlotAvailable trả về false khi khung giờ đã bị đặt', false);
    echo "  [DEBUG] Bỏ qua test do không insert được booking conflict\n";
}

// Test 5: isSlotAvailable trả về true khi khung giờ không chồng lấn (sau giờ đã đặt)
$resultAfter = $booking->isSlotAvailable(1, '2026-11-05', 11, 13);
test('isSlotAvailable trả về true khi khung giờ không chồng lấn', $resultAfter === true);

// Test pending booking expiry is calculated using the database clock.
$pendingBookingCode = 'TEST-PENDING-EXP-001';
$db->query("DELETE FROM bookings WHERE booking_code = ?", [$pendingBookingCode]);
try {
    $pendingBookingId = $booking->createPending([
        'booking_code'   => $pendingBookingCode,
        'user_id'        => null,
        'customer_name'  => 'Test Pending Expiry',
        'customer_phone' => '0900000000',
        'court_id'       => 1,
        'branch_id'      => 1,
        'booking_date'   => '2099-12-02',
        'start_time'     => '09:00:00',
        'end_time'       => '10:00:00',
        'duration_hours' => 1,
        'total_price'    => 150000,
        'status'         => 'pending',
        'notes'          => null,
    ]);

    $remainingSeconds = $booking->getPendingSecondsRemaining($pendingBookingId);
    test('booking mới tạo có thời hạn gần đủ 3 phút theo giờ MySQL', $remainingSeconds >= 175 && $remainingSeconds <= 180);
} finally {
    $db->query("DELETE FROM bookings WHERE booking_code = ?", [$pendingBookingCode]);
}

// Test expired pending bookings are cancelled and no longer reserve their slots.
$expiredBookingCode = 'TEST-EXPIRED-001';
$expiredBookingDate = '2099-12-01';
$db->query("DELETE FROM bookings WHERE booking_code = ?", [$expiredBookingCode]);
try {
    $db->query(
        "INSERT INTO bookings (booking_code, user_id, customer_name, customer_phone, court_id, branch_id, booking_date, start_time, end_time, duration_hours, total_price, status, expires_at)
         VALUES (?, NULL, 'Test Expired', '0900000000', 1, 1, ?, '09:00:00', '11:00:00', 2.0, 300000, 'pending', DATE_SUB(NOW(), INTERVAL 1 MINUTE))",
        [$expiredBookingCode, $expiredBookingDate]
    );

    $availableAfterExpiry = $booking->isSlotAvailable(1, $expiredBookingDate, 9, 11);
    $expiredSlots = $booking->getBookedSlots(1, $expiredBookingDate);
    $expiredBooking = $db->query(
        "SELECT status FROM bookings WHERE booking_code = ?",
        [$expiredBookingCode]
    )->fetch(\PDO::FETCH_ASSOC);

    test('booking pending hết hạn được hủy và không còn khóa khung giờ', $availableAfterExpiry && !in_array(9, $expiredSlots, true));
    test('trạng thái booking hết hạn được lưu là cancelled', $expiredBooking['status'] === 'cancelled');
} finally {
    $db->query("DELETE FROM bookings WHERE booking_code = ?", [$expiredBookingCode]);
}

// Test 2: generateBookingCode có định dạng BB-YYYYMMDD-XXXXX
$code = $booking->generateBookingCode();
$pattern = '/^BB-\d{8}-[A-Z0-9]{5}$/';
test('generateBookingCode đúng định dạng BB-YYYYMMDD-XXXXX', (bool) preg_match($pattern, $code));

// Test 3: generateBookingCode tạo mã duy nhất nhau nhau
$code2 = $booking->generateBookingCode();
test('generateBookingCode tạo mã duy nhất (không trùng)', $code !== $code2);

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
