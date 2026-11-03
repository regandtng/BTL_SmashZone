<?php
// tests/UserTest.php
// Integration test cho User model: tạo tài khoản, hash mật khẩu, xác thực,
// tìm theo email/số điện thoại và ràng buộc email duy nhất.

require_once __DIR__ . '/bootstrap.php';

use App\Models\User;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- UserTest ---\n";

$userModel = new User();
$db = \Core\Database::getInstance();

$token = strtolower(test_unique('user'));
$email = $token . '@example.test';
$phone = '09' . random_int(10000000, 99999999);
$password = 'Secret123!';
$userId = null;

try {
    $userId = $userModel->createUser([
        'name' => 'Test User',
        'email' => $email,
        'phone' => $phone,
        'password' => $password,
    ]);

    test('createUser trả về id hợp lệ', is_int($userId) && $userId > 0);

    $stored = $userModel->findByEmail($email);
    test('createUser lưu đúng email và số điện thoại',
        ($stored['email'] ?? '') === $email && ($stored['phone'] ?? '') === $phone);

    test('Mật khẩu được hash, không lưu plaintext',
        ($stored['password'] ?? '') !== $password
        && password_verify($password, $stored['password']));

    $authenticated = $userModel->authenticate($email, $password);
    test('authenticate đăng nhập đúng email/mật khẩu',
        is_array($authenticated) && (int) $authenticated['id'] === $userId);

    test('authenticate từ chối sai mật khẩu',
        $userModel->authenticate($email, 'WrongPassword!') === false);

    test('authenticate từ chối email không tồn tại',
        $userModel->authenticate('missing-' . $email, $password) === false);

    $byPhone = $userModel->findByPhone($phone);
    test('findByPhone tìm đúng người dùng',
        is_array($byPhone) && (int) $byPhone['id'] === $userId);

    $duplicateRejected = false;
    try {
        $db->getConnection()->prepare(
            "INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)"
        )->execute(['Duplicate', '0911111111', $email, password_hash('x', PASSWORD_DEFAULT)]);
    } catch (\PDOException $e) {
        $duplicateRejected = true;
    }
    test('Database từ chối email trùng (ràng buộc UNIQUE)', $duplicateRejected);
} finally {
    if ($userId !== null) {
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
