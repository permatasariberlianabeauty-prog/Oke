<?php
/**
 * NOXARA Admin - Kelola Produk Mining
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/upload.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Produk Mining';
$adminId   = SessionManager::adminId();

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . '/admin/products.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ── CATEGORY ACTIONS ─────────────────────────────
    if ($action === 'add_category') {
        $name = clean($_POST['cat_name'] ?? '');
        $desc = clean($_POST['cat_desc'] ?? '');
        $sort = (int)($_POST['cat_sort'] ?? 0);
        if ($name) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            db()->execute('INSERT INTO product_categories (name, slug, description, sort_order) VALUES (?,?,?,?)', 'sssi', [$name,$slug,$desc,$sort]);
            logActivity('add_category', 'product_category', (int)db()->lastInsertId(), "Tambah kategori: {$name}");
            setFlashPopup('success', 'Kategori berhasil ditambahkan.');
        }
    } elseif ($action === 'edit_category') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        $name  = clean($_POST['cat_name'] ?? '');
        $desc  = clean($_POST['cat_desc'] ?? '');
        $sort  = (int)($_POST['cat_sort'] ?? 0);
        $actv  = (int)($_POST['cat_active'] ?? 1);
        if ($catId && $name) {
            db()->execute('UPDATE product_categories SET name=?,description=?,sort_order=?,is_active=? WHERE id=?', 'ssiii', [$name,$desc,$sort,$actv,$catId]);
            logActivity('edit_category', 'product_category', $catId, "Edit kategori: {$name}");
            setFlashPopup('success', 'Kategori berhasil diperbarui.');
        }
    } elseif ($action === 'delete_category') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        if ($catId) {
            db()->execute('UPDATE product_categories SET is_active=0 WHERE id=?', 'i', [$catId]);
            logActivity('delete_category', 'product_category', $catId, "Hapus kategori #{$catId}");
            setFlashPopup('success', 'Kategori dihapus.');
        }
    }
    // ── PRODUCT ACTIONS ─────────────────────────────
    elseif ($action === 'add_product' || $action === 'edit_product') {
        $prodId    = (int)($_POST['prod_id'] ?? 0);
        $catId     = (int)($_POST['category_id'] ?? 0);
        $name      = clean($_POST['prod_name'] ?? '');
        $desc      = clean($_POST['prod_desc'] ?? '');
        $price     = (float)($_POST['price'] ?? 0);
        $profitDay = (float)($_POST['profit_per_day'] ?? 0);
        $duration  = (int)($_POST['duration_days'] ?? 30);
        $minVip    = (int)($_POST['min_vip_level'] ?? 0);
        $sort      = (int)($_POST['sort_order'] ?? 0);
        $isActive  = (int)($_POST['is_active'] ?? 1);
        $totalRoi  = $profitDay * $duration;
        $imagePath = null;

        // Handle image upload
        if (!empty($_FILES['prod_image']['name'])) {
            $upload = handleUpload($_FILES['prod_image'], 'product');
            if ($upload['success']) $imagePath = $upload['path'];
        }

        if ($name && $catId && $price > 0 && $profitDay > 0) {
            if ($action === 'add_product') {
                $imgVal = $imagePath ?? '';
                db()->execute(
                    'INSERT INTO products (category_id,name,description,price,profit_per_day,duration_days,total_roi,min_vip_level,image,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    'issddddiisi', [$catId,$name,$desc,$price,$profitDay,$duration,$totalRoi,$minVip,$imgVal,$isActive,$sort]
                );
                logActivity('add_product', 'product', (int)db()->lastInsertId(), "Tambah produk: {$name}");
                setFlashPopup('success', 'Produk berhasil ditambahkan.');
            } else {
                $existing = db()->fetchOne('SELECT image FROM products WHERE id=? LIMIT 1', 'i', [$prodId]);
                $finalImg = $imagePath ?? ($existing['image'] ?? '');
                db()->execute(
                    'UPDATE products SET category_id=?,name=?,description=?,price=?,profit_per_day=?,duration_days=?,total_roi=?,min_vip_level=?,image=?,is_active=?,sort_order=? WHERE id=?',
                    'issddddiisii', [$catId,$name,$desc,$price,$profitDay,$duration,$totalRoi,$minVip,$finalImg,$isActive,$sort,$prodId]
                );
                logActivity('edit_product', 'product', $prodId, "Edit produk: {$name}");
                setFlashPopup('success', 'Produk berhasil diperbarui.');
            }
        } else {
            setFlashPopup('error', 'Semua field wajib wajib diisi.');
        }
    } elseif ($action === 'delete_product') {
        $prodId = (int)($_POST['prod_id'] ?? 0);
        if ($prodId) {
            db()->execute('UPDATE products SET is_active=0 WHERE id=?', 'i', [$prodId]);
            logActivity('delete_product', 'product', $prodId, "Hapus (soft) produk #{$prodId}");
            setFlashPopup('success', 'Produk berhasil dihapus.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

// ── DATA ──────────────────────────────────────────────────────
$categories = db()->fetchAll('SELECT * FROM product_categories ORDER BY sort_order ASC');
$products   = db()->fetchAll(
    'SELECT p.*, c.name as cat_name FROM products p LEFT JOIN product_categories c ON c.id=p.category_id ORDER BY c.sort_order ASC, p.sort_order ASC'
);

// Edit modal data
$editProduct  = null;
$editCategory = null;
$editProdId   = (int)($_GET['edit_prod'] ?? 0);
$editCatId    = (int)($_GET['edit_cat'] ?? 0);
if ($editProdId > 0) $editProduct = db()->fetchOne('SELECT * FROM products WHERE id=? LIMIT 1', 'i', [$editProdId]);
if ($editCatId > 0)  $editCategory = db()->fetchOne('SELECT * FROM product_categories WHERE id=? LIMIT 1', 'i', [$editCatId]);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-grid-2">
  <!-- Products list -->
  <div style="grid-column: 1/-1">
    <div class="admin-card">
      <div class="admin-card-header">
        <h3>Daftar Produk Mining</h3>
        <button class="btn btn-sm btn-primary" onclick="document.getElementById('addProductModal').classList.replace('hidden','visible')">+ Tambah Produk</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Profit/Hari</th><th>Durasi</th><th>Min VIP</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="9" class="text-center text-muted">Belum ada produk</td></tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>
                <?php if ($p['image']): ?><img src="<?= BASE_URL ?>/uploads/<?= e($p['image']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:6px;margin-right:8px;vertical-align:middle" alt=""><?php endif; ?>
                <strong><?= e($p['name']) ?></strong>
              </td>
              <td><?= e($p['cat_name'] ?? '-') ?></td>
              <td><?= formatRupiah((float)$p['price']) ?></td>
              <td><?= formatRupiah((float)$p['profit_per_day']) ?></td>
              <td><?= (int)$p['duration_days'] ?> hari</td>
              <td>VIP <?= (int)$p['min_vip_level'] ?>+</td>
              <td><span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-error' ?>"><?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
              <td>
                <div class="action-btns">
                  <a href="?edit_prod=<?= (int)$p['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
                  <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="prod_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus produk ini?')">Hapus</button></form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Categories -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Kategori Produk</h3>
      <button class="btn btn-sm btn-primary" onclick="document.getElementById('addCatModal').classList.replace('hidden','visible')">+ Kategori</button>
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Nama</th><th>Sort</th><th>Aktif</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= (int)$c['id'] ?></td>
          <td><?= e($c['name']) ?></td>
          <td><?= (int)$c['sort_order'] ?></td>
          <td><span class="badge <?= $c['is_active']?'badge-success':'badge-error' ?>"><?= $c['is_active']?'Ya':'Tidak' ?></span></td>
          <td>
            <div class="action-btns">
              <a href="?edit_cat=<?= (int)$c['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
              <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="cat_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus kategori?')">Hapus</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Product Modal -->
<div class="modal-overlay hidden" id="addProductModal">
  <div class="modal-box" style="max-width:580px">
    <div class="modal-header"><h3>Tambah Produk</h3><button class="btn-close" onclick="closeModal('addProductModal')">✕</button></div>
    <form method="POST" enctype="multipart/form-data">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_product">
      <div class="modal-body">
        <?php include __DIR__ . '/../includes/product_form_fields.php' ?? '' ?>
        <div class="form-row">
          <div class="form-group">
            <label>Kategori *</label>
            <select name="category_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Min VIP Level</label>
            <select name="min_vip_level" class="form-select">
              <?php for($i=0;$i<=5;$i++): ?><option value="<?= $i ?>">VIP <?= $i ?></option><?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Nama Produk *</label>
          <input type="text" name="prod_name" class="form-input" placeholder="STONE I" required>
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="prod_desc" class="form-input" rows="2" placeholder="Deskripsi produk..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Harga (Rp) *</label>
            <input type="number" name="price" class="form-input" min="1" step="1" required>
          </div>
          <div class="form-group">
            <label>Profit/Hari (Rp) *</label>
            <input type="number" name="profit_per_day" class="form-input" min="1" step="1" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Durasi (hari)</label>
            <input type="number" name="duration_days" class="form-input" value="30" min="1">
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-input" value="0">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Gambar Produk</label>
            <input type="file" name="prod_image" class="form-input" accept="image/jpeg,image/png,image/webp">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-select">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addProductModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Produk</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Product Modal -->
<?php if ($editProduct): ?>
<div class="modal-overlay visible" id="editProductModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/products.php'">
  <div class="modal-box" style="max-width:580px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Produk</h3><a href="<?= BASE_URL ?>/admin/products.php" class="btn-close">✕</a></div>
    <form method="POST" enctype="multipart/form-data">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_product">
      <input type="hidden" name="prod_id" value="<?= (int)$editProduct['id'] ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label>Kategori *</label>
            <select name="category_id" class="form-select" required>
              <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $c['id']==$editProduct['category_id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Min VIP Level</label>
            <select name="min_vip_level" class="form-select">
              <?php for($i=0;$i<=5;$i++): ?><option value="<?= $i ?>" <?= $i==$editProduct['min_vip_level']?'selected':'' ?>>VIP <?= $i ?></option><?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Nama Produk *</label>
          <input type="text" name="prod_name" class="form-input" value="<?= e($editProduct['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="prod_desc" class="form-input" rows="2"><?= e($editProduct['description']) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Harga (Rp) *</label>
            <input type="number" name="price" class="form-input" value="<?= (int)$editProduct['price'] ?>" required>
          </div>
          <div class="form-group">
            <label>Profit/Hari (Rp) *</label>
            <input type="number" name="profit_per_day" class="form-input" value="<?= (int)$editProduct['profit_per_day'] ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Durasi (hari)</label>
            <input type="number" name="duration_days" class="form-input" value="<?= (int)$editProduct['duration_days'] ?>">
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-input" value="<?= (int)$editProduct['sort_order'] ?>">
          </div>
        </div>
        <?php if ($editProduct['image']): ?>
        <div class="form-group">
          <label>Gambar Saat Ini</label>
          <img src="<?= BASE_URL ?>/uploads/<?= e($editProduct['image']) ?>" style="height:60px;border-radius:6px" alt="">
        </div>
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label>Ganti Gambar (opsional)</label>
            <input type="file" name="prod_image" class="form-input" accept="image/jpeg,image/png,image/webp">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-select">
              <option value="1" <?= $editProduct['is_active']?'selected':'' ?>>Aktif</option>
              <option value="0" <?= !$editProduct['is_active']?'selected':'' ?>>Nonaktif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Add Category Modal -->
<div class="modal-overlay hidden" id="addCatModal">
  <div class="modal-box" style="max-width:400px">
    <div class="modal-header"><h3>Tambah Kategori</h3><button class="btn-close" onclick="closeModal('addCatModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_category">
      <div class="modal-body">
        <div class="form-group"><label>Nama Kategori *</label><input type="text" name="cat_name" class="form-input" required></div>
        <div class="form-group"><label>Deskripsi</label><input type="text" name="cat_desc" class="form-input"></div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="cat_sort" class="form-input" value="0"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addCatModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Category Modal -->
<?php if ($editCategory): ?>
<div class="modal-overlay visible" id="editCatModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/products.php'">
  <div class="modal-box" style="max-width:400px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Kategori</h3><a href="<?= BASE_URL ?>/admin/products.php" class="btn-close">✕</a></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_category"><input type="hidden" name="cat_id" value="<?= (int)$editCategory['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Nama *</label><input type="text" name="cat_name" class="form-input" value="<?= e($editCategory['name']) ?>" required></div>
        <div class="form-group"><label>Deskripsi</label><input type="text" name="cat_desc" class="form-input" value="<?= e($editCategory['description']) ?>"></div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="cat_sort" class="form-input" value="<?= (int)$editCategory['sort_order'] ?>"></div>
        <div class="form-group"><label>Status</label><select name="cat_active" class="form-select"><option value="1" <?= $editCategory['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$editCategory['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-ghost">Batal</a>
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
