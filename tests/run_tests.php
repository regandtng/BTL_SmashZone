<?php
// tests/run_tests.php
// Chạy tất cả unit test, kiểm tra exit code và tổng hợp kết quả.

require_once __DIR__ . '/bootstrap.php';

$tests = [
    'BookingTest'     => __DIR__ . '/BookingTest.php',
    'PricingRuleTest' => __DIR__ . '/PricingRuleTest.php',
    'BranchTest'      => __DIR__ . '/BranchTest.php',
    'CourtTest'       => __DIR__ . '/CourtTest.php',
    'UserTest'        => __DIR__ . '/UserTest.php',
    'PaymentTest'     => __DIR__ . '/PaymentTest.php',
    'SessionTest'     => __DIR__ . '/SessionTest.php',
    'RouterTest'      => __DIR__ . '/RouterTest.php',
];

$filter = trim((string) getenv('TEST_FILTER'));

$requested = array_slice($argv, 1);
if (!empty($requested)) {
    $selected = [];
    foreach ($requested as $arg) {
        $key = str_ireplace(['tests/', 'tests\\', '.php'], '', trim($arg));
        $found = false;
        foreach ($tests as $name => $path) {
            if (strcasecmp($name, $key) === 0) {
                $selected[$name] = $path;
                $found = true;
                break;
            }
        }
        if (!$found) {
            fwrite(STDERR, "[ERROR] Không tìm thấy test '$arg'.\n");
            fwrite(STDERR, "Các file hiện có: " . implode(', ', array_keys($tests)) . "\n");
            exit(1);
        }
    }
    $tests = $selected;
}

// Preflight: database test phải sẵn sàng trước khi chạy bất kỳ file nào.
try {
    $dsn = 'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost')
        . ';port=' . ($_ENV['DB_PORT'] ?? '3306')
        . ';dbname=' . TEST_DB
        . ';charset=' . ($_ENV['DB_CHARSET'] ?? 'utf8mb4');
    new PDO($dsn, $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] Không kết nối được database test '" . TEST_DB . "': " . $e->getMessage() . "\n");
    fwrite(STDERR, "Chạy trước: php tests/setup_test_db.php\n");
    exit(1);
}

$totalPass = 0;
$totalFail = 0;
$totalSkip = 0;
$errors = [];
$startTime = microtime(true);

echo "========================================\n";
echo "  SmashZone Unit Test Runner\n";
echo "========================================\n";
echo "File chạy: " . implode(', ', array_keys($tests)) . "\n";
echo "Bộ lọc TEST_FILTER: " . ($filter !== '' ? $filter : '(không có)') . "\n\n";

foreach ($tests as $name => $path) {
    if (!file_exists($path)) {
        $errors[] = "$name: file không tồn tại ($path)";
        echo "[ERROR] $name (file không tồn tại)\n\n";
        continue;
    }

    echo ">> Chạy $name\n";

    // Chạy mỗi file trong một tiến trình riêng để tránh redeclare hàm/class.
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $phpBin = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $command = escapeshellarg($phpBin) . ' ' . escapeshellarg($path);
    $process = proc_open($command, $descriptorSpec, $pipes);

    if (!is_resource($process)) {
        $errors[] = "$name: không thể khởi chạy tiến trình";
        echo "[ERROR] Không thể chạy $path\n\n";
        continue;
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    echo $stdout;
    $totalSkip += substr_count($stdout, '  [SKIP] ');
    if ($stderr !== '') {
        echo "STDERR:\n$stderr";
    }

    $summaryMatches = [];
    preg_match_all('/^Kết quả:\s*(\d+) passed,\s*(\d+) failed\s*$/m', $stdout, $summaryMatches, PREG_SET_ORDER);
    $hasFatal = preg_match('/Fatal error|Uncaught (?:Error|Exception|Throwable)|Parse error/i', $stdout . $stderr) === 1;

    $errorCounted = false;
    $fileFail = 0;

    if (count($summaryMatches) !== 1) {
        $errors[] = "$name: thiếu hoặc có nhiều hơn một dòng 'Kết quả: X passed, Y failed' (exit code $exitCode)";
        $totalFail++;
        $errorCounted = true;
        echo "  [ERROR] Không đọc được dòng tổng kết duy nhất (exit code $exitCode).\n\n";
    } else {
        $filePass = (int) $summaryMatches[0][1];
        $fileFail = (int) $summaryMatches[0][2];
        $totalPass += $filePass;
        $totalFail += $fileFail;

        if ($exitCode !== 0) {
            $errors[] = "$name: exit code $exitCode dù báo $filePass passed, $fileFail failed";
            echo "  [ERROR] Exit code khác 0 (exit code $exitCode).\n";
            if (!$errorCounted && $fileFail === 0) {
                $totalFail++;
                $errorCounted = true;
            }
        }
    }

    if ($hasFatal) {
        $errors[] = "$name: output chứa fatal/parse error";
        echo "  [ERROR] Output chứa fatal/parse error.\n";
        if (!$errorCounted) {
            $totalFail++;
            $errorCounted = true;
        }
    }

    if ($stderr !== '') {
        $errors[] = "$name: ghi ra STDERR";
        echo "  [ERROR] Có output STDERR.\n";
        if (!$errorCounted) {
            $totalFail++;
            $errorCounted = true;
        }
    }

    echo "\n";
}

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "========================================\n";
echo "  Tổng Kết\n";
echo "========================================\n";
echo "Tổng passed: $totalPass\n";
echo "Tổng failed: $totalFail\n";
if ($filter !== '') {
    echo "Tổng skipped (không khớp TEST_FILTER): $totalSkip\n";
}
echo "Lỗi hạ tầng/runner: " . count($errors) . "\n";
echo "Thời gian chạy: {$duration}ms\n";

if (!empty($errors)) {
    echo "\nChi tiết lỗi:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nLưu ý: Để có báo cáo coverage chính xác, hãy cài Xdebug và chạy:\n";
echo "  php -d xdebug.mode=coverage tests/run_tests.php\n";
echo "========================================\n";

exit(($totalFail > 0 || !empty($errors)) ? 1 : 0);
