<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\PricingRule;
use Core\Database;
use Core\Session;

class OwnerController extends Controller
{
    private function requireOwner()
    {
        if (!Session::has('owner_id')) {
            Session::flash('error', 'Vui lòng đăng nhập quản trị.');
            $this->redirect('/owner/login');
        }
    }

    private function requireBranchAccess(int $branchId)
    {
        $ownerBranches = Session::get('owner_branches', []);
        if (!Session::get('owner_is_super') && !in_array($branchId, $ownerBranches)) {
            http_response_code(403);
            Session::flash('error', 'Bạn không có quyền quản lý chi nhánh này.');
            $this->redirect('/owner/dashboard');
        }
    }

    public function loginForm()
    {
        if (Session::has('owner_id')) {
            $this->redirect('/owner/dashboard');
        }
        $this->render('auth/owner_login', [
            'title' => 'Đăng Nhập Quản Trị - SmashZone',
        ]);
    }

    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Vui lòng nhập đầy đủ thông tin.');
            $this->redirect('/owner/login');
        }

        $db = Database::getInstance();
        $owner = $db->query(
            'SELECT * FROM owners WHERE username = ? LIMIT 1',
            [$username]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$owner || !password_verify($password, $owner['password'])) {
            Session::flash('error', 'Tên đăng nhập hoặc mật khẩu không đúng.');
            $this->redirect('/owner/login');
        }

        Session::set('owner_id',       $owner['id']);
        Session::set('owner_name',     $owner['name']);
        Session::set('owner_is_super', $owner['is_super_admin']);

        $branches = $db->query(
            'SELECT branch_id FROM owner_branches WHERE owner_id = ?',
            [$owner['id']]
        )->fetchAll(\PDO::FETCH_COLUMN);
        Session::set('owner_branches', $branches);

        Session::flash('success', 'Đăng nhập quản trị thành công! Chào mừng ' . $owner['name']);
        $this->redirect('/owner/dashboard');
    }

    public function logout()
    {
        Session::remove('owner_id');
        Session::remove('owner_name');
        Session::remove('owner_is_super');
        Session::remove('owner_branches');
        Session::flash('success', 'Đã đăng xuất quản trị.');
        $this->redirect('/owner/login');
    }

    public function dashboard()
    {
        $this->requireOwner();

        $db = Database::getInstance();
        $ownerBranches = Session::get('owner_branches', []);
        $isSuper = Session::get('owner_is_super');

        if ($isSuper) {
            $branchRows = $db->query('SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            if (empty($ownerBranches)) {
                $branchRows = [];
            } else {
                $placeholders = implode(',', array_fill(0, count($ownerBranches), '?'));
                $branchRows = $db->query(
                    "SELECT id, name FROM branches WHERE id IN ({$placeholders}) AND is_active = 1 ORDER BY name",
                    $ownerBranches
                )->fetchAll(\PDO::FETCH_ASSOC);
            }
        }

        $totalBookings = 0;
        $totalRevenue  = 0;
        $todayBookings = 0;

        foreach ($branchRows as $branch) {
            $bid = $branch['id'];
            $cnt = $db->query(
                "SELECT COUNT(*) FROM bookings WHERE branch_id = ?",
                [$bid]
            )->fetchColumn();
            $totalBookings += (int)$cnt;

            $rev = $db->query(
                "SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE branch_id = ? AND status = 'confirmed'",
                [$bid]
            )->fetchColumn();
            $totalRevenue += (float)$rev;

            $today = date('Y-m-d');
            $todayCnt = $db->query(
                "SELECT COUNT(*) FROM bookings WHERE branch_id = ? AND booking_date = ?",
                [$bid, $today]
            )->fetchColumn();
            $todayBookings += (int)$todayCnt;
        }

        $this->render('owner/dashboard', [
            'title'         => 'Dashboard Quản Trị - SmashZone',
            'branches'      => $branchRows,
            'total_bookings'=> $totalBookings,
            'total_revenue' => $totalRevenue,
            'today_bookings'=> $todayBookings,
            'is_super'      => $isSuper,
        ]);
    }

    public function branches()
    {
        $this->requireOwner();

        $db = Database::getInstance();
        $ownerBranches = Session::get('owner_branches', []);
        $isSuper = Session::get('owner_is_super');

        if ($isSuper) {
            $branches = $db->query('SELECT * FROM branches ORDER BY city, name')->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            if (empty($ownerBranches)) {
                $branches = [];
            } else {
                $placeholders = implode(',', array_fill(0, count($ownerBranches), '?'));
                $branches = $db->query(
                    "SELECT * FROM branches WHERE id IN ({$placeholders}) ORDER BY city, name",
                    $ownerBranches
                )->fetchAll(\PDO::FETCH_ASSOC);
            }
        }

        $this->render('owner/branches', [
            'title'    => 'Quản Lý Chi Nhánh - SmashZone',
            'branches' => $branches,
            'is_super' => $isSuper,
        ]);
    }

    public function storeBranch()
    {
        $this->requireOwner();

        $name     = trim($_POST['name'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $courts   = (int)($_POST['courts_count'] ?? 10);
        $openTime = $_POST['open_time'] ?? '05:00:00';
        $closeTime= $_POST['close_time'] ?? '22:00:00';

        $errors = [];
        if (empty($name))   $errors[] = 'Tên chi nhánh không được trống.';
        if (empty($address)) $errors[] = 'Địa chỉ không được trống.';
        if (empty($city))   $errors[] = 'Thành phố không được trống.';
        if ($courts < 1)    $errors[] = 'Số sân phải lớn hơn 0.';

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/owner/branches');
        }

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO branches (name, address, city, phone, description, courts_count, open_time, close_time, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$name, $address, $city, $phone, $desc, $courts, $openTime, $closeTime]
        );
        $newBranchId = (int)$db->query('SELECT LAST_INSERT_ID()')->fetchColumn();

        // Nếu là super admin, có thể gán owner quản lý (mặc định gán owner hiện tại)
        $ownerId = Session::get('owner_id');
        if ($ownerId) {
            $db->query(
                'INSERT IGNORE INTO owner_branches (owner_id, branch_id) VALUES (?, ?)',
                [$ownerId, $newBranchId]
            );
        }

        Session::flash('success', 'Đã thêm chi nhánh mới.');
        $this->redirect('/owner/branches');
    }

    public function updateBranch()
    {
        $this->requireOwner();

        $id       = (int)($_POST['branch_id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $courts   = (int)($_POST['courts_count'] ?? 10);
        $openTime = $_POST['open_time'] ?? '05:00:00';
        $closeTime= $_POST['close_time'] ?? '22:00:00';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $this->requireBranchAccess($id);

        $errors = [];
        if ($id <= 0)    $errors[] = 'Chi nhánh không hợp lệ.';
        if (empty($name)) $errors[] = 'Tên chi nhánh không được trống.';

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/owner/branches');
        }

        $db = Database::getInstance();
        $db->query(
            "UPDATE branches SET name=?, address=?, city=?, phone=?, description=?, courts_count=?, open_time=?, close_time=?, is_active=? WHERE id=?",
            [$name, $address, $city, $phone, $desc, $courts, $openTime, $closeTime, $isActive, $id]
        );

        Session::flash('success', 'Đã cập nhật chi nhánh.');
        $this->redirect('/owner/branches');
    }

    public function deleteBranch()
    {
        $this->requireOwner();

        $id = (int)($_POST['branch_id'] ?? 0);
        $this->requireBranchAccess($id);

        $db = Database::getInstance();
        $db->query('DELETE FROM owner_branches WHERE branch_id = ?', [$id]);
        $db->query('DELETE FROM branches WHERE id = ?', [$id]);

        Session::flash('success', 'Đã xóa chi nhánh.');
        $this->redirect('/owner/branches');
    }

    public function bookings()
    {
        $this->requireOwner();

        $db = Database::getInstance();
        $ownerBranches = Session::get('owner_branches', []);
        $isSuper = Session::get('owner_is_super');

        $branchFilter = (int)($_GET['branch_id'] ?? 0);
        $statusFilter = $_GET['status'] ?? '';
        $dateFilter   = $_GET['date'] ?? '';

        if ($isSuper) {
            $allowedBranches = $db->query('SELECT id FROM branches')->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $allowedBranches = $ownerBranches;
        }

        if ($branchFilter > 0) {
            if (!in_array($branchFilter, $allowedBranches)) {
                Session::flash('error', 'Không có quyền xem chi nhánh này.');
                $this->redirect('/owner/bookings');
            }
            $allowedBranches = [$branchFilter];
        }

        if (empty($allowedBranches)) {
            $this->render('owner/bookings', [
                'title'     => 'Quản Lý Đặt Sân - SmashZone',
                'bookings'  => [],
                'branches'  => [],
                'is_super'  => $isSuper,
            ]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($allowedBranches), '?'));
        $params = $allowedBranches;

        $sql = "SELECT b.*, br.name AS branch_name FROM bookings b
                JOIN branches br ON b.branch_id = br.id
                WHERE b.branch_id IN ({$placeholders})";

        if ($statusFilter !== '') {
            $sql .= " AND b.status = ?";
            $params[] = $statusFilter;
        }
        if ($dateFilter !== '') {
            $sql .= " AND b.booking_date = ?";
            $params[] = $dateFilter;
        }
        $sql .= " ORDER BY b.created_at DESC";

        $bookings = $db->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $branches = [];
        foreach ($allowedBranches as $bid) {
            $row = $db->query('SELECT id, name FROM branches WHERE id = ?', [$bid])->fetch(\PDO::FETCH_ASSOC);
            if ($row) $branches[] = $row;
        }

        $this->render('owner/bookings', [
            'title'        => 'Quản Lý Đặt Sân - SmashZone',
            'bookings'     => $bookings,
            'branches'     => $branches,
            'is_super'     => $isSuper,
            'branch_filter'=> $branchFilter,
            'status_filter'=> $statusFilter,
            'date_filter'  => $dateFilter,
        ]);
    }

    public function storeBooking()
    {
        $this->requireOwner();

        $branchId = (int)($_POST['branch_id'] ?? 0);
        $courtId  = (int)($_POST['court_id'] ?? 0);
        $date     = trim($_POST['booking_date'] ?? '');
        $start    = trim($_POST['start_time'] ?? '');
        $end      = trim($_POST['end_time'] ?? '');
        $cName    = trim($_POST['customer_name'] ?? '');
        $cPhone   = trim($_POST['customer_phone'] ?? '');
        $notes    = trim($_POST['notes'] ?? '');

        $this->requireBranchAccess($branchId);

        $errors = [];
        if ($branchId <= 0)   $errors[] = 'Chi nhánh không hợp lệ.';
        if ($courtId <= 0)    $errors[] = 'Sân không hợp lệ.';
        if (empty($date))     $errors[] = 'Ngày đặt không được trống.';
        if (empty($start) || empty($end)) $errors[] = 'Thời gian không được trống.';
        if (empty($cName))    $errors[] = 'Họ tên khách hàng không được trống.';
        if (empty($cPhone))   $errors[] = 'SĐT khách hàng không được trống.';

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/owner/bookings');
        }

        $bookingModel = new Booking();
        if (!$bookingModel->isSlotAvailable($courtId, $date, (int)substr($start, 0, 2), (int)substr($end, 0, 2))) {
            Session::flash('error', 'Khung giờ này đã được đặt.');
            $this->redirect('/owner/bookings?branch_id=' . $branchId);
        }

        $duration = ((strtotime($end) - strtotime($start)) / 3600);
        $price    = 150000 * $duration; // giá đơn giản, có thể thay bằng PricingRule

        $bookingModel->create([
            'booking_code'   => $bookingModel->generateBookingCode(),
            'user_id'        => null,
            'customer_name'  => $cName,
            'customer_phone' => $cPhone,
            'customer_email' => '',
            'court_id'       => $courtId,
            'branch_id'      => $branchId,
            'booking_date'   => $date,
            'start_time'     => $start,
            'end_time'       => $end,
            'duration_hours' => $duration,
            'total_price'    => $price,
            'status'         => 'confirmed',
            'expires_at'     => null,
            'notes'          => $notes . ' (Tạo bởi owner)',
        ]);

        Session::flash('success', 'Đã tạo đặt sân cho khách.');
        $this->redirect('/owner/bookings?branch_id=' . $branchId);
    }

    public function updateBooking()
    {
        $this->requireOwner();

        $id      = (int)($_POST['booking_id'] ?? 0);
        $status  = trim($_POST['status'] ?? '');
        $branchId= (int)($_POST['branch_id'] ?? 0);

        if ($id <= 0) {
            Session::flash('error', 'Đặt sân không hợp lệ.');
            $this->redirect('/owner/bookings');
        }

        $this->requireBranchAccess($branchId);

        $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
        if (!in_array($status, $allowed)) {
            Session::flash('error', 'Trạng thái không hợp lệ.');
            $this->redirect('/owner/bookings?branch_id=' . $branchId);
        }

        $db = Database::getInstance();
        $update = "UPDATE bookings SET status = ? WHERE id = ?";
        $params = [$status, $id];

        if ($status === 'completed') {
            $update = "UPDATE bookings SET status = ?, completed_at = NOW() WHERE id = ?";
        }

        $db->query($update, $params);

        Session::flash('success', 'Đã cập nhật trạng thái đặt sân.');
        $this->redirect('/owner/bookings?branch_id=' . $branchId);
    }

    public function pricing()
    {
        $this->requireOwner();

        $db = Database::getInstance();
        $branchId = (int)($_GET['branch_id'] ?? 0);

        if ($branchId > 0) {
            $this->requireBranchAccess($branchId);
            $rules = $db->query(
                'SELECT * FROM pricing_rules WHERE branch_id = ? ORDER BY day_type, start_hour',
                [$branchId]
            )->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $isSuper = Session::get('owner_is_super');
            if ($isSuper) {
                $rules = $db->query('SELECT * FROM pricing_rules ORDER BY branch_id, day_type, start_hour')->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $rules = [];
            }
        }

        $branches = [];
        if (Session::get('owner_is_super')) {
            $branches = $db->query('SELECT id, name FROM branches ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $ownerBranches = Session::get('owner_branches', []);
            if (!empty($ownerBranches)) {
                $placeholders = implode(',', array_fill(0, count($ownerBranches), '?'));
                $branches = $db->query(
                    "SELECT id, name FROM branches WHERE id IN ({$placeholders}) ORDER BY name",
                    $ownerBranches
                )->fetchAll(\PDO::FETCH_ASSOC);
            }
        }

        $this->render('owner/pricing', [
            'title'   => 'Quản Lý Giá - SmashZone',
            'rules'   => $rules,
            'branches'=> $branches,
            'branch_id'=> $branchId,
        ]);
    }

    public function storePricing()
    {
        $this->requireOwner();

        $branchId = (int)($_POST['branch_id'] ?? 0);
        $dayType  = trim($_POST['day_type'] ?? 'all');
        $start    = trim($_POST['start_hour'] ?? '');
        $end      = trim($_POST['end_hour'] ?? '');
        $price    = (float)($_POST['price_per_hour'] ?? 0);
        $label    = trim($_POST['label'] ?? '');

        if ($branchId > 0) {
            $this->requireBranchAccess($branchId);
        } elseif (!Session::get('owner_is_super')) {
            Session::flash('error', 'Không có quyền.');
            $this->redirect('/owner/pricing');
        }

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO pricing_rules (branch_id, day_type, start_hour, end_hour, price_per_hour, label)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$branchId > 0 ? $branchId : null, $dayType, $start, $end, $price, $label]
        );

        Session::flash('success', 'Đã thêm quy giá.');
        $this->redirect('/owner/pricing?branch_id=' . $branchId);
    }

    public function updatePricing()
    {
        $this->requireOwner();

        $id      = (int)($_POST['id'] ?? 0);
        $dayType = trim($_POST['day_type'] ?? 'all');
        $start   = trim($_POST['start_hour'] ?? '');
        $end     = trim($_POST['end_hour'] ?? '');
        $price   = (float)($_POST['price_per_hour'] ?? 0);
        $label   = trim($_POST['label'] ?? '');

        if ($id <= 0) {
            Session::flash('error', 'Quy giá không hợp lệ.');
            $this->redirect('/owner/pricing');
        }

        $db = Database::getInstance();
        $rule = $db->query('SELECT branch_id FROM pricing_rules WHERE id = ?', [$id])->fetch(\PDO::FETCH_ASSOC);
        if (!$rule) {
            Session::flash('error', 'Không tìm thấy quy giá.');
            $this->redirect('/owner/pricing');
        }

        if (!Session::get('owner_is_super')) {
            $this->requireBranchAccess((int)$rule['branch_id']);
        }

        $db->query(
            "UPDATE pricing_rules SET day_type=?, start_hour=?, end_hour=?, price_per_hour=?, label=? WHERE id=?",
            [$dayType, $start, $end, $price, $label, $id]
        );

        Session::flash('success', 'Đã cập nhật quy giá.');
        $this->redirect('/owner/pricing?branch_id=' . ($rule['branch_id'] ?? 0));
    }

    public function deletePricing()
    {
        $this->requireOwner();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Quy giá không hợp lệ.');
            $this->redirect('/owner/pricing');
        }

        $db = Database::getInstance();
        $rule = $db->query('SELECT branch_id FROM pricing_rules WHERE id = ?', [$id])->fetch(\PDO::FETCH_ASSOC);
        if (!$rule) {
            Session::flash('error', 'Không tìm thấy quy giá.');
            $this->redirect('/owner/pricing');
        }

        if (!Session::get('owner_is_super')) {
            $this->requireBranchAccess((int)$rule['branch_id']);
        }

        $db->query('DELETE FROM pricing_rules WHERE id = ?', [$id]);
        Session::flash('success', 'Đã xóa quy giá.');
        $this->redirect('/owner/pricing?branch_id=' . ($rule['branch_id'] ?? 0));
    }

    public function revenue()
    {
        $this->requireOwner();

        $db = Database::getInstance();
        $ownerBranches = Session::get('owner_branches', []);
        $isSuper = Session::get('owner_is_super');

        $branchFilter = (int)($_GET['branch_id'] ?? 0);
        $fromDate = $_GET['from'] ?? date('Y-m-01');
        $toDate   = $_GET['to'] ?? date('Y-m-t');

        if ($isSuper) {
            $allowed = $db->query('SELECT id FROM branches')->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $allowed = $ownerBranches;
        }

        if ($branchFilter > 0) {
            if (!in_array($branchFilter, $allowed)) {
                Session::flash('error', 'Không có quyền xem doanh thu chi nhánh này.');
                $this->redirect('/owner/revenue');
            }
            $allowed = [$branchFilter];
        }

        if (empty($allowed)) {
            $this->render('owner/revenue', [
                'title'      => 'Doanh Thu - SmashZone',
                'revenue'    => 0,
                'bookings'   => [],
                'branches'   => [],
                'is_super'   => $isSuper,
            ]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($allowed), '?'));
        $params = array_merge($allowed, [$fromDate, $toDate]);

        $sql = "SELECT b.*, br.name AS branch_name FROM bookings b
                JOIN branches br ON b.branch_id = br.id
                WHERE b.branch_id IN ({$placeholders})
                AND b.status = 'confirmed'
                AND b.booking_date BETWEEN ? AND ?
                ORDER BY b.booking_date DESC, b.created_at DESC";

        $bookings = $db->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($bookings as $b) {
            $total += (float)$b['total_price'];
        }

        $branches = [];
        foreach ($allowed as $bid) {
            $row = $db->query('SELECT id, name FROM branches WHERE id = ?', [$bid])->fetch(\PDO::FETCH_ASSOC);
            if ($row) $branches[] = $row;
        }

        $this->render('owner/revenue', [
            'title'      => 'Doanh Thu - SmashZone',
            'revenue'    => $total,
            'bookings'   => $bookings,
            'branches'   => $branches,
            'is_super'   => $isSuper,
            'branch_id'  => $branchFilter,
            'from'       => $fromDate,
            'to'         => $toDate,
        ]);
    }
}