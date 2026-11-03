<?php
// tests/helpers.php
// Hàm trợ giúp chung cho tất cả unit test

$GLOBALS['TEST_PASS'] = 0;
$GLOBALS['TEST_FAIL'] = 0;
$GLOBALS['TEST_SKIP'] = 0;
$GLOBALS['TEST_FILTER'] = trim((string) getenv('TEST_FILTER'));

if (!function_exists('test')) {
    function test($name, $condition) {
        $filter = $GLOBALS['TEST_FILTER'] ?? '';
        if ($filter !== '' && stripos($name, $filter) === false) {
            echo "  [SKIP] $name\n";
            $GLOBALS['TEST_SKIP']++;
            return;
        }

        if ($condition) {
            echo "  [PASS] $name\n";
            $GLOBALS['TEST_PASS']++;
        } else {
            echo "  [FAIL] $name\n";
            $GLOBALS['TEST_FAIL']++;
        }
    }
}

if (!function_exists('test_results')) {
    function test_results() {
        return [
            'pass' => $GLOBALS['TEST_PASS'] ?? 0,
            'fail' => $GLOBALS['TEST_FAIL'] ?? 0,
            'skip' => $GLOBALS['TEST_SKIP'] ?? 0,
        ];
    }
}

if (!function_exists('test_reset')) {
    function test_reset() {
        $GLOBALS['TEST_PASS'] = 0;
        $GLOBALS['TEST_FAIL'] = 0;
        $GLOBALS['TEST_SKIP'] = 0;
    }
}

if (!function_exists('test_unique')) {
    function test_unique(string $prefix = 'TEST'): string {
        return $prefix . '-' . strtoupper(bin2hex(random_bytes(6)));
    }
}
