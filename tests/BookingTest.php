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
