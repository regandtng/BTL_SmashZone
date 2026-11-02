<?php
// tests/bootstrap.php
// Khởi tạo môi trường test cho SmashZone (PHP thuần, không Composer)

define('ROOT', __DIR__ . '/..');
define('TEST_DB', 'badminton_booking_test');

// Nạp .env nếu có
$dotenvPath = ROOT . '/.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Never let tests write to the application database configured in .env.
$_ENV['DB_DATABASE'] = TEST_DB;
putenv('DB_DATABASE=' . TEST_DB);

// Require core classes
require_once ROOT . '/core/Database.php';
require_once ROOT . '/core/Model.php';
require_once ROOT . '/app/Models/Booking.php';
require_once ROOT . '/app/Models/PricingRule.php';
require_once ROOT . '/app/Models/Branch.php';
require_once ROOT . '/app/Models/Court.php';
require_once ROOT . '/app/Models/User.php';
require_once ROOT . '/app/Models/Payment.php';

use Core\Database;
use App\Models\Booking;
use App\Models\PricingRule;

echo "=== SmashZone Test Bootstrap ===\n";
echo "Database host: " . ($_ENV['DB_HOST'] ?? 'localhost') . "\n";
echo "Database name: " . TEST_DB . "\n\n";
