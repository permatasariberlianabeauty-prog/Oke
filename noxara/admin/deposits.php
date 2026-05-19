<?php
/**
 * NOXARA Admin - Kelola Deposit
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/wallet.php';
requireAdmin(ROLE_CS);

$pageTitle = 'Kelola Deposit';
$adminId   = SessionManager::adminId();

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . '/admin/deposits.php');
        exit;
    }

    $action    = $_POST['action'] ?? '';
    $depositId = (int)($_POST['deposit_id'] ?? 0);

    if ($action === 'confirm_deposit' && $depositId) {
        $note   = clean($_POST['admin_note'] ?? '');
        $result = confirmDeposit($depositId, $adminId, $note);
        if ($result['success']) {
            logActivity('confirm_deposit', 'deposit', $depositId, 'Konfirmasi deposit #' . $depositId);
            setFlashPopup('success', 'Deposit #' . $depositId . ' berhasil dikonfirmasi.');
        } else {
            setFlashPopup('error', $result['message'] ?? 'Gagal konfirmasi deposit.');
        }
    } elseif ($action === 'reject_deposit' && $depositId) {
        $reason = clean($_POST['reject_reason'] ?? 'Ditolak oleh admin');
        $result = rejectDeposit($depositId, $adminId, $reason);
        if ($result['success']) {
            logActivity('reject_deposit', 'deposit', $depositId, "Tolak deposit #{$depositId}. Alasan: {$reason}");
            setFlashPopup('success', 'Deposit #' . $depositId . ' berhasil ditolak.');
        } else {
            setFlashPopup('error', $result['message'] ?? 'Gagal menolak deposit.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/deposits.php?tab=' . ($_POST['current_tab'] ?? 'pending'));
    exit;
}

// ── FILTERS ───────────────────────────────────────────────────
$tab       = $_GET['tab'] ?? 'pending';
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = ADMIN_PER_PAGE;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($tab === 'pending') {
    $where[] = "d.status = 'pending'";
}
if ($dateFrom) {
    $where[]  = 'd.created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
    $types   .= 's';
}
if ($dateTo) {
    $where[]  = 'd.created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
    $types   .= 's';
}
$whereStr = implode(' AND ', $where);

$totalRow = db()->fetchOne(
    "SELECT COUNT(*) as cnt FROM deposits d WHERE {$whereStr}", $types, $params
);
$total   = (int)($totalRow['cnt'] ?? 0);
$pagInfo = paginate($total, $page, $perPage);

$limitParams = array_merge($params, [$perPage, $pagInfo['offset']]);
$limitTypes  = $types . 'ii';

$deposits = db()->fetchAll(
    "SELECT d.*, u.username, u.full_name, b.bank_name, b.account_number, b.account_name
     FROM deposits d
     JOIN users u ON u.id = d.user_id
     LEFT JOIN admin_bank_accounts b ON b.id = d.admin_bank_id
     WHERE {$whereStr}
     ORDER BY d.created_at DESC LIMIT ? OFFSET ?",
    $limitTypes, $limitParams
);

// View single
$viewDeposit = null;
$viewId = (int)($_GET['id'] ?? 0);
if ($viewId > 0) {
    $viewDeposit = db()->fetchOne(
        "SELECT d.*, u.username, u.full_name, u.email, b.bank_name, b.account_number, b.account_name
         FROM deposits d JOIN users u ON u.id=d.user_id
         LEFT JOIN admin_bank_accounts b ON b.id=d.admin_bank_id
         WHERE d.id=? LIMIT 1",
        'i', [$viewId]
    );
}

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Tabs -->
<div class="tabs" style="margin-bottom:20px">
  <a href="?tab=pending" class="tab <?= $tab==='pending'?'active':'' ?>">Pending</a>
  <a href="?tab=all" class="tab <?= $tab==='all'?'active':'' ?>">Semua</a>
</div>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:20px">
  <form method="GET" class="filter-row">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <input type="date" name="date_from" class="form-input" value="<?= e($dateFrom) ?>" placeholder="Dari tanggal">
    <input type="date" name="date_to" class="form-input" value="<?= e($dateTo) ?>" placeholder="Sampai tanggal">
    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    <a href="?tab=<?= e($tab) ?>" class="btn btn-sm btn-ghost">Reset</a>
  </form>
</div>

<!-- Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Daftar Deposit (<?= number_format($total) ?> data)</h3>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>#</th><th>Member</th><th>Jumlah</th><th>Kode Unik</th><th>Total Transfer</th><th>Bank Tujuan</th><th>Bukti</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (empty($deposits)): ?>
        <tr><td colspan="10" class="text-center text-muted">Tidak ada data deposit</td></tr>
      <?php else: ?>
        <?php foreach ($deposits as $d): ?>
        <tr>
          <td><?= (int)$d['id'] ?></td>
          <td>
            <div class="fw-600"><?= e($d['username']) ?></div>
            <div class="text-xs text-muted"><?= e($d['full_name']) ?></div>
          </td>
          <td><?= formatRupiah((float)$d['amount']) ?></td>
          <td class="text-muted">+<?= (int)$d['unique_code'] ?></td>
          <td class="fw-600 text-cyan"><?= formatRupiah((float)$d['total_amount']) ?></td>
          <td class="text-xs"><?= e($d['bank_name'] ?? '-') ?><br><?= e($d['account_number'] ?? '') ?></td>
          <td>
            <?php if ($d['proof_image']): ?>
              <a href="<?= BASE_URL ?>/uploads/<?= e($d['proof_image']) ?>" target="_blank" class="btn btn-xs btn-ghost" onclick="showProofImage('<?= BASE_URL ?>/uploads/<?= e($d['proof_image']) ?>');return false">Lihat</a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?= formatDate($d['created_at'], 'd M Y H:i') ?></td>
          <td>
            <?php
            $badges = ['pending'=>'badge-warning','confirmed'=>'badge-success','rejected'=>'badge-error','expired'=>'badge-secondary'];
            $labels = ['pending'=>'Pending','confirmed'=>'Konfirmasi','rejected'=>'Ditolak','expired'=>'Kadaluarsa'];
            ?>
            <span class="badge <?= $badges[$d['status']] ?? 'badge-secondary' ?>"><?= $labels[$d['status']] ?? e($d['status']) ?></span>
          </td>
          <td>
            <?php if ($d['status'] === 'pending'): ?>
            <div class="action-btns">
              <button class="btn btn-xs btn-success" onclick="openConfirmModal(<?= (int)$d['id'] ?>, '<?= e($d['username']) ?>', '<?= e(formatRupiah((float)$d['amount'])) ?>')">Konfirmasi</button>
              <button class="btn btn-xs btn-danger" onclick="openRejectModal(<?= (int)$d['id'] ?>, '<?= e($d['username']) ?>')">Tolak</button>
            </div>
            <?php else: ?>
              <a href="?tab=<?= e($tab) ?>&id=<?= (int)$d['id'] ?>" class="btn btn-xs btn-ghost">Detail</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pagInfo['total_pages'] > 1): ?>
  <div class="pagination">
    <?php if ($pagInfo['has_prev']): ?><a href="?tab=<?= e($tab) ?>&page=<?= $pagInfo['current']-1 ?>&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="btn btn-xs btn-ghost">‹ Prev</a><?php endif; ?>
    <span class="page-info">Halaman <?= $pagInfo['current'] ?> / <?= $pagInfo['total_pages'] ?></span>
    <?php if ($pagInfo['has_next']): ?><a href="?tab=<?= e($tab) ?>&page=<?= $pagInfo['current']+1 ?>&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="btn btn-xs btn-ghost">Next ›</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Proof Image Modal -->
<div class="modal-overlay hidden" id="proofModal" onclick="closeModal('proofModal')">
  <div class="modal-box" style="max-width:600px;background:#0A0E1A" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Bukti Transfer</h3><button class="btn-close" onclick="closeModal('proofModal')">✕</button></div>
    <div class="modal-body" style="text-align:center">
      <img id="proofImg" src="" alt="Bukti Transfer" style="max-width:100%;border-radius:8px">
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="modal-overlay hidden" id="confirmModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3>Konfirmasi Deposit</h3><button class="btn-close" onclick="closeModal('confirmModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="confirm_deposit">
      <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
      <input type="hidden" name="deposit_id" id="confirmDepositId">
      <div class="modal-body">
        <p>Konfirmasi deposit dari <strong id="confirmUsername"></strong> sebesar <strong id="confirmAmount"></strong>?</p>
        <div class="form-group" style="margin-top:16px">
          <label>Catatan Admin (opsional)</label>
          <input type="text" name="admin_note" class="form-input" placeholder="Catatan...">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('confirmModal')">Batal</button>
        <button type="submit" class="btn btn-success">Ya, Konfirmasi</button>
      </div>
    </form>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay hidden" id="rejectModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3>Tolak Deposit</h3><button class="btn-close" onclick="closeModal('rejectModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?>
      <input type="hidden" name="action" value="reject_deposit">
      <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
      <input type="hidden" name="deposit_id" id="rejectDepositId">
      <div class="modal-body">
        <p>Tolak deposit dari <strong id="rejectUsername"></strong>?</p>
        <div class="form-group" style="margin-top:16px">
          <label>Alasan Penolakan *</label>
          <input type="text" name="reject_reason" class="form-input" placeholder="Masukkan alasan..." required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('rejectModal')">Batal</button>
        <button type="submit" class="btn btn-danger">Ya, Tolak</button>
      </div>
    </form>
  </div>
</div>

<?php if ($viewDeposit): ?>
<div class="modal-overlay visible" id="viewDepositModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/deposits.php?tab=<?= e($tab) ?>'">
  <div class="modal-box" style="max-width:520px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Detail Deposit #<?= (int)$viewDeposit['id'] ?></h3><a href="<?= BASE_URL ?>/admin/deposits.php?tab=<?= e($tab) ?>" class="btn-close">✕</a></div>
    <div class="modal-body">
      <div class="detail-grid">
        <div class="detail-item"><span>Member</span><strong><?= e($viewDeposit['username']) ?> (<?= e($viewDeposit['full_name']) ?>)</strong></div>
        <div class="detail-item"><span>Email</span><strong><?= e($viewDeposit['email']) ?></strong></div>
        <div class="detail-item"><span>Jumlah</span><strong><?= formatRupiah((float)$viewDeposit['amount']) ?></strong></div>
        <div class="detail-item"><span>Kode Unik</span><strong>+<?= (int)$viewDeposit['unique_code'] ?></strong></div>
        <div class="detail-item"><span>Total Transfer</span><strong class="text-cyan"><?= formatRupiah((float)$viewDeposit['total_amount']) ?></strong></div>
        <div class="detail-item"><span>Bank Tujuan</span><strong><?= e($viewDeposit['bank_name'] ?? '-') ?></strong></div>
        <div class="detail-item"><span>No. Rekening</span><strong><?= e($viewDeposit['account_number'] ?? '-') ?></strong></div>
        <div class="detail-item"><span>Status</span><strong><?= e($viewDeposit['status']) ?></strong></div>
        <div class="detail-item"><span>Tanggal</span><strong><?= formatDate($viewDeposit['created_at'], 'd M Y H:i') ?></strong></div>
        <?php if ($viewDeposit['admin_note']): ?><div class="detail-item" style="grid-column:1/-1"><span>Catatan Admin</span><strong><?= e($viewDeposit['admin_note']) ?></strong></div><?php endif; ?>
      </div>
      <?php if ($viewDeposit['proof_image']): ?>
      <div style="text-align:center;margin-top:16px">
        <img src="<?= BASE_URL ?>/uploads/<?= e($viewDeposit['proof_image']) ?>" style="max-width:100%;border-radius:8px;max-height:300px" alt="Bukti">
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function showProofImage(src) {
  document.getElementById('proofImg').src = src;
  document.getElementById('proofModal').classList.remove('hidden');
  document.getElementById('proofModal').classList.add('visible');
}
function openConfirmModal(id, user, amount) {
  document.getElementById('confirmDepositId').value = id;
  document.getElementById('confirmUsername').textContent = user;
  document.getElementById('confirmAmount').textContent = amount;
  document.getElementById('confirmModal').classList.remove('hidden');
  document.getElementById('confirmModal').classList.add('visible');
}
function openRejectModal(id, user) {
  document.getElementById('rejectDepositId').value = id;
  document.getElementById('rejectUsername').textContent = user;
  document.getElementById('rejectModal').classList.remove('hidden');
  document.getElementById('rejectModal').classList.add('visible');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('visible');
  document.getElementById(id).classList.add('hidden');
}
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
