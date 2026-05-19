<?php
/**
 * NOXARA - Konfigurasi Utama
 * Edit file ini sesuai konfigurasi server Anda
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'noxara_db');
define('DB_USER', 'noxara_user');
define('DB_PASS', 'your_password_here');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// BASE URL
// Set manual: define('BASE_URL', 'https://noxara.page');
// Auto-detect: biarkan kosong atau null
// ============================================================
define('BASE_URL_MANUAL', ''); // Kosongkan untuk auto-detect

// Auto-detect BASE_URL
if (!empty(BASE_URL_MANUAL)) {
    define('BASE_URL', rtrim(BASE_URL_MANUAL, '/'));
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'noxara.page';
    define('BASE_URL', $scheme . '://' . $host);
}

// ============================================================
// APPLICATION SETTINGS
// ============================================================
define('APP_NAME', 'NOXARA');
define('APP_TAGLINE', 'Invest Smarter, Grow Faster');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // production | development
define('APP_TIMEZONE', 'Asia/Jakarta');

// ============================================================
// PATHS
// ============================================================
define('ROOT_PATH',    dirname(__DIR__));
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('INCLUDES_PATH',ROOT_PATH . '/includes');
define('PAGES_PATH',   ROOT_PATH . '/pages');
define('ADMIN_PATH',   ROOT_PATH . '/admin');
define('ASSETS_PATH',  ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('BACKUPS_PATH', ROOT_PATH . '/backups');
define('LOGS_PATH',    ROOT_PATH . '/logs');
define('CRON_PATH',    ROOT_PATH . '/cron');

// ============================================================
// SECURITY
// ============================================================
define('CSRF_TOKEN_LENGTH', 64);
define('SESSION_LIFETIME', 7200);        // 2 jam (detik)
define('ADMIN_SESSION_LIFETIME', 3600);  // 1 jam (detik)
define('PIN_HASH_COST', 10);
define('PASSWORD_HASH_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCK_DURATION', 1800);     // 30 menit (detik)
define('RESET_TOKEN_LIFETIME', 3600);    // 1 jam (detik)

// ============================================================
// UPLOAD
// ============================================================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg','jpeg','png','webp']);
define('ALLOWED_DEPOSIT_TYPES', ['jpg','jpeg','png','webp','pdf']);

// ============================================================
// ERROR REPORTING
// ============================================================
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
