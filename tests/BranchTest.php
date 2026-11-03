<?php
// tests/BranchTest.php
// Integration test cho Branch model: lọc active, tìm theo thành phố, đếm sân,
// danh sách thành phố, tìm kiếm và featured.

require_once __DIR__ . '/bootstrap.php';

use App\Models\Branch;

require_once __DIR__ . '/helpers.php';
test_reset();

echo "--- BranchTest ---\n";

$branchModel = new Branch();
$db = \Core\Database::getInstance();

$token = test_unique('BRANCHTEST');
$city = 'TestCity-' . $token;
$branchIds = [];

try {
    $insertBranch = static function (array $data) use ($db): int {
        $db->query(
            "INSERT INTO branches (name, address, city, phone, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['address'],
                $data['city'],
                $data['phone'] ?? '0900000000',
                $data['description'] ?? 'Fixture',
                $data['is_active'],
            ]
        );
        return (int) $db->getConnection()->lastInsertId();
    };

    $activeId = $insertBranch([
        'name' => "SmashZone $token Active",
        'address' => "123 $token Street",
        'city' => $city,
        'is_active' => 1,
    ]);
    $branchIds[] = $activeId;

    $inactiveId = $insertBranch([
        'name' => "SmashZone $token Inactive",
        'address' => "456 $token Street",
        'city' => $city,
        'is_active' => 0,
    ]);
    $branchIds[] = $inactiveId;

    $featuredId = $insertBranch([
        'name' => "SmashZone $token Featured",
        'address' => "789 $token Street",
        'city' => $city,
        'is_active' => 1,
    ]);
    $branchIds[] = $featuredId;

    // 2 sân active + 1 sân maintenance: chỉ đếm sân active.
    foreach (['active', 'active', 'maintenance'] as $index => $status) {
        $db->query(
            "INSERT INTO courts (branch_id, name, type, status) VALUES (?, ?, 'standard', ?)",
            [$featuredId, 'T' . ($index + 1), $status]
        );
    }

    $activeIds = array_column($branchModel->findActive(), 'id');
    test('findActive chỉ trả chi nhánh đang hoạt động',
        in_array($activeId, $activeIds, true) && !in_array($inactiveId, $activeIds, true));

    $byCity = $branchModel->findByCity($city);
    $byCityIds = array_column($byCity, 'id');
    test('findByCity chỉ trả chi nhánh active trong thành phố',
        in_array($activeId, $byCityIds, true)
        && in_array($featuredId, $byCityIds, true)
        && !in_array($inactiveId, $byCityIds, true));

    $cities = $branchModel->getCities();
    test('getCities trả danh sách thành phố duy nhất của chi nhánh active',
        count(array_keys($cities, $city, true)) === 1);

    $withCount = $branchModel->getWithCourtsCount();
    $featuredRow = null;
    foreach ($withCount as $row) {
        if ((int) $row['id'] === $featuredId) {
            $featuredRow = $row;
            break;
        }
    }
    test('getWithCourtsCount chỉ đếm sân active',
        $featuredRow !== null && (int) $featuredRow['actual_courts_count'] === 2);

    $searchResults = $branchModel->search($token);
    $searchIds = array_column($searchResults, 'id');
    test('search tìm theo tên/địa chỉ và loại chi nhánh inactive',
        in_array($activeId, $searchIds, true)
        && in_array($featuredId, $searchIds, true)
        && !in_array($inactiveId, $searchIds, true));

    $featured = $branchModel->getFeatured(1);
    test('getFeatured giới hạn đúng số lượng',
        count($featured) === 1 && (int) $featured[0]['is_active'] === 1);
} finally {
    foreach (array_reverse($branchIds) as $id) {
        $db->query("DELETE FROM branches WHERE id = ?", [$id]);
    }
}

$r = test_results();
echo "\nKết quả: {$r['pass']} passed, {$r['fail']} failed\n";
exit($r['fail'] > 0 ? 1 : 0);
