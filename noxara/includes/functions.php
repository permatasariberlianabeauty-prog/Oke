<?php
/**
 * NOXARA - Global Helper Functions
 */

// ============================================================
// OUTPUT & SANITIZE
// ============================================================

function e(mixed $val): string
{
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function clean(string $val): string
{
    return trim(strip_tags($val));
}

function sanitizeUsername(string $val): string
{
    return preg_replace('/[^a-zA-Z0-9_]/', '', $val);
}

// ============================================================
// REDIRECT
// ============================================================

function redirect(string $url, int $code = 302): never
{
    header('Location: ' . $url, true, $code);
    exit;
}

function redirectBack(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    redirect(!empty($ref) ? $ref : $fallback);
}

// ============================================================
// JSON
// ============================================================

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function isAjax(): bool
{
    return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
}

// ============================================================
// SETTINGS FROM DB
// ============================================================

function getSetting(string $key, mixed $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];

    $row = db()->fetchOne('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1', 's', [$key]);
    $cache[$key] = $row ? ($row['setting_value'] ?? $default) : $default;
    return (string)$cache[$key];
}

function getSettings(string $group = ''): array
{
    if ($group) {
        $rows = db()->fetchAll('SELECT setting_key, setting_value FROM settings WHERE setting_group = ?', 's', [$group]);
    } else {
        $rows = db()->fetchAll('SELECT setting_key, setting_value FROM settings');
    }
    $out = [];
    foreach ($rows as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }
    return $out;
}

function updateSetting(string $key, string $value): bool
{
    return db()->execute(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?',
        'sss', [$key, $value, $value]
    );
}

// ============================================================
// FORMAT
// ============================================================

function formatRupiah(float $amount, bool $short = false): string
{
    if ($short) {
        if ($amount >= 1_000_000_000) return 'Rp ' . number_format($amount / 1_000_000_000, 1) . 'M';
        if ($amount >= 1_000_000)     return 'Rp ' . number_format($amount / 1_000_000, 1) . 'jt';
        if ($amount >= 1_000)         return 'Rp ' . number_format($amount / 1_000, 1) . 'rb';
    }
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatNumber(float $n): string
{
    return number_format($n, 0, ',', '.');
}

function formatDate(string $datetime, string $format = 'd M Y'): string
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '-';
    return date($format, strtotime($datetime));
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return $diff . ' detik lalu';
    if ($diff < 3600)   return floor($diff/60) . ' menit lalu';
    if ($diff < 86400)  return floor($diff/3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff/86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}

function maskName(string $name): string
{
    $words = explode(' ', $name);
    $masked = [];
    foreach ($words as $w) {
        $len = mb_strlen($w);
        if ($len <= 2) {
            $masked[] = $w[0] . str_repeat('*', max(1, $len-1));
        } else {
            $masked[] = mb_substr($w, 0, 2) . str_repeat('*', $len - 2);
        }
    }
    return implode(' ', $masked);
}

function generateCode(int $length = 8): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function generateReferralCode(): string
{
    do {
        $code = 'NXR' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $exists = db()->fetchOne('SELECT id FROM users WHERE referral_code = ? LIMIT 1', 's', [$code]);
    } while ($exists);
    return $code;
}

// ============================================================
// IP & USER AGENT
// ============================================================

function getClientIp(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function getUserAgent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);
}

// ============================================================
// AUTH GUARDS
// ============================================================

function requireLogin(): void
{
    if (!SessionManager::isLoggedIn()) {
        SessionManager::flash('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '');
        redirect(BASE_URL . '/auth/login.php');
    }
    // Cek user aktif
    $user = db()->fetchOne('SELECT is_active, is_blocked, block_reason FROM users WHERE id = ? LIMIT 1',
        'i', [SessionManager::userId()]);
    if (!$user || !$user['is_active']) {
        SessionManager::logoutUser();
        redirect(BASE_URL . '/auth/login.php');
    }
    if ($user['is_blocked']) {
        SessionManager::set('block_reason', $user['block_reason']);
        redirect(BASE_URL . '/errors/blocked.php');
    }
}

function requireAdmin(string $minRole = ''): void
{
    if (!SessionManager::isAdminLoggedIn()) {
        redirect(BASE_URL . '/admin/login.php');
    }
    if ($minRole) {
        $role = SessionManager::adminRole();
        $hierarchy = [ROLE_CS => 1, ROLE_FINANCE => 2, ROLE_SUPERADMIN => 3];
        $userLevel = $hierarchy[$role] ?? 0;
        $minLevel  = $hierarchy[$minRole] ?? 0;
        if ($userLevel < $minLevel) {
            http_response_code(403);
            die('Akses ditolak. Role Anda tidak memiliki izin.');
        }
    }
}

function adminHasRole(string $role): bool
{
    return SessionManager::adminRole() === $role;
}

// ============================================================
// POPUP SYSTEM
// ============================================================

function getPopupSetting(string $eventKey): ?array
{
    return db()->fetchOne(
        'SELECT * FROM popup_settings WHERE event_key = ? AND is_active = 1 LIMIT 1',
        's', [$eventKey]
    );
}

function setFlashPopup(string $type, string $message, string $title = ''): void
{
    SessionManager::flash('popup', [
        'type'    => $type,
        'message' => $message,
        'title'   => $title,
    ]);
}

function renderPopupContainer(): string
{
    $popup = SessionManager::getFlash('popup');
    if (!$popup) return '';
    $type    = e($popup['type'] ?? 'info');
    $title   = e($popup['title'] ?? '');
    $message = e($popup['message'] ?? '');
    return "<script>document.addEventListener('DOMContentLoaded',function(){showToast(" .
        json_encode(['type'=>$type,'title'=>$title,'message'=>$message]) . ");});</script>";
}

// ============================================================
// PAGINATION
// ============================================================

function paginate(int $total, int $page, int $perPage = PER_PAGE): array
{
    $totalPages = (int)ceil($total / $perPage);
    $page       = max(1, min($page, $totalPages ?: 1));
    $offset     = ($page - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}

// ============================================================
// MISC
// ============================================================

function assetUrl(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function uploadUrl(string $path): string
{
    return BASE_URL . '/uploads/' . ltrim($path, '/');
}

function pageUrl(string $page): string
{
    return BASE_URL . '/pages/' . ltrim($page, '/');
}

function adminUrl(string $page = ''): string
{
    return BASE_URL . '/admin/' . ltrim($page, '/');
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function postVal(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function getVal(string $key, mixed $default = ''): mixed
{
    return $_GET[$key] ?? $default;
}

function logActivity(string $action, string $targetType = '', int $targetId = 0, string $desc = '', string $status = 'success'): void
{
    $adminId = SessionManager::isAdminLoggedIn() ? SessionManager::adminId() : null;
    db()->execute(
        'INSERT INTO admin_logs (admin_id, action, target_type, target_id, description, ip_address, user_agent, status) VALUES (?,?,?,?,?,?,?,?)',
        'issisisss',
        [$adminId, $action, $targetType, $targetId, $desc, getClientIp(), getUserAgent(), $status]
    );
}

function writeLog(string $filename, string $message): void
{
    $logFile = LOGS_PATH . '/' . $filename;
    $line    = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
