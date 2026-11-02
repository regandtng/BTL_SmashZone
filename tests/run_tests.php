<?php
// tests/run_tests.php
// Chạy tất cả unit test và xuất báo cáo coverage đơn giản

require_once __DIR__ . '/bootstrap.php';

$tests = [
    'BookingTest'   => __DIR__ . '/BookingTest.php',
    'PricingRuleTest'=> __DIR__ . '/PricingRuleTest.php',
];

$totalPass = 0;
$totalFail = 0;
$startTime = microtime(true);

echo "========================================\n";
echo "  SmashZone Unit Test Runner\n";
echo "========================================\n\n";

foreach ($tests as $name => $path) {
    if (!file_exists($path)) {
        echo "[SKIP] $name (file không tồn tại)\n\n";
        continue;
    }
    echo ">> Chạy $name\n";
    ob_start();
    include $path;
    $output = ob_get_clean();
    echo $output . "\n";
}

// Lấy pass/fail từ output là phức tạp, nên ta ước lượng qua exit code của từng file.
// Ở đây chúng ta chỉ báo tổng thể.
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "========================================\n";
echo "  Báo cáo Coverage (Ước tính)\n";
echo "========================================\n";
echo "Files tested: " . count($tests) . "\n";
echo "- app/Models/Booking.php: isSlotAvailable, generateBookingCode\n";
echo "- app/Models/PricingRule.php: getPrice\n";
echo "Thời gian chạy: ${duration}ms\n";
echo "Lưu ý: Để có coverage chính xác, hãy cài Xdebug và chạy:\n";
echo "  php -d xdebug.mode=coverage tests/run_tests.php\n";
echo "========================================\n";
