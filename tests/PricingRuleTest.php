<?php
// tests/PricingRuleTest.php
// Unit test cho PricingRule model (getPrice)

require_once __DIR__ . '/bootstrap.php';

use App\Models\PricingRule;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- PricingRuleTest ---\n";

$pricing = new PricingRule();

// Test 1: getPrice với weekday (Thứ 2), 2 giờ off-peak sáng (05-07) = 2 * 80000
// 2026-11-02 là Thứ Hai
$price = $pricing->getPrice(1, '2026-11-02', 5, 7);
test('Pricing weekday off-peak 2 giờ = 160000', $price === 160000);

// Test 2: getPrice với weekday peak tối (17-19) = 2 * 150000
$price = $pricing->getPrice(1, '2026-11-02', 17, 19);
test('Pricing weekday peak tối 2 giờ = 300000', $price === 300000);

// Test 3: getPrice với weekend (2026-11-07 là Thứ Bảy), toàn ngày = 180000/giờ
$price = $pricing->getPrice(1, '2026-11-07', 10, 12);
test('Pricing weekend 2 giờ = 360000', $price === 360000);

// Test 4: getPrice trả về số dương
$price = $pricing->getPrice(1, '2026-11-02', 8, 10);
test('Pricing trả về giá dương', is_numeric($price) && $price > 0);

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
