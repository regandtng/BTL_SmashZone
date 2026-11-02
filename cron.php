<?php
/**
 * Cron job script for SmashZone
 * 
 * Chạy định kỳ (5-10 phút/lần) qua Windows Task Scheduler hoặc cron Linux
 * 
 * Usage:
 *   php cron.php
 *   php cron.php --auto-complete
 *   php cron.php --clean-pending
 *   php cron.php --all
 */

// Simple bootstrap without composer
$dotenvPath = __DIR__ . '/.env';
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

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_DATABASE'] ?? 'badminton_booking';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $argv[1] ?? '--all';

function logMsg(string $msg) {
    echo "[" . date('Y-m-d H:i:s') . "] $msg\n";
}

try {
    if ($action === '--auto-complete' || $action === '--all') {
        // Auto-complete confirmed bookings where end_time has passed
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'completed', completed_at = NOW() 
            WHERE status = 'confirmed' 
            AND CONCAT(booking_date, ' ', end_time) < NOW()
            AND completed_at IS NULL
        ");
        $stmt->execute();
        $count = $stmt->rowCount();
        logMsg("Auto-completed $count ended sessions");
    }

    if ($action === '--clean-pending' || $action === '--all') {
        // Clean up expired pending bookings (backup)
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'cancelled',
                notes = CONCAT(COALESCE(notes, ''), ' | Auto cancel: quá hạn thanh toán')
            WHERE status = 'pending' 
            AND expires_at IS NOT NULL 
            AND expires_at < NOW()
        ");
        $stmt->execute();
        $count = $stmt->rowCount();
        logMsg("Cleaned $count expired pending bookings");
    }

    logMsg("Done.");
    exit(0);

} catch (Exception $e) {
    logMsg("ERROR: " . $e->getMessage());
    exit(1);
}