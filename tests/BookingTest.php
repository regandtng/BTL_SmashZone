<?php
// tests/BookingTest.php
// Integration test cho Booking model: khả dụng khung giờ, mã booking,
// hết hạn thanh toán và thống kê dashboard.

require_once __DIR__ . '/bootstrap.php';

use App\Models\Booking;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- BookingTest ---\n";

$booking = new Booking();
$db = \Core\Database::getInstance();

function booking_insert($db, array $overrides = []): string
{
    $data = array_merge([
        'booking_code'   => 'TEST-' . strtoupper(bin2hex(random_bytes(4))),
        'user_id'        => null,
        'customer_name'  => 'Test Fixture',
        'customer_phone' => '0900000000',
        'court_id'       => 1,
        'branch_id'      => 1,
        'booking_date'   => '2099-11-01',
        'start_time'     => '09:00:00',
        'end_time'       => '11:00:00',
        'duration_hours' => 2,
        'total_price'    => 300000,
        'status'         => 'confirmed',
    ], $overrides);

    $expiresAt = $data['expires_at'] ?? null;
    unset($data['expires_at']);

    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');

    if ($expiresAt !== null) {
        $columns[] = 'expires_at';
        $placeholders[] = $expiresAt;
    }

    $db->query(
        "INSERT INTO bookings (" . implode(', ', $columns) . ")
         VALUES (" . implode(', ', $placeholders) . ")",
        array_values($data)
    );

    return $overrides['booking_code'] ?? $data['booking_code'];
}

$cleanupCodes = [];
try {
    // ---------- Khả dụng khung giờ: 4 biên chồng lấn + khác sân/ngày ----------
    $overlapDate = '2099-11-01';
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-OVERLAP-001',
        'booking_date' => $overlapDate,
        'start_time'   => '09:00:00',
        'end_time'     => '11:00:00',
        'status'       => 'confirmed',
    ]);

    test('isSlotAvailable trả về true khi sân/ngày không có booking',
        $booking->isSlotAvailable(1, '2099-11-20', 9, 11) === true);

    test('isSlotAvailable trả về false khi trùng hoàn toàn [9,11) với [9,11)',
        $booking->isSlotAvailable(1, $overlapDate, 9, 11) === false);

    test('isSlotAvailable trả về false khi chồng lấn một phần [10,12) với [9,11)',
        $booking->isSlotAvailable(1, $overlapDate, 10, 12) === false);

    test('isSlotAvailable trả về true khi khung mới sát trước [7,9)',
        $booking->isSlotAvailable(1, $overlapDate, 7, 9) === true);

    test('isSlotAvailable trả về true khi khung mới sát sau [11,13)',
        $booking->isSlotAvailable(1, $overlapDate, 11, 13) === true);

    test('isSlotAvailable trả về true khi khác sân cùng ngày giờ',
        $booking->isSlotAvailable(2, $overlapDate, 9, 11) === true);

    test('isSlotAvailable trả về true khi cùng sân nhưng khác ngày',
        $booking->isSlotAvailable(1, '2099-11-02', 9, 11) === true);

    // ---------- Pending còn hạn chặn slot, pending hết hạn được dọn ----------
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-PENDING-ACTIVE',
        'booking_date' => '2099-11-03',
        'start_time'   => '09:00:00',
        'end_time'     => '11:00:00',
        'status'       => 'pending',
        'expires_at'   => 'DATE_ADD(NOW(), INTERVAL 3 MINUTE)',
    ]);
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-PENDING-EXPIRED',
        'booking_date' => '2099-11-04',
        'start_time'   => '09:00:00',
        'end_time'     => '11:00:00',
        'status'       => 'pending',
        'expires_at'   => 'DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
    ]);

    test('pending còn hạn vẫn khóa khung giờ',
        $booking->isSlotAvailable(1, '2099-11-03', 9, 11) === false);

    test('pending hết hạn không khóa khung giờ',
        $booking->isSlotAvailable(1, '2099-11-04', 9, 11) === true);

    $expired = $db->query(
        "SELECT status FROM bookings WHERE booking_code = 'TEST-PENDING-EXPIRED'"
    )->fetch(\PDO::FETCH_ASSOC);
    test('pending hết hạn được chuyển sang cancelled', ($expired['status'] ?? '') === 'cancelled');

    // ---------- getBookedSlots ----------
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-SLOTS-EXPIRED',
        'booking_date' => '2099-11-05',
        'start_time'   => '14:00:00',
        'end_time'     => '16:00:00',
        'status'       => 'pending',
        'expires_at'   => 'DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
    ]);
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-SLOTS-ACTIVE',
        'booking_date' => '2099-11-05',
        'start_time'   => '17:00:00',
        'end_time'     => '19:00:00',
        'status'       => 'pending',
        'expires_at'   => 'DATE_ADD(NOW(), INTERVAL 3 MINUTE)',
    ]);

    $slots = $booking->getBookedSlots(1, '2099-11-05');
    sort($slots);
    test('getBookedSlots chỉ trả các giờ bị khóa bởi booking còn hiệu lực',
        $slots === [17, 18]);

    // ---------- cleanExpiredPending không đụng booking còn hạn/confirmed ----------
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-CLEAN-VALID',
        'booking_date' => '2099-11-06',
        'status'       => 'pending',
        'expires_at'   => 'DATE_ADD(NOW(), INTERVAL 3 MINUTE)',
    ]);
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-CLEAN-CONFIRMED',
        'booking_date' => '2099-11-06',
        'status'       => 'confirmed',
    ]);
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-CLEAN-EXPIRED',
        'booking_date' => '2099-11-06',
        'status'       => 'pending',
        'expires_at'   => 'DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
    ]);

    $booking->cleanExpiredPending();

    $statuses = $db->query(
        "SELECT booking_code, status FROM bookings
         WHERE booking_code IN ('TEST-CLEAN-VALID', 'TEST-CLEAN-CONFIRMED', 'TEST-CLEAN-EXPIRED')"
    )->fetchAll(\PDO::FETCH_KEY_PAIR);

    test('cleanExpiredPending giữ nguyên pending còn hạn',
        ($statuses['TEST-CLEAN-VALID'] ?? '') === 'pending');
    test('cleanExpiredPending giữ nguyên booking confirmed',
        ($statuses['TEST-CLEAN-CONFIRMED'] ?? '') === 'confirmed');
    test('cleanExpiredPending hủy pending quá hạn',
        ($statuses['TEST-CLEAN-EXPIRED'] ?? '') === 'cancelled');

    // ---------- autoExpireEndedSessions ----------
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-ENDED-SESSION',
        'booking_date' => date('Y-m-d', strtotime('-1 day')),
        'start_time'   => '09:00:00',
        'end_time'     => '11:00:00',
        'status'       => 'confirmed',
    ]);

    $booking->autoExpireEndedSessions();
    $ended = $db->query(
        "SELECT status FROM bookings WHERE booking_code = 'TEST-ENDED-SESSION'"
    )->fetch(\PDO::FETCH_ASSOC);
    test('autoExpireEndedSessions hủy booking confirmed đã qua giờ chơi',
        ($ended['status'] ?? '') === 'cancelled');

    // ---------- createPending + getPendingSecondsRemaining theo giờ MySQL ----------
    $pendingCode = 'TEST-PENDING-EXP-001';
    $cleanupCodes[] = $pendingCode;
    $db->query("DELETE FROM bookings WHERE booking_code = ?", [$pendingCode]);

    $pendingBookingId = $booking->createPending([
        'booking_code'   => $pendingCode,
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
    test('booking mới tạo có thời hạn gần đủ 3 phút theo giờ MySQL',
        $remainingSeconds >= 175 && $remainingSeconds <= 180);

    // ---------- generateBookingCode ----------
    $code = $booking->generateBookingCode();
    test('generateBookingCode đúng định dạng BB-YYYYMMDD-XXXXX',
        preg_match('/^BB-\d{8}-[A-Z0-9]{5}$/', $code) === 1);

    $code2 = $booking->generateBookingCode();
    test('generateBookingCode tạo mã duy nhất', $code !== $code2);

    // ---------- getDashboardStats gộp theo chi nhánh ----------
    $before = $booking->getDashboardStats([1, 2]);
    $beforeBranch1 = $before[1] ?? ['total_bookings' => 0, 'total_revenue' => 0, 'today_bookings' => 0];
    $beforeBranch2 = $before[2] ?? ['total_bookings' => 0, 'total_revenue' => 0, 'today_bookings' => 0];

    $today = date('Y-m-d');
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-STATS-B1-CONFIRMED',
        'booking_date' => $today,
        'status'       => 'confirmed',
        'total_price'  => 111111,
    ]);
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-STATS-B1-CANCELLED',
        'booking_date' => $today,
        'status'       => 'cancelled',
        'total_price'  => 222222,
    ]);
    $cleanupCodes[] = booking_insert($db, [
        'booking_code' => 'TEST-STATS-B2-PENDING',
        'branch_id'    => 2,
        'booking_date' => $today,
        'status'       => 'pending',
        'total_price'  => 333333,
        'expires_at'   => 'DATE_ADD(NOW(), INTERVAL 3 MINUTE)',
    ]);

    $after = $booking->getDashboardStats([1, 2]);
    $afterBranch1 = $after[1] ?? ['total_bookings' => 0, 'total_revenue' => 0, 'today_bookings' => 0];
    $afterBranch2 = $after[2] ?? ['total_bookings' => 0, 'total_revenue' => 0, 'today_bookings' => 0];

    test('getDashboardStats đếm tổng booking theo chi nhánh',
        $afterBranch1['total_bookings'] - $beforeBranch1['total_bookings'] === 2
        && $afterBranch2['total_bookings'] - $beforeBranch2['total_bookings'] === 1);

    test('getDashboardStats chỉ cộng doanh thu booking confirmed',
        (int) $afterBranch1['total_revenue'] - (int) $beforeBranch1['total_revenue'] === 111111);

    test('getDashboardStats đếm booking confirmed trong ngày',
        $afterBranch1['today_bookings'] - $beforeBranch1['today_bookings'] === 1);
} finally {
    foreach (array_unique($cleanupCodes) as $code) {
        $db->query("DELETE FROM bookings WHERE booking_code = ?", [$code]);
    }
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
