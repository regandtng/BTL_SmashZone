<?php
// tests/setup_test_db.php
// Tạo database kiểm thử riêng và import schema/seed.
// Chạy: php tests/setup_test_db.php [--fresh]
// An toàn: chỉ thao tác trên database có hậu tố _test, không đụng DB ứng dụng.

const TEST_DB_NAME = 'badminton_booking_test';
const SCHEMA_FILE = __DIR__ . '/../database/schema.sql';

$root = dirname(__DIR__);
$envPath = $root . '/.env';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

if (!str_ends_with(TEST_DB_NAME, '_test')) {
    fwrite(STDERR, "Từ chối thao tác: database test phải có hậu tố _test.\n");
    exit(1);
}

if (!file_exists(SCHEMA_FILE)) {
    fwrite(STDERR, "Không tìm thấy schema: " . SCHEMA_FILE . "\n");
    exit(1);
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$fresh = in_array('--fresh', $argv, true);

echo "=== SmashZone Test DB Setup ===\n";
echo "Host: {$host}:{$port}\n";
echo "Database: " . TEST_DB_NAME . "\n";
echo "Chế độ: " . ($fresh ? 'tạo mới (xóa dữ liệu cũ)' : 'giữ nguyên nếu đã tồn tại') . "\n\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    if ($fresh) {
        $pdo->exec("DROP DATABASE IF EXISTS `" . TEST_DB_NAME . "`");
        echo "[OK] Đã xóa database cũ.\n";
    }

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . TEST_DB_NAME . "` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
    echo "[OK] Đã tạo database.\n";

    $pdo->exec("USE `" . TEST_DB_NAME . "`");

    $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $shouldImport = $fresh || empty($existingTables);

    if ($shouldImport) {
        $schema = file_get_contents(SCHEMA_FILE);
        $statements = array_filter(
            array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema)),
            static fn(string $sql): bool => $sql !== ''
        );

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        echo "[OK] Đã import schema + seed (" . count($statements) . " câu lệnh).\n";
    } else {
        echo "[SKIP] Database đã có bảng, không import lại. Dùng --fresh để tạo sạch.\n";
    }

    $counts = [
        'branches'      => (int) $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn(),
        'courts'        => (int) $pdo->query("SELECT COUNT(*) FROM courts")->fetchColumn(),
        'pricing_rules' => (int) $pdo->query("SELECT COUNT(*) FROM pricing_rules")->fetchColumn(),
        'owners'        => (int) $pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn(),
    ];

    echo "\nDữ liệu seed:\n";
    foreach ($counts as $table => $count) {
        echo "  - {$table}: {$count}\n";
    }

    echo "\nSẵn sàng. Chạy test bằng: php tests/run_tests.php\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
