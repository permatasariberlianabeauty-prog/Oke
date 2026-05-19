<?php
/**
 * NOXARA Cron: reset_missions.php
 * Reset misi harian dan mingguan
 * Jadwal: 10 0 * * * php /www/wwwroot/noxara.page/cron/reset_missions.php
 */
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_INSTALL_CHECK', true);
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    $cronToken = getSetting('cron_secret_token', '');
    $reqToken  = $_GET['token'] ?? '';
    if (empty($cronToken) || !hash_equals($cronToken, $reqToken)) {
        http_response_code(403); die('Forbidden');
    }
}

$cronName = 'reset_missions';
db()->execute('INSERT INTO cron_logs (cron_name, status, started_at) VALUES (?,?,NOW())', 'ss', [$cronName, 'running']);
$logId = db()->lastInsertId();

$dailyReset  = 0;
$weeklyReset = 0;

try {
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Reset misi harian yang period_start < today (unclaimed → reset)
    $r1 = db()->execute(
        'DELETE FROM user_missions WHERE mission_id IN (SELECT id FROM missions WHERE type = "daily")
         AND status IN ("in_progress","completed") AND period_start < ?',
        's', [$today]
    );
    $dailyReset = db()->affectedRows();

    // Reset misi mingguan jika sudah melewati period_end
    $r2 = db()->execute(
        'DELETE FROM user_missions WHERE mission_id IN (SELECT id FROM missions WHERE type = "weekly")
         AND status IN ("in_progress","completed") AND period_end < ?',
        's', [$today]
    );
    $weeklyReset = db()->affectedRows();

    $msg = "Misi harian direset: {$dailyReset}, misi mingguan direset: {$weeklyReset}";
    db()->execute('UPDATE cron_logs SET status=?,records_processed=?,message=?,finished_at=NOW() WHERE id=?',
        'sisi', ['success', $dailyReset + $weeklyReset, $msg, $logId]);
    echo $msg . PHP_EOL;

} catch (Throwable $e) {
    $msg = 'Error: ' . $e->getMessage();
    db()->execute('UPDATE cron_logs SET status=?,message=?,finished_at=NOW() WHERE id=?', 'ssi', ['failed',$msg,$logId]);
    writeLog('cron_errors.log', "[reset_missions] {$msg}");
    exit(1);
}
