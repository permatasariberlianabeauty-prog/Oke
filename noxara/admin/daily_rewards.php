<?php
/**
 * NOXARA Admin - Pengaturan Hadiah Harian
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Hadiah Harian';
$adminId   = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/daily_rewards.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $isEnabled  = (int)($_POST['is_enabled'] ?? 0);
        $resetHour  = max(0, min(23, (int)($_POST['reset_hour'] ?? 0)));
        db()->execute(
            'INSERT INTO daily_reward_settings (id,is_enabled,reset_hour) VALUES (1,?,?) ON DUPLICATE KEY UPDATE is_enabled=?,reset_hour=?',
            'iiii', [$isEnabled,$resetHour,$isEnabled,$resetHour]
        );
        logActivity('update_daily_reward_settings', 'daily_reward_settings', 0, 'Update daily reward settings');
        setFlashPopup('success', 'Pengaturan hadiah harian disimpan.');
    } elseif ($action === 'add_item') {
        $name     = clean($_POST['item_name'] ?? '');
        $type     = in_array($_POST['item_type'] ?? '', ['free_balance','extra_ad','boost_profit','jackpot']) ? $_POST['item_type'] : 'free_balance';
        $value    = (float)($_POST['item_value'] ?? 0);
        $prob     = (float)($_POST['item_probability'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);
        if ($name) {
            db()->execute(
                'INSERT INTO daily_reward_items (name,type,value,probability,is_active) VALUES (?,?,?,?,?)',
                'ssddi', [$name,$type,$value,$prob,$isActive]
            );
            logActivity('add_daily_reward_item', 'daily_reward_items', (int)db()->lastInsertId(), "Tambah item: {$name}");
            setFlashPopup('success', 'Item hadiah ditambahkan.');
        }
    } elseif ($action === 'edit_item') {
        $itemId   = (int)($_POST['item_id'] ?? 0);
        $name     = clean($_POST['item_name'] ?? '');
        $type     = in_array($_POST['item_type'] ?? '', ['free_balance','extra_ad','boost_profit','jackpot']) ? $_POST['item_type'] : 'free_balance';
        $value    = (float)($_POST['item_value'] ?? 0);
        $prob     = (float)($_POST['item_probability'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        if ($itemId && $name) {
            db()->execute(
                'UPDATE daily_reward_items SET name=?,type=?,value=?,probability=?,is_active=? WHERE id=?',
                'ssddii', [$name,$type,$value,$prob,$isActive,$itemId]
            );
            logActivity('edit_daily_reward_item', 'daily_reward_items', $itemId, "Edit item: {$name}");
            setFlashPopup('success', 'Item hadiah diperbarui.');
        }
    } elseif ($action === 'delete_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId) {
            db()->execute('DELETE FROM daily_reward_items WHERE id=?', 'i', [$itemId]);
            logActivity('delete_daily_reward_item', 'daily_reward_items', $itemId, "Hapus item #{$itemId}");
            setFlashPopup('success', 'Item dihapus.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/daily_rewards.php');
    exit;
}

$settings  = db()->fetchOne('SELECT * FROM daily_reward_settings WHERE id=1 LIMIT 1') ?? ['is_enabled'=>1,'reset_hour'=>0];
$items     = db()->fetchAll('SELECT * FROM daily_reward_items ORDER BY is_active DESC, id ASC');
$totalProb = array_sum(array_column(array_filter($items, fn($i)=>$i['is_active']), 'probability'));
$editItem  = null;
$editId    = (int)($_GET['edit'] ?? 0);
if ($editId > 0) $editItem = db()->fetchOne('SELECT * FROM daily_reward_items WHERE id=? LIMIT 1', 'i', [$editId]);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Settings -->
<div class="admin-card" style="margin-bottom:24px">
  <div class="admin-card-header"><h3>Pengaturan Sistem Hadiah Harian</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="action" value="save_settings">
    <div class="form-row">
      <div class="form-group">
        <label>Status Hadiah Harian</label>
        <select name="is_enabled" class="form-select">
          <option value="1" <?= $settings['is_enabled']?'selected':'' ?>>Aktif</option>
          <option value="0" <?= !$settings['is_enabled']?'selected':'' ?>>Nonaktif</option>
        </select>
      </div>
      <div class="form-group">
        <label>Jam Reset Harian (0–23 WIB)</label>
        <input type="number" name="reset_hour" class="form-input" value="<?= (int)$settings['reset_hour'] ?>" min="0" max="23">
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Simpan Pengaturan</button>
  </form>
</div>

<!-- Probability Warning -->
<?php if (abs($totalProb - 100) > 0.01): ?>
<div class="alert-warning" style="background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);border-radius:10px;padding:12px 16px;color:#fbbf24;margin-bottom:20px">
  ⚠️ Total probabilitas item aktif saat ini: <strong><?= number_format($totalProb, 2) ?>%</strong>. Harus tepat <strong>100%</strong> agar sistem berfungsi dengan benar.
</div>
<?php else: ?>
<div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:10px 16px;color:#22c55e;margin-bottom:20px;font-size:14px">
  ✓ Total probabilitas aktif: <?= number_format($totalProb, 2) ?>% — OK
</div>
<?php endif; ?>

<!-- Items List -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Item Hadiah (<?= count($items) ?>)</h3>
    <button class="btn btn-sm btn-primary" onclick="document.getElementById('addItemModal').classList.replace('hidden','visible')">+ Tambah Item</button>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Nama</th><th>Tipe</th><th>Nilai</th><th>Probabilitas</th><th>Aktif</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="7" class="text-center text-muted">Belum ada item</td></tr>
      <?php else: ?>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><?= (int)$item['id'] ?></td>
          <td><?= e($item['name']) ?></td>
          <td>
            <?php $typeLabels=['free_balance'=>'Saldo Gratis','extra_ad'=>'Extra Iklan','boost_profit'=>'Boost Profit','jackpot'=>'Jackpot']; ?>
            <span class="badge badge-secondary"><?= e($typeLabels[$item['type']] ?? $item['type']) ?></span>
          </td>
          <td><?= $item['type'] === 'free_balance' || $item['type'] === 'jackpot' ? formatRupiah((float)$item['value']) : e((float)$item['value']) ?></td>
          <td>
            <span style="display:inline-flex;align-items:center;gap:8px">
              <?= number_format((float)$item['probability'],2) ?>%
              <span style="display:inline-block;width:60px;height:6px;background:rgba(255,255,255,.1);border-radius:4px;overflow:hidden"><span style="display:block;height:100%;background:#00D4FF;width:<?= min(100, (float)$item['probability']) ?>%"></span></span>
            </span>
          </td>
          <td><span class="badge <?= $item['is_active']?'badge-success':'badge-error' ?>"><?= $item['is_active']?'Ya':'Tidak' ?></span></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= (int)$item['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
              <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_item"><input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus item?')">Hapus</button></form>
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
<div class="modal-overlay hidden" id="addItemModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3>Tambah Item Hadiah</h3><button class="btn-close" onclick="closeModal('addItemModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_item">
      <div class="modal-body">
        <div class="form-group"><label>Nama Item *</label><input type="text" name="item_name" class="form-input" required></div>
        <div class="form-group">
          <label>Tipe</label>
          <select name="item_type" class="form-select">
            <option value="free_balance">Saldo Gratis</option>
            <option value="extra_ad">Extra Kuota Iklan</option>
            <option value="boost_profit">Boost Profit</option>
            <option value="jackpot">Jackpot</option>
          </select>
        </div>
        <div class="form-group"><label>Nilai (Rp atau Multiplier)</label><input type="number" name="item_value" class="form-input" min="0" step="0.01" required></div>
        <div class="form-group"><label>Probabilitas (%) — Total aktif harus = 100%</label><input type="number" name="item_probability" class="form-input" min="0" max="100" step="0.01" required></div>
        <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addItemModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<?php if ($editItem): ?>
<div class="modal-overlay visible" id="editItemModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/daily_rewards.php'">
  <div class="modal-box" style="max-width:420px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Item Hadiah</h3><a href="<?= BASE_URL ?>/admin/daily_rewards.php" class="btn-close">✕</a></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_item"><input type="hidden" name="item_id" value="<?= (int)$editItem['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Nama *</label><input type="text" name="item_name" class="form-input" value="<?= e($editItem['name']) ?>" required></div>
        <div class="form-group"><label>Tipe</label><select name="item_type" class="form-select"><option value="free_balance" <?= $editItem['type']==='free_balance'?'selected':'' ?>>Saldo Gratis</option><option value="extra_ad" <?= $editItem['type']==='extra_ad'?'selected':'' ?>>Extra Iklan</option><option value="boost_profit" <?= $editItem['type']==='boost_profit'?'selected':'' ?>>Boost Profit</option><option value="jackpot" <?= $editItem['type']==='jackpot'?'selected':'' ?>>Jackpot</option></select></div>
        <div class="form-group"><label>Nilai</label><input type="number" name="item_value" class="form-input" value="<?= (float)$editItem['value'] ?>" step="0.01"></div>
        <div class="form-group"><label>Probabilitas (%)</label><input type="number" name="item_probability" class="form-input" value="<?= (float)$editItem['probability'] ?>" step="0.01"></div>
        <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?= $editItem['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$editItem['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/daily_rewards.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>function closeModal(id){document.getElementById(id).classList.replace('visible','hidden')}</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
