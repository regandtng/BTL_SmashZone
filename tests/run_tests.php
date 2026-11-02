<?php
// tests/run_tests.php
// Chạy tất cả unit test và xuất báo cáo tổng kết

require_once __DIR__ . '/bootstrap.php';

$tests = [
    'BookingTest'      => __DIR__ . '/BookingTest.php',
    'PricingRuleTest'  => __DIR__ . '/PricingRuleTest.php',
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
    // Chạy mỗi file trong một tiến trình riêng để tránh redeclare hàm
    $descriptorSpec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"],
    ];
    $phpBin = defined('PHP_BINARY') ? PHP_BINARY : 'php';
    $process = proc_open($phpBin . ' ' . escapeshellarg($path), $descriptorSpec, $pipes);
    if (is_resource($process)) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        $exitCode = proc_close($process);
        echo $stdout;
        if ($stderr) {
            echo "STDERR: $stderr\n";
        }
        // Parse pass/fail từ output
        preg_match('/Kết quả: (\d+) passed, (\d+) failed/', $stdout, $m);
        if (isset($m[1], $m[2])) {
            $totalPass += (int)$m[1];
            $totalFail += (int)$m[2];
        }
        echo "\n";
    } else {
        echo "[ERROR] Không thể chạy $path\n\n";
    }
}

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "========================================\n";
echo "  Tổng Kết\n";
echo "========================================\n";
echo "Tổng passed: $totalPass\n";
echo "Tổng failed: $totalFail\n";
echo "Thời gian chạy: ${duration}ms\n";
echo "\nLưu ý: Để có báo cáo coverage chính xác, hãy cài Xdebug và chạy:\n";
echo "  php -d xdebug.mode=coverage tests/run_tests.php\n";
echo "========================================\n";

exit($totalFail > 0 ? 1 : 0);
