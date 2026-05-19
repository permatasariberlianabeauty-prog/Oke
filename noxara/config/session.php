<?php
/**
 * NOXARA - Session Manager
 */

class SessionManager
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Konfigurasi session sebelum start
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', '0'); // hingga browser ditutup

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }

        session_name('NOXARA_SESS');
        session_start();
        self::$started = true;

        // Regenerate jika session lama
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            session_regenerate_id(true);
        } elseif (time() - $_SESSION['_created'] > 1800) {
            // Regenerate setiap 30 menit
            $_SESSION['_created'] = time();
            session_regenerate_id(true);
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set('_flash_' . $key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $val = self::get('_flash_' . $key, $default);
        self::remove('_flash_' . $key);
        return $val;
    }

    public static function hasFlash(string $key): bool
    {
        return self::has('_flash_' . $key);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        self::$started = false;
    }

    // ============================================================
    // USER SESSION
    // ============================================================
    public static function loginUser(array $user): void
    {
        self::set('user_id',    (int)$user['id']);
        self::set('user_name',  $user['username']);
        self::set('user_full',  $user['full_name']);
        self::set('user_email', $user['email']);
        self::set('user_vip',   (int)$user['vip_level']);
        self::set('user_avatar',$user['avatar'] ?? '');
        self::set('user_logged_in', true);
        self::set('user_login_time', time());
        session_regenerate_id(true);
    }

    public static function isLoggedIn(): bool
    {
        return self::get('user_logged_in', false) === true && self::get('user_id') > 0;
    }

    public static function userId(): int
    {
        return (int)self::get('user_id', 0);
    }

    public static function logoutUser(): void
    {
        self::remove('user_id');
        self::remove('user_name');
        self::remove('user_full');
        self::remove('user_email');
        self::remove('user_vip');
        self::remove('user_avatar');
        self::remove('user_logged_in');
        self::remove('user_login_time');
    }

    // ============================================================
    // ADMIN SESSION
    // ============================================================
    public static function loginAdmin(array $admin): void
    {
        self::set('admin_id',       (int)$admin['id']);
        self::set('admin_username', $admin['username']);
        self::set('admin_name',     $admin['full_name']);
        self::set('admin_role',     $admin['role']);
        self::set('admin_logged_in', true);
        self::set('admin_login_time', time());
        session_regenerate_id(true);
    }

    public static function isAdminLoggedIn(): bool
    {
        if (!self::get('admin_logged_in', false)) return false;
        $loginTime = self::get('admin_login_time', 0);
        if (time() - $loginTime > ADMIN_SESSION_LIFETIME) {
            self::logoutAdmin();
            return false;
        }
        // Refresh timeout
        self::set('admin_login_time', time());
        return true;
    }

    public static function adminId(): int
    {
        return (int)self::get('admin_id', 0);
    }

    public static function adminRole(): string
    {
        return (string)self::get('admin_role', '');
    }

    public static function logoutAdmin(): void
    {
        self::remove('admin_id');
        self::remove('admin_username');
        self::remove('admin_name');
        self::remove('admin_role');
        self::remove('admin_logged_in');
        self::remove('admin_login_time');
    }
}
