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
