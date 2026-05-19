<?php
/**
 * NOXARA Admin - Kelola Voucher
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Kelola Voucher';
$adminId   = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/vouchers.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_voucher' || $action === 'edit_voucher') {
        $voucherId    = (int)($_POST['voucher_id'] ?? 0);
        $code         = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['code'] ?? ''));
        $type         = in_array($_POST['v_type'] ?? '', ['deposit','product']) ? $_POST['v_type'] : 'deposit';
        $discType     = $_POST['discount_type'] === 'nominal' ? 'nominal' : 'percent';
        $discVal      = (float)($_POST['discount_value'] ?? 0);
        $minVip       = (int)($_POST['min_vip_level'] ?? 0);
        $minAmount    = (float)($_POST['min_amount'] ?? 0);
        $maxDiscount  = (float)($_POST['max_discount'] ?? 0);
        $usageLimit   = (int)($_POST['usage_limit'] ?? 0);
        $expiredAt    = $_POST['expired_at'] ?? null;
        $isActive     = (int)($_POST['is_active'] ?? 1);

        if (empty($code)) {
            setFlashPopup('error', 'Kode voucher wajib diisi.');
        } elseif ($action === 'add_voucher') {
            // Check unique
            $exists = db()->fetchOne('SELECT id FROM vouchers WHERE code=? LIMIT 1', 's', [$code]);
            if ($exists) {
                setFlashPopup('error', 'Kode voucher sudah digunakan.');
            } else {
                db()->execute(
                    'INSERT INTO vouchers (code,type,discount_type,discount_value,min_vip_level,min_amount,max_discount,usage_limit,is_active,expired_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    'sssdiiddisi', [$code,$type,$discType,$discVal,$minVip,$minAmount,$maxDiscount,$usageLimit,$isActive,$expiredAt?:null,$adminId]
                );
                logActivity('add_voucher', 'voucher', (int)db()->lastInsertId(), "Tambah voucher: {$code}");
                setFlashPopup('success', "Voucher {$code} berhasil ditambahkan.");
            }
        } else {
            db()->execute(
                'UPDATE vouchers SET code=?,type=?,discount_type=?,discount_value=?,min_vip_level=?,min_amount=?,max_discount=?,usage_limit=?,is_active=?,expired_at=? WHERE id=?',
                'sssdiiddiisi', [$code,$type,$discType,$discVal,$minVip,$minAmount,$maxDiscount,$usageLimit,$isActive,$expiredAt?:null,$voucherId]
            );
            logActivity('edit_voucher', 'voucher', $voucherId, "Edit voucher: {$code}");
            setFlashPopup('success', 'Voucher berhasil diperbarui.');
        }
    } elseif ($action === 'deactivate_voucher') {
        $voucherId = (int)($_POST['voucher_id'] ?? 0);
        if ($voucherId) {
            db()->execute('UPDATE vouchers SET is_active=0 WHERE id=?', 'i', [$voucherId]);
            logActivity('deactivate_voucher', 'voucher', $voucherId, "Nonaktifkan voucher #{$voucherId}");
            setFlashPopup('success', 'Voucher dinonaktifkan.');
        }
    } elseif ($action === 'delete_voucher') {
        $voucherId = (int)($_POST['voucher_id'] ?? 0);
        if ($voucherId) {
            db()->execute('DELETE FROM vouchers WHERE id=?', 'i', [$voucherId]);
            logActivity('delete_voucher', 'voucher', $voucherId, "Hapus voucher #{$voucherId}");
            setFlashPopup('success', 'Voucher dihapus.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/vouchers.php');
    exit;
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = ADMIN_PER_PAGE;
$total   = (int)(db()->fetchOne('SELECT COUNT(*) as cnt FROM vouchers')['cnt'] ?? 0);
$pagInfo = paginate($total, $page, $perPage);

$vouchers = db()->fetchAll(
    'SELECT * FROM vouchers ORDER BY created_at DESC LIMIT ? OFFSET ?',
    'ii', [$perPage, $pagInfo['offset']]
);

$editVoucher = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) $editVoucher = db()->fetchOne('SELECT * FROM vouchers WHERE id=? LIMIT 1', 'i', [$editId]);

// Auto-generate code
$autoCode = generateCode(8);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h3>Daftar Voucher (<?= number_format($total) ?>)</h3>
    <button class="btn btn-sm btn-primary" onclick="document.getElementById('addVoucherModal').classList.replace('hidden','visible')">+ Tambah Voucher</button>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Kode</th><th>Tipe</th><th>Diskon</th><th>Min Amount</th><th>Terpakai</th><th>Limit</th><th>Kadaluarsa</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (empty($vouchers)): ?>
        <tr><td colspan="10" class="text-center text-muted">Belum ada voucher</td></tr>
      <?php else: ?>
        <?php foreach ($vouchers as $v): ?>
        <tr>
          <td><?= (int)$v['id'] ?></td>
          <td><code style="font-size:13px;font-weight:700;color:#00D4FF"><?= e($v['code']) ?></code></td>
          <td><span class="badge badge-secondary"><?= ucfirst(e($v['type'])) ?></span></td>
          <td>
            <?= $v['discount_type']==='percent' ? number_format((float)$v['discount_value'],2).'%' : formatRupiah((float)$v['discount_value']) ?>
            <?php if ($v['max_discount'] > 0): ?><div class="text-xs text-muted">Maks <?= formatRupiah((float)$v['max_discount']) ?></div><?php endif; ?>
          </td>
          <td><?= $v['min_amount']>0 ? formatRupiah((float)$v['min_amount']) : '-' ?></td>
          <td><?= (int)$v['used_count'] ?></td>
          <td><?= (int)$v['usage_limit'] ?: '∞' ?></td>
          <td><?= $v['expired_at'] ? formatDate($v['expired_at'], 'd M Y') : '∞' ?></td>
          <td>
            <?php if (!$v['is_active']): ?><span class="badge badge-error">Nonaktif</span>
            <?php elseif ($v['expired_at'] && strtotime($v['expired_at']) < time()): ?><span class="badge badge-warning">Kadaluarsa</span>
            <?php else: ?><span class="badge badge-success">Aktif</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= (int)$v['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
              <?php if ($v['is_active']): ?>
              <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="deactivate_voucher"><input type="hidden" name="voucher_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-xs btn-warning" onclick="return confirm('Nonaktifkan voucher?')">Nonaktif</button></form>
              <?php endif; ?>
              <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_voucher"><input type="hidden" name="voucher_id" value="<?= (int)$v['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus voucher permanen?')">Hapus</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pagInfo['total_pages'] > 1): ?>
  <div class="pagination">
    <?php if ($pagInfo['has_prev']): ?><a href="?page=<?= $pagInfo['current']-1 ?>" class="btn btn-xs btn-ghost">‹ Prev</a><?php endif; ?>
    <span class="page-info">Halaman <?= $pagInfo['current'] ?> / <?= $pagInfo['total_pages'] ?></span>
    <?php if ($pagInfo['has_next']): ?><a href="?page=<?= $pagInfo['current']+1 ?>" class="btn btn-xs btn-ghost">Next ›</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay hidden" id="addVoucherModal">
  <div class="modal-box" style="max-width:540px">
    <div class="modal-header"><h3>Tambah Voucher</h3><button class="btn-close" onclick="closeModal('addVoucherModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_voucher">
      <div class="modal-body">
        <div class="form-group">
          <label>Kode Voucher *</label>
          <div style="display:flex;gap:8px">
            <input type="text" name="code" id="voucherCode" class="form-input" value="<?= e($autoCode) ?>" required style="text-transform:uppercase;flex:1">
            <button type="button" class="btn btn-sm btn-ghost" onclick="generateVoucherCode()">Auto</button>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Tipe Voucher</label><select name="v_type" class="form-select"><option value="deposit">Deposit</option><option value="product">Produk</option></select></div>
          <div class="form-group"><label>Tipe Diskon</label><select name="discount_type" class="form-select"><option value="percent">Persentase (%)</option><option value="nominal">Nominal (Rp)</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Nilai Diskon</label><input type="number" name="discount_value" class="form-input" min="0" step="0.01" required></div>
          <div class="form-group"><label>Maks Diskon (Rp, 0=no limit)</label><input type="number" name="max_discount" class="form-input" value="0" min="0"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Min Amount (Rp)</label><input type="number" name="min_amount" class="form-input" value="0" min="0"></div>
          <div class="form-group"><label>Min VIP Level</label><select name="min_vip_level" class="form-select"><?php for($i=0;$i<=5;$i++): ?><option value="<?= $i ?>">VIP <?= $i ?></option><?php endfor; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Limit Pemakaian (0=∞)</label><input type="number" name="usage_limit" class="form-input" value="0" min="0"></div>
          <div class="form-group"><label>Kadaluarsa</label><input type="datetime-local" name="expired_at" class="form-input"></div>
        </div>
        <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addVoucherModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Voucher</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<?php if ($editVoucher): ?>
<div class="modal-overlay visible" id="editVoucherModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/vouchers.php'">
  <div class="modal-box" style="max-width:540px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Voucher</h3><a href="<?= BASE_URL ?>/admin/vouchers.php" class="btn-close">✕</a></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_voucher"><input type="hidden" name="voucher_id" value="<?= (int)$editVoucher['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Kode *</label><input type="text" name="code" class="form-input" value="<?= e($editVoucher['code']) ?>" required style="text-transform:uppercase"></div>
        <div class="form-row">
          <div class="form-group"><label>Tipe</label><select name="v_type" class="form-select"><option value="deposit" <?= $editVoucher['type']==='deposit'?'selected':'' ?>>Deposit</option><option value="product" <?= $editVoucher['type']==='product'?'selected':'' ?>>Produk</option></select></div>
          <div class="form-group"><label>Tipe Diskon</label><select name="discount_type" class="form-select"><option value="percent" <?= $editVoucher['discount_type']==='percent'?'selected':'' ?>>Persen (%)</option><option value="nominal" <?= $editVoucher['discount_type']==='nominal'?'selected':'' ?>>Nominal (Rp)</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Nilai Diskon</label><input type="number" name="discount_value" class="form-input" value="<?= (float)$editVoucher['discount_value'] ?>" step="0.01"></div>
          <div class="form-group"><label>Maks Diskon</label><input type="number" name="max_discount" class="form-input" value="<?= (float)$editVoucher['max_discount'] ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Min Amount</label><input type="number" name="min_amount" class="form-input" value="<?= (float)$editVoucher['min_amount'] ?>"></div>
          <div class="form-group"><label>Min VIP</label><select name="min_vip_level" class="form-select"><?php for($i=0;$i<=5;$i++): ?><option value="<?= $i ?>" <?= (int)$editVoucher['min_vip_level']===$i?'selected':'' ?>>VIP <?= $i ?></option><?php endfor; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Limit</label><input type="number" name="usage_limit" class="form-input" value="<?= (int)$editVoucher['usage_limit'] ?>"></div>
          <div class="form-group"><label>Kadaluarsa</label><input type="datetime-local" name="expired_at" class="form-input" value="<?= $editVoucher['expired_at'] ? date('Y-m-d\TH:i', strtotime($editVoucher['expired_at'])) : '' ?>"></div>
        </div>
        <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?= $editVoucher['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$editVoucher['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/vouchers.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function closeModal(id){document.getElementById(id).classList.replace('visible','hidden')}
function generateVoucherCode(){
  const chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code='';
  for(let i=0;i<8;i++) code+=chars[Math.floor(Math.random()*chars.length)];
  document.getElementById('voucherCode').value=code;
}
</script>
<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
