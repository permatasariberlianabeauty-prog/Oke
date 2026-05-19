<?php
/**
 * NOXARA Admin - Data Member
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/ledger.php';
requireAdmin();

$pageTitle    = 'Data Member';
$adminId      = SessionManager::adminId();
$isSuperadmin = (SessionManager::adminRole() === ROLE_SUPERADMIN);

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . '/admin/members.php');
        exit;
    }

    $action   = $_POST['action'] ?? '';
    $memberId = (int)($_POST['member_id'] ?? 0);

    if ($action === 'block_member' && $memberId) {
        $reason = clean($_POST['block_reason'] ?? 'Diblokir oleh admin');
        db()->execute("UPDATE users SET is_blocked=1, block_reason=? WHERE id=?", 'si', [$reason, $memberId]);
        logActivity('block_member', 'user', $memberId, "Blokir member. Alasan: {$reason}");
        setFlashPopup('success', 'Member berhasil diblokir.');
    } elseif ($action === 'unblock_member' && $memberId) {
        db()->execute("UPDATE users SET is_blocked=0, block_reason=NULL WHERE id=?", 'i', [$memberId]);
        logActivity('unblock_member', 'user', $memberId, 'Buka blokir member');
        setFlashPopup('success', 'Member berhasil dibuka blokirnya.');
    } elseif ($action === 'freeze_balance' && $memberId) {
        db()->execute("UPDATE users SET is_frozen=1 WHERE id=?", 'i', [$memberId]);
        logActivity('freeze_balance', 'user', $memberId, 'Freeze saldo member');
        setFlashPopup('success', 'Saldo member berhasil dibekukan.');
    } elseif ($action === 'unfreeze_balance' && $memberId) {
        db()->execute("UPDATE users SET is_frozen=0 WHERE id=?", 'i', [$memberId]);
        logActivity('unfreeze_balance', 'user', $memberId, 'Unfreeze saldo member');
        setFlashPopup('success', 'Saldo member berhasil dicairkan.');
    } elseif ($action === 'adjust_balance' && $memberId && $isSuperadmin) {
        $walletType = $_POST['wallet_type'] === 'free' ? WALLET_FREE : WALLET_MAIN;
        $direction  = $_POST['direction'] === 'debit' ? DIR_DEBIT : DIR_CREDIT;
        $amount     = (float)($_POST['amount'] ?? 0);
        $reason     = clean($_POST['reason'] ?? 'Koreksi manual oleh admin');
        if ($amount > 0) {
            $result = adminAdjustBalance($memberId, $walletType, $direction, $amount, $reason, $adminId);
            setFlashPopup($result ? 'success' : 'error', $result ? 'Saldo berhasil disesuaikan.' : 'Gagal menyesuaikan saldo. Periksa saldo pengguna.');
        } else {
            setFlashPopup('error', 'Jumlah tidak valid.');
        }
    } elseif ($action === 'export_csv' && $isSuperadmin) {
        // Export CSV
        $rows = db()->fetchAll(
            "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.vip_level, u.is_active, u.is_blocked, u.is_frozen, u.created_at,
                    COALESCE(uw.main_balance,0) as main_balance, COALESCE(uw.free_balance,0) as free_balance,
                    COALESCE(uw.total_deposit,0) as total_deposit, COALESCE(uw.total_profit,0) as total_profit
             FROM users u LEFT JOIN user_wallets uw ON uw.user_id=u.id ORDER BY u.id ASC"
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="members_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Username','Nama','Email','HP','VIP','Aktif','Blokir','Freeze','Saldo Utama','Saldo Gratis','Total Deposit','Total Profit','Bergabung']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],$r['username'],$r['full_name'],$r['email'],$r['phone'],$r['vip_level'],
                $r['is_active']?'Ya':'Tidak', $r['is_blocked']?'Ya':'Tidak', $r['is_frozen']?'Ya':'Tidak',
                $r['main_balance'],$r['free_balance'],$r['total_deposit'],$r['total_profit'],$r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    header('Location: ' . BASE_URL . '/admin/members.php');
    exit;
}

// ── FILTERS & PAGINATION ──────────────────────────────────────
$search    = clean($_GET['q'] ?? '');
$vipFilter = isset($_GET['vip']) ? (int)$_GET['vip'] : -1;
$status    = $_GET['status'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = ADMIN_PER_PAGE;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.full_name LIKE ?)';
    $s = "%{$search}%";
    $params = array_merge($params, [$s,$s,$s,$s]);
    $types .= 'ssss';
}
if ($vipFilter >= 0) {
    $where[]  = 'u.vip_level = ?';
    $params[] = $vipFilter;
    $types   .= 'i';
}
if ($status === 'blocked') {
    $where[]  = 'u.is_blocked = 1';
} elseif ($status === 'inactive') {
    $where[]  = 'u.is_active = 0';
} elseif ($status === 'frozen') {
    $where[]  = 'u.is_frozen = 1';
}

$whereStr = implode(' AND ', $where);

$totalRow  = db()->fetchOne("SELECT COUNT(*) as cnt FROM users u WHERE {$whereStr}", $types, $params);
$total     = (int)($totalRow['cnt'] ?? 0);
$pagInfo   = paginate($total, $page, $perPage);

$limitParams = array_merge($params, [$perPage, $pagInfo['offset']]);
$limitTypes  = $types . 'ii';

$members = db()->fetchAll(
    "SELECT u.*, COALESCE(uw.main_balance,0) as main_balance, COALESCE(uw.total_deposit,0) as total_deposit,
            COALESCE(uw.total_profit,0) as total_profit
     FROM users u LEFT JOIN user_wallets uw ON uw.user_id=u.id
     WHERE {$whereStr} ORDER BY u.id DESC LIMIT ? OFFSET ?",
    $limitTypes, $limitParams
);

// View detail
$viewMember = null;
$viewId = (int)($_GET['id'] ?? 0);
if ($viewId > 0) {
    $viewMember = db()->fetchOne(
        "SELECT u.*, COALESCE(uw.main_balance,0) as main_balance, COALESCE(uw.free_balance,0) as free_balance,
                COALESCE(uw.total_deposit,0) as total_deposit, COALESCE(uw.total_withdraw,0) as total_withdraw,
                COALESCE(uw.total_profit,0) as total_profit, COALESCE(uw.total_referral,0) as total_referral
         FROM users u LEFT JOIN user_wallets uw ON uw.user_id=u.id WHERE u.id=? LIMIT 1",
        'i', [$viewId]
    );
}

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Search & Filter -->
<div class="admin-card" style="margin-bottom:20px">
  <form method="GET" action="" class="filter-row">
    <input type="text" name="q" class="form-input" placeholder="Cari username, email, HP..." value="<?= e($search) ?>">
    <select name="vip" class="form-select">
      <option value="-1" <?= $vipFilter<0?'selected':'' ?>>Semua VIP</option>
      <?php for($v=0;$v<=5;$v++): ?>
      <option value="<?= $v ?>" <?= $vipFilter===$v?'selected':'' ?>>VIP <?= $v ?></option>
      <?php endfor; ?>
    </select>
    <select name="status" class="form-select">
      <option value="" <?= $status===''?'selected':'' ?>>Semua Status</option>
      <option value="blocked" <?= $status==='blocked'?'selected':'' ?>>Diblokir</option>
      <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Tidak Aktif</option>
      <option value="frozen" <?= $status==='frozen'?'selected':'' ?>>Saldo Dibekukan</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
    <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-sm btn-ghost">Reset</a>
    <?php if ($isSuperadmin): ?>
    <form method="POST" action="" style="display:inline">
      <?= CSRF::field() ?><input type="hidden" name="action" value="export_csv">
      <button type="submit" class="btn btn-sm btn-secondary">Export CSV</button>
    </form>
    <?php endif; ?>
  </form>
</div>

<!-- Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Data Member (<?= number_format($total) ?> total)</h3>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>#</th><th>Username</th><th>Nama Lengkap</th><th>Email</th><th>VIP</th><th>Total Deposit</th><th>Total Profit</th><th>Status</th><th>Bergabung</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (empty($members)): ?>
        <tr><td colspan="10" class="text-center text-muted">Tidak ada data member</td></tr>
      <?php else: ?>
        <?php foreach ($members as $m): ?>
        <tr>
          <td><?= (int)$m['id'] ?></td>
          <td><strong><?= e($m['username']) ?></strong></td>
          <td><?= e($m['full_name']) ?></td>
          <td><?= e($m['email']) ?></td>
          <td><span class="badge badge-vip">VIP <?= (int)$m['vip_level'] ?></span></td>
          <td><?= formatRupiah((float)$m['total_deposit']) ?></td>
          <td><?= formatRupiah((float)$m['total_profit']) ?></td>
          <td>
            <?php if ($m['is_blocked']): ?><span class="badge badge-error">Blokir</span>
            <?php elseif (!$m['is_active']): ?><span class="badge badge-warning">Nonaktif</span>
            <?php elseif ($m['is_frozen']): ?><span class="badge badge-warning">Freeze</span>
            <?php else: ?><span class="badge badge-success">Aktif</span>
            <?php endif; ?>
          </td>
          <td><?= formatDate($m['created_at'], 'd M Y') ?></td>
          <td>
            <div class="action-btns">
              <a href="?id=<?= (int)$m['id'] ?>&<?= http_build_query(['q'=>$search,'vip'=>$vipFilter,'status'=>$status,'page'=>$page]) ?>" class="btn btn-xs btn-ghost">Detail</a>
              <?php if ($m['is_blocked']): ?>
                <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="unblock_member"><input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-xs btn-success" onclick="return confirm('Buka blokir member ini?')">Buka Blokir</button></form>
              <?php else: ?>
                <button class="btn btn-xs btn-danger" onclick="openBlockModal(<?= (int)$m['id'] ?>, '<?= e($m['username']) ?>')">Blokir</button>
              <?php endif; ?>
              <?php if ($m['is_frozen']): ?>
                <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="unfreeze_balance"><input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-xs btn-success" onclick="return confirm('Cairkan saldo?')">Unfreeze</button></form>
              <?php else: ?>
                <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="freeze_balance"><input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-xs btn-warning" onclick="return confirm('Bekukan saldo member ini?')">Freeze</button></form>
              <?php endif; ?>
              <?php if ($isSuperadmin): ?>
                <button class="btn btn-xs btn-primary" onclick="openAdjustModal(<?= (int)$m['id'] ?>, '<?= e($m['username']) ?>')">Koreksi</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pagInfo['total_pages'] > 1): ?>
  <div class="pagination">
    <?php if ($pagInfo['has_prev']): ?>
      <a href="?page=<?= $pagInfo['current']-1 ?>&q=<?= urlencode($search) ?>&vip=<?= $vipFilter ?>&status=<?= e($status) ?>" class="btn btn-xs btn-ghost">‹ Prev</a>
    <?php endif; ?>
    <span class="page-info">Halaman <?= $pagInfo['current'] ?> / <?= $pagInfo['total_pages'] ?></span>
    <?php if ($pagInfo['has_next']): ?>
      <a href="?page=<?= $pagInfo['current']+1 ?>&q=<?= urlencode($search) ?>&vip=<?= $vipFilter ?>&status=<?= e($status) ?>" class="btn btn-xs btn-ghost">Next ›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Detail Modal -->
<?php if ($viewMember): ?>
<div class="modal-overlay visible" id="detailModal" onclick="closeDetailModal(event)">
  <div class="modal-box" style="max-width:560px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <h3>Detail Member: <?= e($viewMember['username']) ?></h3>
      <a href="<?= BASE_URL ?>/admin/members.php" class="btn-close">✕</a>
    </div>
    <div class="modal-body">
      <div class="detail-grid">
        <div class="detail-item"><span>ID</span><strong><?= (int)$viewMember['id'] ?></strong></div>
        <div class="detail-item"><span>Username</span><strong><?= e($viewMember['username']) ?></strong></div>
        <div class="detail-item"><span>Nama</span><strong><?= e($viewMember['full_name']) ?></strong></div>
        <div class="detail-item"><span>Email</span><strong><?= e($viewMember['email']) ?></strong></div>
        <div class="detail-item"><span>HP</span><strong><?= e($viewMember['phone']) ?></strong></div>
        <div class="detail-item"><span>VIP Level</span><strong>VIP <?= (int)$viewMember['vip_level'] ?></strong></div>
        <div class="detail-item"><span>Saldo Utama</span><strong><?= formatRupiah((float)$viewMember['main_balance']) ?></strong></div>
        <div class="detail-item"><span>Saldo Gratis</span><strong><?= formatRupiah((float)$viewMember['free_balance']) ?></strong></div>
        <div class="detail-item"><span>Total Deposit</span><strong><?= formatRupiah((float)$viewMember['total_deposit']) ?></strong></div>
        <div class="detail-item"><span>Total Withdraw</span><strong><?= formatRupiah((float)$viewMember['total_withdraw']) ?></strong></div>
        <div class="detail-item"><span>Total Profit</span><strong><?= formatRupiah((float)$viewMember['total_profit']) ?></strong></div>
        <div class="detail-item"><span>Total Referral</span><strong><?= formatRupiah((float)$viewMember['total_referral']) ?></strong></div>
        <div class="detail-item"><span>Bergabung</span><strong><?= formatDate($viewMember['created_at'], 'd M Y H:i') ?></strong></div>
        <div class="detail-item"><span>Status Blokir</span><strong><?= $viewMember['is_blocked']?'<span class="badge badge-error">Diblokir</span>':'<span class="badge badge-success">Normal</span>' ?></strong></div>
        <?php if ($viewMember['block_reason']): ?>
        <div class="detail-item" style="grid-column:1/-1"><span>Alasan Blokir</span><strong><?= e($viewMember['block_reason']) ?></strong></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Block Modal -->
<div class="modal-overlay hidden" id="blockModal">
  <div class="modal-box" style="max-width:400px">
    <div class="modal-header"><h3>Blokir Member</h3><button class="btn-close" onclick="closeModal('blockModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="block_member">
      <input type="hidden" name="member_id" id="blockMemberId">
      <div class="modal-body">
        <p style="margin-bottom:12px">Blokir member: <strong id="blockMemberName"></strong></p>
        <div class="form-group">
          <label>Alasan Blokir</label>
          <input type="text" name="block_reason" class="form-input" placeholder="Masukkan alasan blokir..." required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('blockModal')">Batal</button>
        <button type="submit" class="btn btn-danger">Blokir Member</button>
      </div>
    </form>
  </div>
</div>

<!-- Adjust Balance Modal -->
<?php if ($isSuperadmin): ?>
<div class="modal-overlay hidden" id="adjustModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3>Koreksi Saldo</h3><button class="btn-close" onclick="closeModal('adjustModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="adjust_balance">
      <input type="hidden" name="member_id" id="adjustMemberId">
      <div class="modal-body">
        <p style="margin-bottom:16px">Member: <strong id="adjustMemberName"></strong></p>
        <div class="form-group">
          <label>Jenis Saldo</label>
          <select name="wallet_type" class="form-select">
            <option value="main">Saldo Utama</option>
            <option value="free">Saldo Gratis</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tipe Koreksi</label>
          <select name="direction" class="form-select">
            <option value="credit">Tambah Saldo (Credit)</option>
            <option value="debit">Kurangi Saldo (Debit)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Jumlah (Rp)</label>
          <input type="number" name="amount" class="form-input" min="1" step="1" placeholder="0" required>
        </div>
        <div class="form-group">
          <label>Alasan / Catatan</label>
          <input type="text" name="reason" class="form-input" placeholder="Alasan koreksi..." required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('adjustModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Koreksi</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function openBlockModal(id, name) {
  document.getElementById('blockMemberId').value = id;
  document.getElementById('blockMemberName').textContent = name;
  document.getElementById('blockModal').classList.remove('hidden');
  document.getElementById('blockModal').classList.add('visible');
}
function openAdjustModal(id, name) {
  document.getElementById('adjustMemberId').value = id;
  document.getElementById('adjustMemberName').textContent = name;
  document.getElementById('adjustModal').classList.remove('hidden');
  document.getElementById('adjustModal').classList.add('visible');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('visible');
  document.getElementById(id).classList.add('hidden');
}
function closeDetailModal(e) {
  if (e.target === document.getElementById('detailModal')) {
    window.location = '<?= BASE_URL ?>/admin/members.php';
  }
}
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
