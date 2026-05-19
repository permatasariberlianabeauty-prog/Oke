<?php
/**
 * NOXARA Admin - Backup Database
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/backup.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Backup Database';
$adminId   = SessionManager::adminId();

// ── DOWNLOAD ──────────────────────────────────────────────────
if (isset($_GET['download'])) {
    if (!CSRF::verify($_GET['csrf'] ?? '')) { http_response_code(403); die('Token tidak valid.'); }
    $filename = basename($_GET['download']);
    downloadBackup($filename, $adminId);
}

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/backup.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'run_backup') {
        $result = runBackup('manual', $adminId);
        if ($result['success']) {
            logActivity('manual_backup', 'backup', 0, 'Manual backup: ' . ($result['filename'] ?? ''));
            setFlashPopup('success', 'Backup berhasil: ' . e($result['filename'] ?? '') . ' (' . number_format(($result['filesize'] ?? 0) / 1024, 1) . ' KB)');
        } else {
            setFlashPopup('error', $result['message'] ?? 'Backup gagal.');
        }
    } elseif ($action === 'delete_backup') {
        $filename = basename($_POST['filename'] ?? '');
        if ($filename) {
            $filepath = BACKUPS_PATH . '/' . $filename;
            if (file_exists($filepath) && is_file($filepath) && str_ends_with($filename, '.sql')) {
                @unlink($filepath);
                db()->execute("UPDATE backup_logs SET status='failed', message='Dihapus manual' WHERE filename=?", 's', [$filename]);
                logActivity('delete_backup', 'backup', 0, "Hapus backup: {$filename}");
                setFlashPopup('success', 'File backup dihapus.');
            } else {
                setFlashPopup('error', 'File tidak ditemukan.');
            }
        }
    } elseif ($action === 'save_backup_settings') {
        $retentionDays = max(1, (int)($_POST['backup_retention_days'] ?? 30));
        $autoEnabled   = (string)(int)($_POST['backup_auto_enabled'] ?? 0);
        updateSetting('backup_retention_days', (string)$retentionDays);
        updateSetting('backup_auto_enabled', $autoEnabled);
        logActivity('update_backup_settings', 'settings', 0, "Update backup settings: retention={$retentionDays}d, auto={$autoEnabled}");
        setFlashPopup('success', 'Pengaturan backup disimpan.');
    }

    header('Location: ' . BASE_URL . '/admin/backup.php');
    exit;
}

$backupList = getBackupList();
$retentionDays  = (int)getSetting('backup_retention_days', '30');
$autoEnabled    = getSetting('backup_auto_enabled', '1');

// Check backups dir writable
$backupDir = BACKUPS_PATH;
$dirWritable = is_dir($backupDir) && is_writable($backupDir);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Backup Settings -->
<div class="admin-grid-2" style="margin-bottom:24px">
  <div class="admin-card">
    <div class="admin-card-header"><h3>Pengaturan Backup</h3></div>
    <?php if (!$dirWritable): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:10px 14px;color:#f87171;margin-bottom:16px;font-size:13px">
      ⚠ Folder <code>backups/</code> tidak bisa ditulis! Perbaiki permission folder.
    </div>
    <?php endif; ?>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="save_backup_settings">
      <div class="form-group">
        <label>Backup Auto Aktif</label>
        <select name="backup_auto_enabled" class="form-select">
          <option value="1" <?= $autoEnabled==='1'?'selected':'' ?>>Ya (via cron)</option>
          <option value="0" <?= $autoEnabled==='0'?'selected':'' ?>>Tidak</option>
        </select>
      </div>
      <div class="form-group">
        <label>Hapus Backup Lebih Dari (hari)</label>
        <input type="number" name="backup_retention_days" class="form-input" value="<?= $retentionDays ?>" min="1" max="365">
      </div>
      <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
    </form>
  </div>

  <div class="admin-card">
    <div class="admin-card-header"><h3>Jalankan Backup Manual</h3></div>
    <p style="color:#64748b;font-size:14px;margin-bottom:20px">Buat snapshot database saat ini. Proses ini membutuhkan waktu beberapa detik hingga menit tergantung ukuran database.</p>
    <div style="margin-bottom:12px">
      <div style="font-size:13px;color:#94a3b8;margin-bottom:4px">Folder Backup:</div>
      <code style="font-size:12px;color:#00D4FF"><?= e($backupDir) ?></code>
      <span class="badge <?= $dirWritable?'badge-success':'badge-error' ?>" style="margin-left:8px"><?= $dirWritable?'Writable':'Not Writable' ?></span>
    </div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="run_backup">
      <button type="submit" class="btn btn-primary" style="min-width:180px" <?= !$dirWritable?'disabled':'' ?>>
        💾 Backup Sekarang
      </button>
    </form>
  </div>
</div>

<!-- Backup List -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Riwayat Backup (<?= count($backupList) ?> file)</h3>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Nama File</th><th>Ukuran</th><th>Tipe</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (empty($backupList)): ?>
        <tr><td colspan="7" class="text-center text-muted">Belum ada backup</td></tr>
      <?php else: ?>
        <?php foreach ($backupList as $b): ?>
        <?php $fileExists = file_exists(BACKUPS_PATH . '/' . basename($b['filename'])); ?>
        <tr>
          <td><?= (int)$b['id'] ?></td>
          <td>
            <code style="font-size:11px"><?= e(basename($b['filename'])) ?></code>
            <?php if (!$fileExists): ?><span class="badge badge-error" style="margin-left:4px">File Hilang</span><?php endif; ?>
          </td>
          <td><?= $b['filesize'] > 0 ? number_format($b['filesize'] / 1024, 1) . ' KB' : '-' ?></td>
          <td><span class="badge badge-secondary"><?= ucfirst(e($b['type'])) ?></span></td>
          <td><span class="badge badge-<?= $b['status']==='success'?'success':'error' ?>"><?= ucfirst(e($b['status'])) ?></span></td>
          <td><?= formatDate($b['created_at'], 'd M Y H:i') ?></td>
          <td>
            <div class="action-btns">
              <?php if ($fileExists): ?>
              <a href="?download=<?= urlencode(basename($b['filename'])) ?>&csrf=<?= CSRF::token() ?>" class="btn btn-xs btn-primary">Download</a>
              <form method="POST" style="display:inline">
                <?= CSRF::field() ?><input type="hidden" name="action" value="delete_backup"><input type="hidden" name="filename" value="<?= e(basename($b['filename'])) ?>">
                <button class="btn btn-xs btn-danger" onclick="return confirm('Hapus file backup ini?')">Hapus</button>
              </form>
              <?php else: ?>
              <span class="text-muted text-xs">File tidak ada</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
