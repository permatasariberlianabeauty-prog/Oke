<?php
/**
 * NOXARA Admin - Status Cron Job
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Status Cron Job';
$adminId   = SessionManager::adminId();

// ── MANUAL RUN ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/cron_status.php');
        exit;
    }

    $cronName = $_POST['cron_name'] ?? '';
    $allowedCrons = ['daily_profit','check_packages','reset_missions','backup'];

    if (in_array($cronName, $allowedCrons)) {
        $cronFile = ROOT_PATH . '/cron/' . $cronName . '.php';
        if (file_exists($cronFile)) {
            // Execute via HTTP
            $url = BASE_URL . '/cron/' . $cronName . '.php?admin_key=' . urlencode(getSetting('cron_admin_key', ''));
            $ch  = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'NOXARA-Admin-CronRun/1.0']);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200) {
                logActivity('manual_cron', 'cron', 0, "Manual run cron: {$cronName}");
                setFlashPopup('success', "Cron {$cronName} berhasil dijalankan.");
            } else {
                setFlashPopup('error', "Cron {$cronName} gagal (HTTP {$httpCode}).");
            }
        } else {
            setFlashPopup('error', "File cron tidak ditemukan: {$cronName}.php");
        }
    }
    header('Location: ' . BASE_URL . '/admin/cron_status.php');
    exit;
}

// ── DATA ──────────────────────────────────────────────────────
$crons = ['daily_profit', 'check_packages', 'reset_missions', 'backup'];

$cronData = [];
foreach ($crons as $cron) {
    $last = db()->fetchOne(
        'SELECT * FROM cron_logs WHERE cron_name=? ORDER BY id DESC LIMIT 1',
        's', [$cron]
    );
    $logs = db()->fetchAll(
        'SELECT * FROM cron_logs WHERE cron_name=? ORDER BY id DESC LIMIT 10',
        's', [$cron]
    );
    $cronData[$cron] = ['last' => $last, 'logs' => $logs];
}

$cronCommands = [
    'daily_profit'   => '0 0 * * * php /www/wwwroot/noxara.page/cron/daily_profit.php',
    'check_packages' => '5 0 * * * php /www/wwwroot/noxara.page/cron/check_packages.php',
    'reset_missions' => '10 0 * * * php /www/wwwroot/noxara.page/cron/reset_missions.php',
    'backup'         => '0 2 * * * php /www/wwwroot/noxara.page/cron/backup.php',
];

$cronLabels = [
    'daily_profit'   => 'Daily Profit Mining',
    'check_packages' => 'Check Package Status',
    'reset_missions' => 'Reset Daily Missions',
    'backup'         => 'Database Backup',
];

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-card" style="margin-bottom:24px">
  <div class="admin-card-header"><h3>Konfigurasi Cron untuk aaPanel</h3></div>
  <p style="color:#64748b;font-size:13px;margin-bottom:12px">Tambahkan perintah berikut ke aaPanel → Cron Jobs → Shell Script:</p>
  <?php foreach ($cronCommands as $cron => $cmd): ?>
  <div style="margin-bottom:10px">
    <div style="font-size:12px;color:#94a3b8;margin-bottom:4px"><?= e($cronLabels[$cron]) ?></div>
    <div style="background:#0A0E1A;border:1px solid rgba(0,212,255,.15);border-radius:8px;padding:10px 14px;font-family:monospace;font-size:13px;color:#00D4FF;display:flex;align-items:center;justify-content:space-between">
      <code><?= e($cmd) ?></code>
      <button type="button" class="btn btn-xs btn-ghost" onclick="copyCmd(this,'<?= e($cmd) ?>')">Salin</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Cron Status Cards -->
<?php foreach ($cronData as $cronName => $data): ?>
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-header">
    <div>
      <h3><?= e($cronLabels[$cronName] ?? $cronName) ?></h3>
      <div class="text-xs text-muted" style="margin-top:2px"><code><?= e($cronName) ?>.php</code></div>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <?php if ($data['last']): ?>
        <?php $ls = $data['last']['status']; ?>
        <span class="badge badge-<?= $ls==='success'?'success':($ls==='failed'?'error':'warning') ?>">
          <?= ucfirst($ls) ?>
        </span>
        <span class="text-xs text-muted"><?= $data['last']['finished_at'] ? timeAgo($data['last']['finished_at']) : timeAgo($data['last']['created_at']) ?></span>
      <?php else: ?>
        <span class="badge badge-secondary">Belum Pernah Jalan</span>
      <?php endif; ?>
      <form method="POST" style="display:inline">
        <?= CSRF::field() ?><input type="hidden" name="cron_name" value="<?= e($cronName) ?>">
        <button type="submit" class="btn btn-xs btn-primary" onclick="return confirm('Jalankan <?= e($cronName) ?> sekarang?')">▶ Jalankan</button>
      </form>
    </div>
  </div>

  <?php if (!empty($data['logs'])): ?>
  <div class="table-wrap">
    <table class="admin-table" style="font-size:12px">
      <thead><tr><th>ID</th><th>Status</th><th>Records</th><th>Pesan</th><th>Mulai</th><th>Selesai</th></tr></thead>
      <tbody>
        <?php foreach ($data['logs'] as $log): ?>
        <tr>
          <td><?= (int)$log['id'] ?></td>
          <td><span class="badge badge-<?= $log['status']==='success'?'success':($log['status']==='failed'?'error':'warning') ?>"><?= e($log['status']) ?></span></td>
          <td><?= number_format((int)$log['records_processed']) ?></td>
          <td class="text-muted"><?= e(mb_substr($log['message'] ?? '-',0,80)) ?></td>
          <td><?= $log['started_at'] ? formatDate($log['started_at'],'d M H:i:s') : '-' ?></td>
          <td><?= $log['finished_at'] ? formatDate($log['finished_at'],'d M H:i:s') : '-' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div style="padding:12px;text-align:center;color:#64748b;font-size:13px">Belum ada log untuk cron ini</div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<script>
function copyCmd(btn, text) {
  navigator.clipboard.writeText(text).then(()=>{ btn.textContent='✓'; setTimeout(()=>btn.textContent='Salin',2000); });
}
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
