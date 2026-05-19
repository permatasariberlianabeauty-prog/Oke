<?php
/**
 * NOXARA Admin - Popup Settings
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/upload.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Pengaturan Popup';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/popup_settings.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_popup') {
        $popupId    = (int)($_POST['popup_id'] ?? 0);
        $title      = clean($_POST['pop_title'] ?? '');
        $message    = clean($_POST['pop_message'] ?? '');
        $type       = in_array($_POST['pop_type'] ?? '', ['success','error','warning','info']) ? $_POST['pop_type'] : 'info';
        $isActive   = (int)($_POST['is_active'] ?? 0);
        $duration   = (int)($_POST['duration'] ?? 4000);
        $btnText    = clean($_POST['button_text'] ?? '');
        $btnUrl     = clean($_POST['button_url'] ?? '');
        if ($popupId) {
            db()->execute(
                'UPDATE popup_settings SET title=?,message=?,type=?,is_active=?,duration=?,button_text=?,button_url=? WHERE id=?',
                'sssiiissi', [$title,$message,$type,$isActive,$duration,$btnText,$btnUrl,$popupId]
            );
            logActivity('update_popup_settings', 'popup_settings', $popupId, "Update popup #{$popupId}");
            setFlashPopup('success', 'Popup berhasil diperbarui.');
        }
    } elseif ($action === 'save_welcome_popup') {
        $keys = [
            'welcome_popup_enabled','welcome_popup_title','welcome_popup_message',
            'welcome_popup_whatsapp_url','welcome_popup_button_text',
            'welcome_popup_show_mode','welcome_popup_animation'
        ];
        foreach ($keys as $k) {
            $val = ($k === 'welcome_popup_enabled') ? (string)(int)($_POST[$k] ?? 0) : clean($_POST[$k] ?? '');
            updateSetting($k, $val);
        }
        // Image upload
        if (!empty($_FILES['welcome_popup_image']['name'])) {
            $upload = handleUpload($_FILES['welcome_popup_image'], 'banner');
            if ($upload['success']) updateSetting('welcome_popup_image', $upload['path']);
        }
        logActivity('update_welcome_popup', 'settings', 0, 'Update welcome popup');
        setFlashPopup('success', 'Welcome popup berhasil disimpan.');
    }

    header('Location: ' . BASE_URL . '/admin/popup_settings.php');
    exit;
}

$popups = db()->fetchAll('SELECT * FROM popup_settings ORDER BY id ASC');
$wp     = getSettings('popup');
$editPopupId = (int)($_GET['edit'] ?? 0);
$editPopup   = null;
if ($editPopupId) $editPopup = db()->fetchOne('SELECT * FROM popup_settings WHERE id=? LIMIT 1', 'i', [$editPopupId]);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-grid-2">
  <!-- Popup List -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header"><h3>Daftar Event Popup</h3></div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Event Key</th><th>Judul</th><th>Tipe</th><th>Aktif</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($popups as $p): ?>
          <tr>
            <td><code style="font-size:11px;background:rgba(0,212,255,.08);padding:2px 6px;border-radius:4px"><?= e($p['event_key']) ?></code></td>
            <td><?= e($p['title']) ?></td>
            <td><span class="badge badge-<?= e($p['type']) ?>"><?= e($p['type']) ?></span></td>
            <td><span class="badge <?= $p['is_active']?'badge-success':'badge-error' ?>"><?= $p['is_active']?'Ya':'Tidak' ?></span></td>
            <td><a href="?edit=<?= (int)$p['id'] ?>" class="btn btn-xs btn-ghost">Edit</a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Welcome Popup Settings -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header"><h3>Welcome Popup</h3></div>
      <form method="POST" enctype="multipart/form-data">
        <?= CSRF::field() ?><input type="hidden" name="action" value="save_welcome_popup">
        <div class="form-group">
          <label>Status Welcome Popup</label>
          <select name="welcome_popup_enabled" class="form-select">
            <option value="1" <?= ($wp['welcome_popup_enabled'] ?? '1')==='1'?'selected':'' ?>>Aktif</option>
            <option value="0" <?= ($wp['welcome_popup_enabled'] ?? '1')==='0'?'selected':'' ?>>Nonaktif</option>
          </select>
        </div>
        <div class="form-group">
          <label>Judul Popup</label>
          <input type="text" name="welcome_popup_title" class="form-input" value="<?= e($wp['welcome_popup_title'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Pesan</label>
          <textarea name="welcome_popup_message" class="form-input" rows="3"><?= e($wp['welcome_popup_message'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>URL WhatsApp Grup</label>
          <input type="url" name="welcome_popup_whatsapp_url" class="form-input" value="<?= e($wp['welcome_popup_whatsapp_url'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Teks Tombol</label>
          <input type="text" name="welcome_popup_button_text" class="form-input" value="<?= e($wp['welcome_popup_button_text'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Mode Tampil</label>
          <select name="welcome_popup_show_mode" class="form-select">
            <option value="always" <?= ($wp['welcome_popup_show_mode'] ?? '')==='always'?'selected':'' ?>>Selalu Tampil</option>
            <option value="once_session" <?= ($wp['welcome_popup_show_mode'] ?? '')==='once_session'?'selected':'' ?>>Sekali Per Sesi</option>
            <option value="once_day" <?= ($wp['welcome_popup_show_mode'] ?? '')==='once_day'?'selected':'' ?>>Sekali Per Hari</option>
          </select>
        </div>
        <div class="form-group">
          <label>Animasi</label>
          <select name="welcome_popup_animation" class="form-select">
            <option value="zoom" <?= ($wp['welcome_popup_animation'] ?? '')==='zoom'?'selected':'' ?>>Zoom</option>
            <option value="slide" <?= ($wp['welcome_popup_animation'] ?? '')==='slide'?'selected':'' ?>>Slide</option>
            <option value="fade" <?= ($wp['welcome_popup_animation'] ?? '')==='fade'?'selected':'' ?>>Fade</option>
          </select>
        </div>
        <?php if (!empty($wp['welcome_popup_image'])): ?>
        <div class="form-group"><label>Gambar Saat Ini</label><img src="<?= BASE_URL ?>/uploads/<?= e($wp['welcome_popup_image']) ?>" style="max-height:80px;border-radius:8px"></div>
        <?php endif; ?>
        <div class="form-group">
          <label>Upload Gambar (opsional)</label>
          <input type="file" name="welcome_popup_image" class="form-input" accept="image/jpeg,image/png,image/webp">
        </div>
        <button type="submit" class="btn btn-primary">Simpan Welcome Popup</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Popup Modal -->
<?php if ($editPopup): ?>
<div class="modal-overlay visible" id="editPopupModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/popup_settings.php'">
  <div class="modal-box" style="max-width:500px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Popup: <?= e($editPopup['event_key']) ?></h3><a href="<?= BASE_URL ?>/admin/popup_settings.php" class="btn-close">✕</a></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="update_popup"><input type="hidden" name="popup_id" value="<?= (int)$editPopup['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Judul</label><input type="text" name="pop_title" class="form-input" value="<?= e($editPopup['title']) ?>"></div>
        <div class="form-group"><label>Pesan</label><textarea name="pop_message" class="form-input" rows="2"><?= e($editPopup['message']) ?></textarea></div>
        <div class="form-row">
          <div class="form-group">
            <label>Tipe</label>
            <select name="pop_type" class="form-select">
              <?php foreach (['success','error','warning','info'] as $t): ?>
              <option value="<?= $t ?>" <?= $editPopup['type']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Durasi (ms)</label>
            <input type="number" name="duration" class="form-input" value="<?= (int)$editPopup['duration'] ?>" min="0" step="500">
          </div>
        </div>
        <div class="form-group"><label>Teks Tombol (opsional)</label><input type="text" name="button_text" class="form-input" value="<?= e($editPopup['button_text']) ?>"></div>
        <div class="form-group"><label>URL Tombol (opsional)</label><input type="url" name="button_url" class="form-input" value="<?= e($editPopup['button_url']) ?>"></div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active" class="form-select">
            <option value="1" <?= $editPopup['is_active']?'selected':'' ?>>Aktif</option>
            <option value="0" <?= !$editPopup['is_active']?'selected':'' ?>>Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/popup_settings.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
