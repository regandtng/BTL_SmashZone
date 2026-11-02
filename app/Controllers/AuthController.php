<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use Core\Database;
use Core\Session;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (Session::get('user_id')) {
            $this->redirect('/booking/history');
        }
        $this->render('auth/login', [
            'title' => 'Đăng Nhập - SmashZone',
        ]);
    }

    public function login()
    {
        $login    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            Session::flash('error', 'Vui lòng nhập đầy đủ thông tin.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->authenticate($login, $password);

        if ($user) {
            session_regenerate_id(true);
            Session::set('user_id',   $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_email', $user['email']);
            Session::flash('success', 'Đăng nhập thành công! Chào mừng ' . $user['name']);
            $redirect = Session::get('intended_url') ?: '/';
            Session::set('intended_url', null);
            $this->redirect($redirect);
        }

        $db = Database::getInstance();
        $owner = $db->query(
            'SELECT * FROM owners WHERE username = ? LIMIT 1',
            [$login]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($owner && password_verify($password, $owner['password'])) {
            session_regenerate_id(true);
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

        Session::flash('error', 'Email/tên đăng nhập hoặc mật khẩu không đúng.');
        $this->redirect('/login');
    }

    public function registerForm()
    {
        if (Session::get('user_id')) {
            $this->redirect('/');
        }
        $this->render('auth/register', [
            'title' => 'Đăng Ký - SmashZone',
        ]);
    }

    public function register()
    {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirmation'] ?? $_POST['password_confirm'] ?? '';

        // Validate
        $errors = [];
        if (empty($name))    $errors[] = 'Vui lòng nhập họ tên.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if (empty($phone) || !preg_match('/^[0-9]{10,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ.';
        if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
        if ($password !== $confirm)  $errors[] = 'Mật khẩu xác nhận không khớp.';

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/register');
        }

        $userModel = new User();

        if ($userModel->findByEmail($email)) {
            Session::flash('error', 'Email này đã được đăng ký.');
            $this->redirect('/register');
        }

        $userId = $userModel->createUser([
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => $password,
        ]);

        if ($userId) {
            Session::set('user_id',   $userId);
            Session::set('user_name', $name);
            Session::set('user_email', $email);
            Session::flash('success', 'Đăng ký thành công! Chào mừng ' . $name);
            $this->redirect('/');
        } else {
            Session::flash('error', 'Đăng ký thất bại. Vui lòng thử lại.');
            $this->redirect('/register');
        }
    }

    public function logout()
    {
        Session::set('user_id',    null);
        Session::set('user_name',  null);
        Session::set('user_email', null);
        Session::flash('success', 'Đã đăng xuất thành công.');
        $this->redirect('/');
    }

    // ─────────────────── CUSTOMER PROFILE ───────────────────

    public function profile()
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            Session::set('intended_url', '/profile');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user) {
            Session::flash('error', 'Không tìm thấy tài khoản.');
            $this->redirect('/logout');
        }

        $this->render('auth/profile', [
            'title' => 'Hồ Sơ Cá Nhân - SmashZone',
            'user'  => $user,
        ]);
    }

    public function updateProfile()
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            $this->redirect('/login');
        }

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $errors = [];
        if (empty($name))    $errors[] = 'Vui lòng nhập họ tên.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if (empty($phone) || !preg_match('/^[0-9]{10,11}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ.';

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/profile');
        }

        $db = Database::getInstance();
        
        // Check email unique
        $existing = $db->query('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $userId])->fetch();
        if ($existing) {
            Session::flash('error', 'Email này đã được sử dụng bởi tài khoản khác.');
            $this->redirect('/profile');
        }

        // Check phone unique
        $existing = $db->query('SELECT id FROM users WHERE phone = ? AND id != ?', [$phone, $userId])->fetch();
        if ($existing) {
            Session::flash('error', 'Số điện thoại này đã được sử dụng bởi tài khoản khác.');
            $this->redirect('/profile');
        }

        $db->query('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?', [$name, $email, $phone, $userId]);

        Session::set('user_name', $name);
        Session::set('user_email', $email);
        Session::flash('success', 'Đã cập nhật thông tin cá nhân.');
        $this->redirect('/profile');
    }

    public function changePassword()
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            $this->redirect('/login');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];
        if (empty($currentPassword)) $errors[] = 'Vui lòng nhập mật khẩu hiện tại.';
        if (empty($newPassword))     $errors[] = 'Vui lòng nhập mật khẩu mới.';
        if (strlen($newPassword) < 6) $errors[] = 'Mật khẩu mới tối thiểu 6 ký tự.';
        if ($newPassword !== $confirmPassword) $errors[] = 'Mật khẩu xác nhận không khớp.';

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('/profile');
        }

        $db = Database::getInstance();
        $user = $db->query('SELECT password FROM users WHERE id = ?', [$userId])->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            Session::flash('error', 'Mật khẩu hiện tại không đúng.');
            $this->redirect('/profile');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query('UPDATE users SET password = ? WHERE id = ?', [$newHash, $userId]);

        Session::flash('success', 'Đã đổi mật khẩu thành công.');
        $this->redirect('/profile');
    }
}
