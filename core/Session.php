<?php

namespace Core;

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = $_ENV['SESSION_LIFETIME'] ?? 120;
            
            session_set_cookie_params([
                'lifetime' => $lifetime * 60,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key)
    {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function setFlash($key, $message)
    {
        $_SESSION['flash'][$key] = $message;
    }

    public static function flash($key, $message)
    {
        self::setFlash($key, $message);
    }

    public static function getFlash($key)
    {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }

    public static function generateCsrfToken()
    {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    public static function validateCsrfToken($token)
    {
        $sessionToken = self::get('csrf_token', '');
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    public static function csrfField()
    {
        self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::get('csrf_token')) . '">';
    }
}
