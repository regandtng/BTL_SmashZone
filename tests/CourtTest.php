<?php
// tests/CourtTest.php
// Integration test cho Court model: lọc sân theo chi nhánh, tìm sân trống,
// lưới khả dụng theo giờ.

require_once __DIR__ . '/bootstrap.php';

use App\Models\Court;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- CourtTest ---\n";

$courtModel = new Court();
$db = \Core\Database::getInstance();

$date = '2099-12-10';
$codes = [];

try {
    $insertCourt = static function (int $branchId, string $name, string $status) use ($db): int {
        $db->query(
            "INSERT INTO courts (branch_id, name, type, status) VALUES (?, ?, 'standard', ?)",
            [$branchId, $name, $status]
        );
        return (int) $db->getConnection()->lastInsertId();
    };

    $activeCourtId = $insertCourt(1, 'ZZ-TEST-ACTIVE', 'active');
    $maintenanceCourtId = $insertCourt(1, 'ZZ-TEST-MAINT', 'maintenance');
    $expiredCourtId = $insertCourt(1, 'ZZ-TEST-EXPIRED', 'active');

    $insertBooking = static function (string $code, int $courtId, string $status, string $expiresAt = null) use ($db, $date, &$codes): void {
        $codes[] = $code;
        $db->query(
            "INSERT INTO bookings
                (booking_code, user_id, customer_name, customer_phone, court_id, branch_id,
                 booking_date, start_time, end_time, duration_hours, total_price, status, expires_at)
             VALUES (?, NULL, 'Court Fixture', '0900000000', ?, 1, ?, '09:00:00', '11:00:00', 2, 300000, ?, " .
            ($expiresAt === null ? 'NULL' : $expiresAt) . ")",
            [$code, $courtId, $date, $status]
        );
    };

    $insertBooking('TEST-COURT-CONFIRMED', $activeCourtId, 'confirmed');
    $insertBooking('TEST-COURT-PENDING', 2, 'pending', 'DATE_ADD(NOW(), INTERVAL 3 MINUTE)');
    $insertBooking('TEST-COURT-EXPIRED', $expiredCourtId, 'pending', 'DATE_SUB(NOW(), INTERVAL 1 MINUTE)');

    $courts = $courtModel->findByBranch(1);
    $courtIds = array_column($courts, 'id');
    test('findByBranch chỉ trả sân active',
        in_array($activeCourtId, $courtIds, true) && !in_array($maintenanceCourtId, $courtIds, true));

    $available = $courtModel->findAvailable(1, $date, 9, 11);
    $availableIds = array_column($available, 'id');
    test('findAvailable loại sân đã có booking confirmed trùng giờ',
        !in_array($activeCourtId, $availableIds, true));
    test('findAvailable loại sân đã có booking pending còn hạn trùng giờ',
        !in_array(2, $availableIds, true));
    test('findAvailable giữ sân có pending đã hết hạn',
        in_array($expiredCourtId, $availableIds, true));

    $availableBefore = array_column($courtModel->findAvailable(1, $date, 7, 9), 'id');
    $availableAfter = array_column($courtModel->findAvailable(1, $date, 11, 13), 'id');
    test('findAvailable giữ sân trống ở khung sát trước [7,9)',
        in_array($activeCourtId, $availableBefore, true));
    test('findAvailable giữ sân trống ở khung sát sau [11,13)',
        in_array($activeCourtId, $availableAfter, true));

    $grid = $courtModel->getAvailabilityGrid(1, $date);
    test('getAvailabilityGrid có đủ sân active của chi nhánh',
        count($grid) === count($courts));

    test('getAvailabilityGrid đánh dấu giờ bị đặt là không khả dụng',
        $grid[$activeCourtId]['slots']['09:00:00'] === false
        && $grid[$activeCourtId]['slots']['10:00:00'] === false);

    test('getAvailabilityGrid giữ giờ sát sau booking là khả dụng',
        $grid[$activeCourtId]['slots']['11:00:00'] === true);

    test('getAvailabilityGrid bỏ qua pending đã hết hạn',
        $grid[$expiredCourtId]['slots']['09:00:00'] === true);
} finally {
    foreach ($codes as $code) {
        $db->query("DELETE FROM bookings WHERE booking_code = ?", [$code]);
    }
    if (isset($activeCourtId)) {
        $db->query("DELETE FROM courts WHERE id = ?", [$activeCourtId]);
    }
    if (isset($maintenanceCourtId)) {
        $db->query("DELETE FROM courts WHERE id = ?", [$maintenanceCourtId]);
    }
    if (isset($expiredCourtId)) {
        $db->query("DELETE FROM courts WHERE id = ?", [$expiredCourtId]);
    }
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
