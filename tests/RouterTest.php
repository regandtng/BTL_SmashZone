<?php
// tests/RouterTest.php
// Unit test cho Core\Router: khớp route tĩnh, route động và dấu slash cuối.

require_once __DIR__ . '/bootstrap.php';

require_once ROOT . '/core/Router.php';

use Core\Router;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- RouterTest ---\n";

$originalServer = $_SERVER;

try {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $router = new Router();

    $router->get('/test/static', static function () {
        $GLOBALS['route_hit'] = 'static';
    });
    $router->get('/test/detail/{id}', static function ($id) {
        $GLOBALS['route_hit'] = 'detail';
        $GLOBALS['route_id'] = $id;
    });

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test/static';
    $router->dispatch();
    test('Router khớp route tĩnh',
        ($GLOBALS['route_hit'] ?? null) === 'static');

    $GLOBALS['route_hit'] = null;
    $_SERVER['REQUEST_URI'] = '/test/detail/abc-123';
    $router->dispatch();
    test('Router khớp route động và truyền tham số',
        ($GLOBALS['route_hit'] ?? null) === 'detail'
        && ($GLOBALS['route_id'] ?? null) === 'abc-123');

    $GLOBALS['route_hit'] = null;
    $_SERVER['REQUEST_URI'] = '/test/static/';
    $router->dispatch();
    test('Router chấp nhận dấu slash cuối',
        ($GLOBALS['route_hit'] ?? null) === 'static');

    $_SERVER['REQUEST_URI'] = '/test/detail/xyz_9';
    $router->dispatch();
    test('Router cho phép chữ, số, gạch dưới trong tham số',
        ($GLOBALS['route_id'] ?? null) === 'xyz_9');
} finally {
    $_SERVER = $originalServer;
    unset($GLOBALS['route_hit'], $GLOBALS['route_id']);
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
