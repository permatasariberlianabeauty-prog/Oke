<?php
/**
 * NOXARA - CSRF Protection
 */

class CSRF
{
    private const TOKEN_KEY = '_csrf_token';

    public static function generate(): string
    {
        SessionManager::start();
        if (!SessionManager::has(self::TOKEN_KEY)) {
            $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
            SessionManager::set(self::TOKEN_KEY, $token);
        }
        return SessionManager::get(self::TOKEN_KEY);
    }

    public static function token(): string
    {
        return self::generate();
    }

    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function meta(): string
    {
        $token = self::generate();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(string $token = ''): bool
    {
        SessionManager::start();
        $sessionToken = SessionManager::get(self::TOKEN_KEY, '');

        if (empty($token)) {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        if (empty($token) || empty($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            if (isAjax()) {
                jsonResponse(['success' => false, 'message' => 'Token keamanan tidak valid. Muat ulang halaman.'], 403);
            }
            http_response_code(403);
            die('Token keamanan tidak valid. <a href="javascript:history.back()">Kembali</a>');
        }
    }

    public static function refresh(): void
    {
        SessionManager::remove(self::TOKEN_KEY);
        self::generate();
    }
}
