<?php
// tests/SessionTest.php
// Unit test cho Core\Session: get/set/has/remove, flash dùng một lần và CSRF.

require_once __DIR__ . '/bootstrap.php';

require_once ROOT . '/core/Session.php';

use Core\Session;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- SessionTest ---\n";

$_SESSION = [];

try {
    Session::set('demo_key', 'demo_value');
    test('Session::get trả giá trị đã set',
        Session::get('demo_key') === 'demo_value');
    test('Session::get trả default khi key không tồn tại',
        Session::get('missing_key', 'fallback') === 'fallback');
    test('Session::has phát hiện key tồn tại', Session::has('demo_key') === true);

    Session::remove('demo_key');
    test('Session::remove xóa key',
        Session::has('demo_key') === false && Session::get('demo_key') === null);

    Session::flash('success', 'Đã lưu thành công');
    test('Session::getFlash trả thông báo một lần',
        Session::getFlash('success') === 'Đã lưu thành công'
        && Session::getFlash('success') === null);

    $token = Session::generateCsrfToken();
    test('CSRF token có 64 ký tự hex',
        strlen($token) === 64 && preg_match('/^[a-f0-9]{64}$/', $token) === 1);
    test('CSRF token ổn định trong cùng session',
        Session::generateCsrfToken() === $token);

    test('validateCsrfToken chấp nhận token đúng',
        Session::validateCsrfToken($token) === true);
    test('validateCsrfToken từ chối token sai',
        Session::validateCsrfToken(str_repeat('a', 64)) === false);
    test('validateCsrfToken từ chối token rỗng',
        Session::validateCsrfToken('') === false);

    $field = Session::csrfField();
    test('csrfField tạo hidden input chứa token',
        str_contains($field, 'name="csrf_token"')
        && str_contains($field, $token));
} finally {
    $_SESSION = [];
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
