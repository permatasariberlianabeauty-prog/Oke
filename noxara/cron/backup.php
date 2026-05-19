<?php
/**
 * NOXARA Cron: backup.php
 * Backup database otomatis
 * Jadwal: 0 2 * * * php /www/wwwroot/noxara.page/cron/backup.php
 */
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_INSTALL_CHECK', true);
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/backup.php';

if (PHP_SAPI !== 'cli') {
    $cronToken = getSetting('cron_secret_token', '');
    $reqToken  = $_GET['token'] ?? '';
    if (empty($cronToken) || !hash_equals($cronToken, $reqToken)) {
        http_response_code(403); die('Forbidden');
    }
}

$autoEnabled = getSetting('backup_auto_enabled', '1');
if ($autoEnabled !== '1') {
    echo 'Auto backup dinonaktifkan.' . PHP_EOL;
    exit(0);
}

$cronName = 'backup';
db()->execute('INSERT INTO cron_logs (cron_name, status, started_at) VALUES (?,?,NOW())', 'ss', [$cronName, 'running']);
$logId = db()->lastInsertId();

try {
    $result = runBackup('auto', null);
    $status  = $result['success'] ? 'success' : 'failed';
    $msg     = $result['success']
        ? "Backup berhasil: {$result['filename']} (" . number_format($result['filesize']) . " bytes)"
        : "Backup gagal: {$result['message']}";

    db()->execute('UPDATE cron_logs SET status=?,records_processed=?,message=?,finished_at=NOW() WHERE id=?',
        'sisi', [$status, $result['success'] ? 1 : 0, $msg, $logId]);
    echo $msg . PHP_EOL;

} catch (Throwable $e) {
    $msg = 'Error: ' . $e->getMessage();
    db()->execute('UPDATE cron_logs SET status=?,message=?,finished_at=NOW() WHERE id=?', 'ssi', ['failed',$msg,$logId]);
    writeLog('cron_errors.log', "[backup] {$msg}");
    exit(1);
}
