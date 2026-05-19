<?php
/**
 * NOXARA Cron: daily_profit.php
 * Proses profit harian otomatis untuk semua paket aktif
 * Jadwal: 0 0 * * * php /www/wwwroot/noxara.page/cron/daily_profit.php
 */
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_INSTALL_CHECK', true);
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/mining.php';
require_once INCLUDES_PATH . '/notification.php';

// Hanya bisa dijalankan dari CLI atau dengan token rahasia
if (PHP_SAPI !== 'cli') {
    $cronToken = getSetting('cron_secret_token', '');
    $reqToken  = $_GET['token'] ?? '';
    if (empty($cronToken) || !hash_equals($cronToken, $reqToken)) {
        http_response_code(403);
        die('Forbidden');
    }
}

$startTime = microtime(true);
$cronName  = 'daily_profit';
$today     = date('Y-m-d');

// Log start
$logId = db()->lastInsertId();
db()->execute(
    'INSERT INTO cron_logs (cron_name, status, started_at) VALUES (?,?,NOW())',
    'ss', [$cronName, 'running']
);
$logId = db()->lastInsertId();

$processed = 0;
$errors    = 0;
$messages  = [];

try {
    // Ambil semua paket aktif yang belum diklaim hari ini
    $packages = db()->fetchAll(
        'SELECT up.*, u.id as uid FROM user_products up
         JOIN users u ON u.id = up.user_id
         WHERE up.status = "active"
         AND (up.last_claim_date IS NULL OR up.last_claim_date < ?)
         AND up.expired_at > NOW()
         AND u.is_active = 1 AND u.is_blocked = 0',
        's', [$today]
    );

    foreach ($packages as $pkg) {
        $result = claimDailyProfit((int)$pkg['uid'], (int)$pkg['id']);
        if ($result['success']) {
            $processed++;
        } else {
            $errors++;
            $messages[] = "Package #{$pkg['id']}: {$result['message']}";
        }
    }

    $duration = round(microtime(true) - $startTime, 3);
    $msg = "Selesai: {$processed} klaim sukses, {$errors} gagal. Durasi: {$duration}s";
    if (!empty($messages)) $msg .= ' | ' . implode('; ', array_slice($messages, 0, 5));

    db()->execute(
        'UPDATE cron_logs SET status = ?, records_processed = ?, message = ?, finished_at = NOW() WHERE id = ?',
        'sisi', ['success', $processed, $msg, $logId]
    );

    echo $msg . PHP_EOL;

} catch (Throwable $e) {
    $msg = 'Error: ' . $e->getMessage();
    db()->execute(
        'UPDATE cron_logs SET status = ?, message = ?, finished_at = NOW() WHERE id = ?',
        'ssi', ['failed', $msg, $logId]
    );
    writeLog('cron_errors.log', "[daily_profit] {$msg}");
    echo $msg . PHP_EOL;
    exit(1);
}
