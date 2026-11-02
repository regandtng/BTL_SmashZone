<?php

namespace Core;

class Controller
{
    protected function render($view, $data = [])
    {
        extract($data);
        
        $viewFile = ROOT . "/app/Views/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View {$view} not found.");
        }
    }

    protected function redirect($url)
    {
        if (preg_match('#^https?://#i', $url)) {
            header("Location: {$url}");
            exit;
        }

        if (defined('BASE_URL')) {
            $baseUrl = BASE_URL;
        } else {
            $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        }

        $redirectUrl = $baseUrl . '/' . ltrim($url, '/');
        header("Location: {$redirectUrl}");
        exit;
    }

    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function auth()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
        return true;
    }

    protected function adminAuth()
    {
        if (!Session::has('admin_id') && Session::get('user_role') !== 'admin') {
            $this->redirect('/login');
        }
        return true;
    }
    
    protected function ownerAuth()
    {
        if (!Session::has('owner_id')) {
            $this->redirect('/owner/login');
        }
        return true;
    }

    protected function csrfProtect()
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::flash('error', 'Yêu cầu không hợp lệ (CSRF token sai hoặc hết hạn). Vui lòng thử lại.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        return true;
    }
}
