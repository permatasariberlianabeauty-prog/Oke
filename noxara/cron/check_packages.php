<?php
/**
 * NOXARA Cron: check_packages.php
 * Cek paket expired dan kembalikan modal
 * Jadwal: 5 0 * * * php /www/wwwroot/noxara.page/cron/check_packages.php
 */
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_INSTALL_CHECK', true);
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/notification.php';

if (PHP_SAPI !== 'cli') {
    $cronToken = getSetting('cron_secret_token', '');
    $reqToken  = $_GET['token'] ?? '';
    if (empty($cronToken) || !hash_equals($cronToken, $reqToken)) {
        http_response_code(403); die('Forbidden');
    }
}

$cronName  = 'check_packages';
db()->execute('INSERT INTO cron_logs (cron_name, status, started_at) VALUES (?,?,NOW())', 'ss', [$cronName, 'running']);
$logId = db()->lastInsertId();

$expired   = 0;
$modal     = 0;
$notif3day = 0;
$notif1day = 0;

try {
    // 1. Tandai paket yang expired
    $expiredPackages = db()->fetchAll(
        'SELECT * FROM user_products WHERE status = "active" AND expired_at <= NOW()',
    );
    foreach ($expiredPackages as $pkg) {
        db()->execute('UPDATE user_products SET status = "expired" WHERE id = ?', 'i', [(int)$pkg['id']]);
        $expired++;

        // Kembalikan modal jika belum
        if (!(int)$pkg['modal_returned']) {
            $pricePaid = (float)$pkg['price_paid'];
            if ($pricePaid > 0) {
                creditBalance((int)$pkg['user_id'], WALLET_MAIN, $pricePaid, TX_MODAL_RETURN,
                    'user_product', (int)$pkg['id'], 'Modal paket dikembalikan (expired)');
                db()->execute('UPDATE user_products SET modal_returned = 1 WHERE id = ?', 'i', [(int)$pkg['id']]);
                $modal++;
            }
        }

        // Notif expired
        createNotification((int)$pkg['user_id'], 'package_expired', 'Paket Berakhir',
            'Paket Anda telah berakhir. Modal sudah dikembalikan ke saldo utama.');
    }

    // 2. Notifikasi H-3
    $in3days = date('Y-m-d', strtotime('+3 days'));
    $pkg3 = db()->fetchAll(
        'SELECT * FROM user_products WHERE status = "active" AND DATE(expired_at) = ? AND notified_3days = 0',
        's', [$in3days]
    );
    foreach ($pkg3 as $p) {
        createNotification((int)$p['user_id'], 'package_expiring', 'Paket Akan Berakhir',
            'Paket Anda akan berakhir dalam 3 hari. Segera beli paket baru!');
        db()->execute('UPDATE user_products SET notified_3days = 1 WHERE id = ?', 'i', [(int)$p['id']]);
        $notif3day++;
    }

    // 3. Notifikasi H-1
    $in1day = date('Y-m-d', strtotime('+1 day'));
    $pkg1 = db()->fetchAll(
        'SELECT * FROM user_products WHERE status = "active" AND DATE(expired_at) = ? AND notified_1day = 0',
        's', [$in1day]
    );
    foreach ($pkg1 as $p) {
        createNotification((int)$p['user_id'], 'package_expiring_soon', 'Paket Berakhir Besok!',
            'Paket Anda akan berakhir besok. Segera beli paket baru!');
        db()->execute('UPDATE user_products SET notified_1day = 1 WHERE id = ?', 'i', [(int)$p['id']]);
        $notif1day++;
    }

    $msg = "Expired: {$expired}, Modal kembali: {$modal}, Notif H-3: {$notif3day}, Notif H-1: {$notif1day}";
    db()->execute('UPDATE cron_logs SET status=?,records_processed=?,message=?,finished_at=NOW() WHERE id=?',
        'sisi', ['success', $expired + $modal, $msg, $logId]);
    echo $msg . PHP_EOL;

} catch (Throwable $e) {
    $msg = 'Error: ' . $e->getMessage();
    db()->execute('UPDATE cron_logs SET status=?,message=?,finished_at=NOW() WHERE id=?', 'ssi', ['failed',$msg,$logId]);
    writeLog('cron_errors.log', "[check_packages] {$msg}");
    echo $msg . PHP_EOL;
    exit(1);
}
