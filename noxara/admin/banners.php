<?php
/**
 * NOXARA Admin - Kelola Banner
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/upload.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Kelola Banner';
$adminId   = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/banners.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_banner') {
        $title    = clean($_POST['banner_title'] ?? '');
        $url      = clean($_POST['banner_url'] ?? '');
        $sort     = (int)($_POST['sort_order'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);

        if (empty($_FILES['banner_image']['name'])) {
            setFlashPopup('error', 'Gambar banner wajib diupload.');
        } else {
            $upload = handleUpload($_FILES['banner_image'], 'banner');
            if ($upload['success']) {
                db()->execute(
                    'INSERT INTO banners (title,image,url,sort_order,is_active) VALUES (?,?,?,?,?)',
                    'sssii', [$title,$upload['path'],$url,$sort,$isActive]
                );
                logActivity('add_banner', 'banner', (int)db()->lastInsertId(), "Tambah banner: {$title}");
                setFlashPopup('success', 'Banner berhasil ditambahkan.');
            } else {
                setFlashPopup('error', $upload['message'] ?? 'Upload gagal.');
            }
        }
    } elseif ($action === 'edit_banner') {
        $bannerId = (int)($_POST['banner_id'] ?? 0);
        $title    = clean($_POST['banner_title'] ?? '');
        $url      = clean($_POST['banner_url'] ?? '');
        $sort     = (int)($_POST['sort_order'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);
        $existing = db()->fetchOne('SELECT image FROM banners WHERE id=? LIMIT 1', 'i', [$bannerId]);
        $finalImg = $existing['image'] ?? '';

        if (!empty($_FILES['banner_image']['name'])) {
            $upload = handleUpload($_FILES['banner_image'], 'banner');
            if ($upload['success']) $finalImg = $upload['path'];
        }
        if ($bannerId) {
            db()->execute(
                'UPDATE banners SET title=?,image=?,url=?,sort_order=?,is_active=? WHERE id=?',
                'sssiii', [$title,$finalImg,$url,$sort,$isActive,$bannerId]
            );
            logActivity('edit_banner', 'banner', $bannerId, "Edit banner: {$title}");
            setFlashPopup('success', 'Banner diperbarui.');
        }
    } elseif ($action === 'delete_banner') {
        $bannerId = (int)($_POST['banner_id'] ?? 0);
        if ($bannerId) {
            db()->execute('DELETE FROM banners WHERE id=?', 'i', [$bannerId]);
            logActivity('delete_banner', 'banner', $bannerId, "Hapus banner #{$bannerId}");
            setFlashPopup('success', 'Banner dihapus.');
        }
    } elseif ($action === 'update_sort') {
        // Update sort order via drag
        $orders = $_POST['order'] ?? [];
        foreach ($orders as $idx => $bannerId) {
            db()->execute('UPDATE banners SET sort_order=? WHERE id=?', 'ii', [(int)$idx, (int)$bannerId]);
        }
        echo json_encode(['success' => true]); exit;
    }

    header('Location: ' . BASE_URL . '/admin/banners.php');
    exit;
}

$banners   = db()->fetchAll('SELECT * FROM banners ORDER BY sort_order ASC, id DESC');
$editBanner = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) $editBanner = db()->fetchOne('SELECT * FROM banners WHERE id=? LIMIT 1', 'i', [$editId]);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-header">
    <h3>Daftar Banner (<?= count($banners) ?>)</h3>
    <button class="btn btn-sm btn-primary" onclick="document.getElementById('addBannerModal').classList.replace('hidden','visible')">+ Tambah Banner</button>
  </div>
  <p style="padding:0 4px 12px;color:#64748b;font-size:13px">Seret baris untuk mengubah urutan tampil banner di slider.</p>
  <div class="table-wrap">
    <table class="admin-table" id="bannerTable">
      <thead><tr><th style="width:32px">↕</th><th>Preview</th><th>Judul</th><th>URL</th><th>Sort</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody id="sortableBanners">
        <?php if (empty($banners)): ?>
          <tr><td colspan="7" class="text-center text-muted">Belum ada banner</td></tr>
        <?php else: ?>
          <?php foreach ($banners as $b): ?>
          <tr data-id="<?= (int)$b['id'] ?>">
            <td style="cursor:grab;text-align:center;color:#64748b">☰</td>
            <td>
              <img src="<?= BASE_URL ?>/uploads/<?= e($b['image']) ?>" style="height:44px;max-width:120px;object-fit:cover;border-radius:6px" alt="">
            </td>
            <td><?= e($b['title'] ?? '-') ?></td>
            <td class="text-xs text-muted"><?= $b['url'] ? e(mb_substr($b['url'],0,40)).'...' : '-' ?></td>
            <td><?= (int)$b['sort_order'] ?></td>
            <td><span class="badge <?= $b['is_active']?'badge-success':'badge-error' ?>"><?= $b['is_active']?'Aktif':'Nonaktif' ?></span></td>
            <td>
              <div class="action-btns">
                <a href="?edit=<?= (int)$b['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
                <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_banner"><input type="hidden" name="banner_id" value="<?= (int)$b['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus banner?')">Hapus</button></form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay hidden" id="addBannerModal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header"><h3>Tambah Banner</h3><button class="btn-close" onclick="closeModal('addBannerModal')">✕</button></div>
    <form method="POST" enctype="multipart/form-data">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_banner">
      <div class="modal-body">
        <div class="form-group"><label>Judul (opsional)</label><input type="text" name="banner_title" class="form-input" placeholder="Judul banner..."></div>
        <div class="form-group"><label>Gambar Banner * (direkomendasikan 1200x400px)</label><input type="file" name="banner_image" class="form-input" accept="image/jpeg,image/png,image/webp" required></div>
        <div class="form-group"><label>URL Link (opsional)</label><input type="url" name="banner_url" class="form-input" placeholder="https://..."></div>
        <div class="form-row">
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="0"></div>
          <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addBannerModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Upload & Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<?php if ($editBanner): ?>
<div class="modal-overlay visible" id="editBannerModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/banners.php'">
  <div class="modal-box" style="max-width:480px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Banner</h3><a href="<?= BASE_URL ?>/admin/banners.php" class="btn-close">✕</a></div>
    <form method="POST" enctype="multipart/form-data">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_banner"><input type="hidden" name="banner_id" value="<?= (int)$editBanner['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Judul</label><input type="text" name="banner_title" class="form-input" value="<?= e($editBanner['title']) ?>"></div>
        <div class="form-group"><label>Gambar Saat Ini</label><img src="<?= BASE_URL ?>/uploads/<?= e($editBanner['image']) ?>" style="max-height:80px;border-radius:6px;display:block"></div>
        <div class="form-group"><label>Ganti Gambar (opsional)</label><input type="file" name="banner_image" class="form-input" accept="image/jpeg,image/png,image/webp"></div>
        <div class="form-group"><label>URL Link</label><input type="url" name="banner_url" class="form-input" value="<?= e($editBanner['url']) ?>"></div>
        <div class="form-row">
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="<?= (int)$editBanner['sort_order'] ?>"></div>
          <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?= $editBanner['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$editBanner['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/banners.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function closeModal(id) { document.getElementById(id).classList.replace('visible','hidden'); }

// Simple drag-sort
(function(){
  const tbody = document.getElementById('sortableBanners');
  if (!tbody) return;
  let dragging = null;
  tbody.querySelectorAll('tr[data-id]').forEach(row => {
    row.draggable = true;
    row.addEventListener('dragstart', () => { dragging = row; row.style.opacity = '.4'; });
    row.addEventListener('dragend', () => { dragging = null; row.style.opacity = '1'; saveSortOrder(); });
    row.addEventListener('dragover', e => { e.preventDefault(); const rect = row.getBoundingClientRect(); if (e.clientY < rect.top + rect.height/2) tbody.insertBefore(dragging, row); else tbody.insertBefore(dragging, row.nextSibling); });
  });

  function saveSortOrder() {
    const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
    const fd = new FormData();
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    fd.append('action','update_sort');
    fd.append('csrf_token', csrfMeta ? csrfMeta.content : '');
    ids.forEach((id,i) => fd.append('order['+i+']',id));
    fetch('', {method:'POST',body:fd});
  }
})();
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
