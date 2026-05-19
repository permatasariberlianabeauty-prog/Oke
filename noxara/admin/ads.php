<?php
/**
 * NOXARA Admin - Kelola Iklan
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/upload.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Kelola Iklan';
$adminId   = SessionManager::adminId();

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/ads.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_ad_settings') {
        $maxAds     = (int)($_POST['max_ads_per_day'] ?? 10);
        $cooldown   = (int)($_POST['cooldown_seconds'] ?? 60);
        $isEnabled  = (int)($_POST['is_enabled'] ?? 0);
        db()->execute(
            'INSERT INTO ad_settings (id,max_ads_per_day,cooldown_seconds,is_enabled) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE max_ads_per_day=?,cooldown_seconds=?,is_enabled=?',
            'iiiii', [$maxAds,$cooldown,$isEnabled,$maxAds,$cooldown,$isEnabled]
        );
        logActivity('save_ad_settings', 'ad_settings', 0, "Update ad settings");
        setFlashPopup('success', 'Pengaturan iklan berhasil disimpan.');
    } elseif ($action === 'add_ad' || $action === 'edit_ad') {
        $adId         = (int)($_POST['ad_id'] ?? 0);
        $title        = clean($_POST['ad_title'] ?? '');
        $url          = clean($_POST['ad_url'] ?? '');
        $reward       = (float)($_POST['reward_amount'] ?? 0);
        $watchDur     = (int)($_POST['watch_duration'] ?? 30);
        $rewardWallet = $_POST['reward_wallet'] === 'main' ? 'main' : 'free';
        $isActive     = (int)($_POST['is_active'] ?? 1);
        $sort         = (int)($_POST['sort_order'] ?? 0);
        $desc         = clean($_POST['ad_desc'] ?? '');
        $imagePath    = null;

        if (!empty($_FILES['ad_image']['name'])) {
            $upload = handleUpload($_FILES['ad_image'], 'ad');
            if ($upload['success']) $imagePath = $upload['path'];
        }

        if ($title) {
            if ($action === 'add_ad') {
                db()->execute(
                    'INSERT INTO ads (title,image,url,reward_amount,reward_wallet,watch_duration,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?)',
                    'sssdsiiii', [$title,$imagePath??'',$url,$reward,$rewardWallet,$watchDur,$isActive,$sort]
                );
                logActivity('add_ad', 'ad', (int)db()->lastInsertId(), "Tambah iklan: {$title}");
                setFlashPopup('success', 'Iklan berhasil ditambahkan.');
            } else {
                $existing = db()->fetchOne('SELECT image FROM ads WHERE id=? LIMIT 1', 'i', [$adId]);
                $finalImg = $imagePath ?? ($existing['image'] ?? '');
                db()->execute(
                    'UPDATE ads SET title=?,image=?,url=?,reward_amount=?,reward_wallet=?,watch_duration=?,is_active=?,sort_order=? WHERE id=?',
                    'sssdsiii i', [$title,$finalImg,$url,$reward,$rewardWallet,$watchDur,$isActive,$sort,$adId]
                );
                logActivity('edit_ad', 'ad', $adId, "Edit iklan: {$title}");
                setFlashPopup('success', 'Iklan berhasil diperbarui.');
            }
        }
    } elseif ($action === 'delete_ad') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        if ($adId) {
            db()->execute('DELETE FROM ads WHERE id=?', 'i', [$adId]);
            logActivity('delete_ad', 'ad', $adId, "Hapus iklan #{$adId}");
            setFlashPopup('success', 'Iklan dihapus.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/ads.php');
    exit;
}

// ── DATA ──────────────────────────────────────────────────────
$ads       = db()->fetchAll('SELECT * FROM ads ORDER BY sort_order ASC, id DESC');
$adSettings = db()->fetchOne('SELECT * FROM ad_settings WHERE id=1 LIMIT 1') ?? ['max_ads_per_day'=>10,'cooldown_seconds'=>60,'is_enabled'=>1];
$editAd    = null;
$editAdId  = (int)($_GET['edit'] ?? 0);
if ($editAdId > 0) $editAd = db()->fetchOne('SELECT * FROM ads WHERE id=? LIMIT 1', 'i', [$editAdId]);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Ad Settings -->
<div class="admin-card" style="margin-bottom:24px">
  <div class="admin-card-header"><h3>Pengaturan Sistem Iklan</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="action" value="save_ad_settings">
    <div class="form-row">
      <div class="form-group">
        <label>Maks Iklan Per Hari</label>
        <input type="number" name="max_ads_per_day" class="form-input" value="<?= (int)$adSettings['max_ads_per_day'] ?>" min="1">
      </div>
      <div class="form-group">
        <label>Cooldown Antar Iklan (detik)</label>
        <input type="number" name="cooldown_seconds" class="form-input" value="<?= (int)$adSettings['cooldown_seconds'] ?>" min="0">
      </div>
      <div class="form-group">
        <label>Status Sistem Iklan</label>
        <select name="is_enabled" class="form-select">
          <option value="1" <?= $adSettings['is_enabled']?'selected':'' ?>>Aktif</option>
          <option value="0" <?= !$adSettings['is_enabled']?'selected':'' ?>>Nonaktif</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Simpan Pengaturan</button>
  </form>
</div>

<!-- Ads List -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Daftar Iklan (<?= count($ads) ?>)</h3>
    <button class="btn btn-sm btn-primary" onclick="document.getElementById('addAdModal').classList.replace('hidden','visible')">+ Tambah Iklan</button>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Gambar</th><th>Judul</th><th>Reward</th><th>Wallet</th><th>Durasi Tonton</th><th>Sort</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (empty($ads)): ?>
        <tr><td colspan="9" class="text-center text-muted">Belum ada iklan</td></tr>
      <?php else: ?>
        <?php foreach ($ads as $ad): ?>
        <tr>
          <td><?= (int)$ad['id'] ?></td>
          <td>
            <?php if ($ad['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= e($ad['image']) ?>" style="width:48px;height:32px;object-fit:cover;border-radius:4px">
            <?php else: ?><span class="text-muted">-</span><?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= e($ad['title']) ?></div>
            <?php if ($ad['url']): ?><div class="text-xs text-muted"><?= e(mb_substr($ad['url'],0,40)) ?>...</div><?php endif; ?>
          </td>
          <td><?= formatRupiah((float)$ad['reward_amount']) ?></td>
          <td><?= $ad['reward_wallet'] === 'main' ? 'Utama' : 'Gratis' ?></td>
          <td><?= (int)$ad['watch_duration'] ?>s</td>
          <td><?= (int)$ad['sort_order'] ?></td>
          <td><span class="badge <?= $ad['is_active']?'badge-success':'badge-error' ?>"><?= $ad['is_active']?'Aktif':'Nonaktif' ?></span></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= (int)$ad['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
              <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_ad"><input type="hidden" name="ad_id" value="<?= (int)$ad['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus iklan ini?')">Hapus</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Ad Modal -->
<div class="modal-overlay hidden" id="addAdModal">
  <div class="modal-box" style="max-width:520px">
    <div class="modal-header"><h3>Tambah Iklan</h3><button class="btn-close" onclick="closeModal('addAdModal')">✕</button></div>
    <form method="POST" enctype="multipart/form-data">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_ad">
      <div class="modal-body">
        <div class="form-group"><label>Judul Iklan *</label><input type="text" name="ad_title" class="form-input" required></div>
        <div class="form-group"><label>URL Tujuan</label><input type="url" name="ad_url" class="form-input" placeholder="https://..."></div>
        <div class="form-group"><label>Gambar Iklan</label><input type="file" name="ad_image" class="form-input" accept="image/jpeg,image/png,image/webp"></div>
        <div class="form-row">
          <div class="form-group"><label>Reward (Rp)</label><input type="number" name="reward_amount" class="form-input" value="0" min="0"></div>
          <div class="form-group"><label>Wallet Reward</label><select name="reward_wallet" class="form-select"><option value="free">Saldo Gratis</option><option value="main">Saldo Utama</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Durasi Tonton (detik)</label><input type="number" name="watch_duration" class="form-input" value="30" min="5"></div>
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="0"></div>
        </div>
        <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addAdModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Ad Modal -->
<?php if ($editAd): ?>
<div class="modal-overlay visible" id="editAdModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/ads.php'">
  <div class="modal-box" style="max-width:520px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Iklan</h3><a href="<?= BASE_URL ?>/admin/ads.php" class="btn-close">✕</a></div>
    <form method="POST" enctype="multipart/form-data">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_ad"><input type="hidden" name="ad_id" value="<?= (int)$editAd['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Judul *</label><input type="text" name="ad_title" class="form-input" value="<?= e($editAd['title']) ?>" required></div>
        <div class="form-group"><label>URL</label><input type="url" name="ad_url" class="form-input" value="<?= e($editAd['url']) ?>"></div>
        <?php if ($editAd['image']): ?><div class="form-group"><label>Gambar Saat Ini</label><img src="<?= BASE_URL ?>/uploads/<?= e($editAd['image']) ?>" style="height:50px;border-radius:4px"></div><?php endif; ?>
        <div class="form-group"><label>Ganti Gambar</label><input type="file" name="ad_image" class="form-input" accept="image/jpeg,image/png,image/webp"></div>
        <div class="form-row">
          <div class="form-group"><label>Reward (Rp)</label><input type="number" name="reward_amount" class="form-input" value="<?= (float)$editAd['reward_amount'] ?>"></div>
          <div class="form-group"><label>Wallet Reward</label><select name="reward_wallet" class="form-select"><option value="free" <?= $editAd['reward_wallet']==='free'?'selected':'' ?>>Gratis</option><option value="main" <?= $editAd['reward_wallet']==='main'?'selected':'' ?>>Utama</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Durasi Tonton (s)</label><input type="number" name="watch_duration" class="form-input" value="<?= (int)$editAd['watch_duration'] ?>"></div>
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="<?= (int)$editAd['sort_order'] ?>"></div>
        </div>
        <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?= $editAd['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$editAd['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/ads.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function closeModal(id) { document.getElementById(id).classList.replace('visible','hidden'); }
</script>
<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
