<?php
// tests/helpers.php
// Hàm trợ giúp chung cho tất cả unit test

$GLOBALS['TEST_PASS'] = 0;
$GLOBALS['TEST_FAIL'] = 0;

if (!function_exists('test')) {
    function test($name, $condition) {
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
        ];
    }
}

if (!function_exists('test_reset')) {
    function test_reset() {
        $GLOBALS['TEST_PASS'] = 0;
        $GLOBALS['TEST_FAIL'] = 0;
    }
}
