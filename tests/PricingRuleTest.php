<?php
// tests/PricingRuleTest.php
// Integration test cho PricingRule model: giá theo ngày/khung giờ, override chi nhánh.

require_once __DIR__ . '/bootstrap.php';

use App\Models\PricingRule;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- PricingRuleTest ---\n";

$pricing = new PricingRule();
$db = \Core\Database::getInstance();

$weekday = '2026-11-02'; // Thứ Hai
$saturday = '2026-11-07';
$sunday = '2026-11-08';

test('Pricing weekday off-peak sáng [5,7) = 160000',
    $pricing->getPrice(1, $weekday, 5, 7) === 160000);

test('Pricing weekday peak sáng [8,10) = 300000',
    $pricing->getPrice(1, $weekday, 8, 10) === 300000);

test('Pricing weekday off-peak chiều [14,16) = 160000',
    $pricing->getPrice(1, $weekday, 14, 16) === 160000);

test('Pricing weekday peak tối [17,19) = 300000',
    $pricing->getPrice(1, $weekday, 17, 19) === 300000);

test('Pricing weekend Thứ Bảy [10,12) = 360000',
    $pricing->getPrice(1, $saturday, 10, 12) === 360000);

test('Pricing weekend Chủ Nhật [5,6) = 180000',
    $pricing->getPrice(1, $sunday, 5, 6) === 180000);

test('Pricing trả về số nguyên dương',
    is_int($pricing->getPrice(1, $weekday, 8, 10)) && $pricing->getPrice(1, $weekday, 8, 10) > 0);

$ruleId = null;
try {
    // Rule riêng của chi nhánh phải được ưu tiên hơn rule toàn hệ thống.
    $db->query(
        "INSERT INTO pricing_rules (branch_id, day_type, start_hour, end_hour, price_per_hour, label)
         VALUES (1, 'all', '05:00:00', '22:00:00', 123456, 'TEST-BRANCH-OVERRIDE')"
    );
    $ruleId = (int) $db->getConnection()->lastInsertId();

    test('Rule riêng của chi nhánh được ưu tiên',
        $pricing->getPrice(1, $weekday, 5, 6) === 123456);

    test('Chi nhánh khác vẫn dùng bảng giá toàn hệ thống',
        $pricing->getPrice(2, $weekday, 5, 6) === 80000);

    $branchRules = $pricing->getForBranch(1);
    $labels = array_column($branchRules, 'label');
    test('getForBranch trả cả rule toàn hệ thống và rule riêng chi nhánh',
        in_array('TEST-BRANCH-OVERRIDE', $labels, true)
        && count($branchRules) >= 6);
} finally {
    if ($ruleId !== null) {
        $db->query("DELETE FROM pricing_rules WHERE id = ?", [$ruleId]);
    }
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
