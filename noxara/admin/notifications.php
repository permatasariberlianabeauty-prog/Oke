<?php
/**
 * NOXARA Admin - Broadcast Notifikasi
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Notifikasi Broadcast';
$adminId   = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/notifications.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'broadcast') {
        $title   = clean($_POST['notif_title'] ?? '');
        $message = clean($_POST['notif_message'] ?? '');
        $type    = in_array($_POST['notif_type'] ?? '', ['info','success','warning','error']) ? $_POST['notif_type'] : 'info';

        if ($title && $message) {
            // Insert broadcast notification (user_id=NULL means all)
            db()->execute(
                'INSERT INTO notifications (user_id, type, title, message, is_broadcast) VALUES (NULL,?,?,?,1)',
                'sss', [$type, $title, $message]
            );
            logActivity('broadcast_notification', 'notifications', (int)db()->lastInsertId(), "Broadcast: {$title}");
            setFlashPopup('success', 'Notifikasi broadcast berhasil dikirim ke semua member.');
        } else {
            setFlashPopup('error', 'Judul dan pesan wajib diisi.');
        }
    } elseif ($action === 'save_fonnte') {
        $fonnte_enabled  = (int)($_POST['fonnte_enabled'] ?? 0);
        $fonnte_token    = clean($_POST['fonnte_token'] ?? '');
        $wa_deposit      = clean($_POST['wa_deposit_template'] ?? '');
        $wa_withdraw     = clean($_POST['wa_withdraw_template'] ?? '');
        $wa_profit       = clean($_POST['wa_profit_template'] ?? '');
        db()->execute(
            'INSERT INTO notification_settings (id,fonnte_enabled,fonnte_token,wa_deposit_template,wa_withdraw_template,wa_profit_template)
             VALUES (1,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE fonnte_enabled=?,fonnte_token=?,wa_deposit_template=?,wa_withdraw_template=?,wa_profit_template=?',
            'iissssssssss', [$fonnte_enabled,$fonnte_token,$wa_deposit,$wa_withdraw,$wa_profit,
                             $fonnte_enabled,$fonnte_token,$wa_deposit,$wa_withdraw,$wa_profit]
        );
        logActivity('update_fonnte_settings', 'notification_settings', 0, 'Update Fonnte WA settings');
        setFlashPopup('success', 'Pengaturan notifikasi WA disimpan.');
    }

    header('Location: ' . BASE_URL . '/admin/notifications.php');
    exit;
}

$broadcasts = db()->fetchAll(
    "SELECT * FROM notifications WHERE is_broadcast=1 ORDER BY created_at DESC LIMIT 20"
);
$fonnte = db()->fetchOne('SELECT * FROM notification_settings WHERE id=1 LIMIT 1') ?? [
    'fonnte_enabled' => 0, 'fonnte_token' => '', 'wa_deposit_template' => '', 'wa_withdraw_template' => '', 'wa_profit_template' => ''
];

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-grid-2">
  <!-- Broadcast Form -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header"><h3>Kirim Broadcast Notifikasi</h3></div>
      <p style="color:#64748b;font-size:13px;margin-bottom:16px">Notifikasi akan dikirim ke semua member yang login.</p>
      <form method="POST">
        <?= CSRF::field() ?><input type="hidden" name="action" value="broadcast">
        <div class="form-group"><label>Judul Notifikasi *</label><input type="text" name="notif_title" class="form-input" placeholder="Promo Spesial!" required></div>
        <div class="form-group"><label>Pesan *</label><textarea name="notif_message" class="form-input" rows="3" placeholder="Tulis pesan untuk semua member..." required></textarea></div>
        <div class="form-group">
          <label>Tipe</label>
          <select name="notif_type" class="form-select">
            <option value="info">Info (Biru)</option>
            <option value="success">Sukses (Hijau)</option>
            <option value="warning">Peringatan (Kuning)</option>
            <option value="error">Penting (Merah)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="min-width:160px">Kirim ke Semua Member</button>
      </form>
    </div>

    <!-- Riwayat Broadcast -->
    <div class="admin-card" style="margin-top:20px">
      <div class="admin-card-header"><h3>Riwayat Broadcast Terbaru</h3></div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Judul</th><th>Tipe</th><th>Tanggal</th></tr></thead>
          <tbody>
          <?php if (empty($broadcasts)): ?>
            <tr><td colspan="3" class="text-center text-muted">Belum ada broadcast</td></tr>
          <?php else: ?>
            <?php foreach ($broadcasts as $b): ?>
            <tr>
              <td>
                <div class="fw-600"><?= e($b['title']) ?></div>
                <div class="text-xs text-muted"><?= e(mb_substr($b['message'],0,60)) ?>...</div>
              </td>
              <td><span class="badge badge-<?= e($b['type']) ?>"><?= e($b['type']) ?></span></td>
              <td><?= formatDate($b['created_at'],'d M Y H:i') ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Fonnte WA Settings -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header"><h3>Pengaturan WhatsApp (Fonnte)</h3></div>
      <form method="POST">
        <?= CSRF::field() ?><input type="hidden" name="action" value="save_fonnte">
        <div class="form-group">
          <label>Status Fonnte</label>
          <select name="fonnte_enabled" class="form-select">
            <option value="1" <?= $fonnte['fonnte_enabled']?'selected':'' ?>>Aktif</option>
            <option value="0" <?= !$fonnte['fonnte_enabled']?'selected':'' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="form-group">
          <label>Fonnte API Token</label>
          <input type="text" name="fonnte_token" class="form-input" value="<?= e($fonnte['fonnte_token']) ?>" placeholder="Masukkan token Fonnte...">
          <div class="text-xs text-muted" style="margin-top:4px">Dapatkan token di <a href="https://fonnte.com" target="_blank" style="color:#00D4FF">fonnte.com</a></div>
        </div>
        <div class="form-group">
          <label>Template Notif Deposit</label>
          <textarea name="wa_deposit_template" class="form-input" rows="3"><?= e($fonnte['wa_deposit_template']) ?></textarea>
          <div class="text-xs text-muted" style="margin-top:4px">Variabel: {amount}, {username}, {date}</div>
        </div>
        <div class="form-group">
          <label>Template Notif Withdraw</label>
          <textarea name="wa_withdraw_template" class="form-input" rows="3"><?= e($fonnte['wa_withdraw_template']) ?></textarea>
        </div>
        <div class="form-group">
          <label>Template Notif Profit</label>
          <textarea name="wa_profit_template" class="form-input" rows="3"><?= e($fonnte['wa_profit_template']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Pengaturan WA</button>
      </form>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
