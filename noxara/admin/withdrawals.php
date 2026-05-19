<?php
/**
 * NOXARA Admin - Kelola Withdraw
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/wallet.php';
requireAdmin(ROLE_FINANCE);

$pageTitle = 'Kelola Withdraw';
$adminId   = SessionManager::adminId();

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . '/admin/withdrawals.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $wdId   = (int)($_POST['wd_id'] ?? 0);

    if ($action === 'approve_withdraw' && $wdId) {
        $note   = clean($_POST['admin_note'] ?? '');
        $result = approveWithdraw($wdId, $adminId, $note);
        if ($result['success']) {
            logActivity('approve_withdraw', 'withdraw', $wdId, 'Setujui withdraw #' . $wdId);
            setFlashPopup('success', 'Withdraw #' . $wdId . ' berhasil disetujui.');
        } else {
            setFlashPopup('error', $result['message'] ?? 'Gagal menyetujui withdraw.');
        }
    } elseif ($action === 'reject_withdraw' && $wdId) {
        $reason = clean($_POST['reject_reason'] ?? 'Ditolak oleh admin');
        $result = rejectWithdraw($wdId, $adminId, $reason);
        if ($result['success']) {
            logActivity('reject_withdraw', 'withdraw', $wdId, "Tolak withdraw #{$wdId}. Alasan: {$reason}");
            setFlashPopup('success', 'Withdraw #' . $wdId . ' berhasil ditolak. Dana dikembalikan.');
        } else {
            setFlashPopup('error', $result['message'] ?? 'Gagal menolak withdraw.');
        }
    } elseif ($action === 'export_csv') {
        $rows = db()->fetchAll(
            "SELECT w.id, u.username, u.full_name, w.amount, w.fee, w.net_amount, b.bank_name, b.account_number, b.account_name, w.status, w.created_at, w.processed_at, w.admin_note
             FROM withdrawals w JOIN users u ON u.id=w.user_id JOIN bank_accounts b ON b.id=w.bank_account_id
             ORDER BY w.id DESC"
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="withdrawals_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Username','Nama','Jumlah','Fee','Net','Bank','No.Rekening','Atas Nama','Status','Tanggal','Diproses','Catatan']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'],$r['username'],$r['full_name'],$r['amount'],$r['fee'],$r['net_amount'],$r['bank_name'],$r['account_number'],$r['account_name'],$r['status'],$r['created_at'],$r['processed_at'],$r['admin_note']]);
        }
        fclose($out); exit;
    }

    header('Location: ' . BASE_URL . '/admin/withdrawals.php?tab=' . ($_POST['current_tab'] ?? 'pending'));
    exit;
}

// ── FILTERS ───────────────────────────────────────────────────
$tab      = $_GET['tab'] ?? 'pending';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = ADMIN_PER_PAGE;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($tab === 'pending') { $where[] = "w.status='pending'"; }
if ($dateFrom) { $where[] = 'w.created_at >= ?'; $params[] = $dateFrom.' 00:00:00'; $types .= 's'; }
if ($dateTo)   { $where[] = 'w.created_at <= ?'; $params[] = $dateTo.' 23:59:59';   $types .= 's'; }
$whereStr = implode(' AND ', $where);

$totalRow = db()->fetchOne("SELECT COUNT(*) as cnt FROM withdrawals w WHERE {$whereStr}", $types, $params);
$total    = (int)($totalRow['cnt'] ?? 0);
$pagInfo  = paginate($total, $page, $perPage);

$rows = db()->fetchAll(
    "SELECT w.*, u.username, u.full_name, b.bank_name, b.account_number, b.account_name
     FROM withdrawals w
     JOIN users u ON u.id=w.user_id
     JOIN bank_accounts b ON b.id=w.bank_account_id
     WHERE {$whereStr}
     ORDER BY w.created_at DESC LIMIT ? OFFSET ?",
    $types . 'ii', array_merge($params, [$perPage, $pagInfo['offset']])
);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="tabs" style="margin-bottom:20px">
  <a href="?tab=pending" class="tab <?= $tab==='pending'?'active':'' ?>">Pending</a>
  <a href="?tab=all" class="tab <?= $tab==='all'?'active':'' ?>">Semua</a>
</div>

<div class="admin-card" style="margin-bottom:20px">
  <form method="GET" class="filter-row">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <input type="date" name="date_from" class="form-input" value="<?= e($dateFrom) ?>">
    <input type="date" name="date_to" class="form-input" value="<?= e($dateTo) ?>">
    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    <a href="?tab=<?= e($tab) ?>" class="btn btn-sm btn-ghost">Reset</a>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h3>Daftar Withdraw (<?= number_format($total) ?> data)</h3>
    <form method="POST" style="display:inline">
      <?= CSRF::field() ?><input type="hidden" name="action" value="export_csv">
      <button class="btn btn-xs btn-secondary">Export CSV</button>
    </form>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>#</th><th>Member</th><th>Jumlah</th><th>Fee</th><th>Diterima</th><th>Bank</th><th>No. Rekening</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="10" class="text-center text-muted">Tidak ada data withdraw</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $w): ?>
        <tr>
          <td><?= (int)$w['id'] ?></td>
          <td><div class="fw-600"><?= e($w['username']) ?></div><div class="text-xs text-muted"><?= e($w['full_name']) ?></div></td>
          <td><?= formatRupiah((float)$w['amount']) ?></td>
          <td class="text-muted"><?= formatRupiah((float)$w['fee']) ?></td>
          <td class="fw-600 text-cyan"><?= formatRupiah((float)$w['net_amount']) ?></td>
          <td><?= e($w['bank_name']) ?></td>
          <td><?= e($w['account_number']) ?><br><span class="text-xs text-muted"><?= e($w['account_name']) ?></span></td>
          <td><?= formatDate($w['created_at'], 'd M Y H:i') ?></td>
          <td>
            <?php $statusMap=['pending'=>['badge-warning','Pending'],'approved'=>['badge-success','Disetujui'],'rejected'=>['badge-error','Ditolak'],'processing'=>['badge-info','Proses']]; $st=$statusMap[$w['status']]??['badge-secondary',$w['status']]; ?>
            <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
          </td>
          <td>
            <?php if ($w['status'] === 'pending'): ?>
            <div class="action-btns">
              <button class="btn btn-xs btn-success" onclick="openApproveModal(<?= (int)$w['id'] ?>, '<?= e($w['username']) ?>', '<?= e(formatRupiah((float)$w['net_amount'])) ?>')">Setujui</button>
              <button class="btn btn-xs btn-danger" onclick="openRejectModal(<?= (int)$w['id'] ?>, '<?= e($w['username']) ?>')">Tolak</button>
            </div>
            <?php elseif ($w['admin_note']): ?>
              <span class="text-xs text-muted"><?= e(mb_substr($w['admin_note'],0,30)) ?>...</span>
            <?php else: ?><span class="text-muted">-</span><?php endif; ?>
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

<!-- Approve Modal -->
<div class="modal-overlay hidden" id="approveModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3>Setujui Withdraw</h3><button class="btn-close" onclick="closeModal('approveModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="approve_withdraw">
      <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
      <input type="hidden" name="wd_id" id="approveWdId">
      <div class="modal-body">
        <p>Setujui withdraw dari <strong id="approveUser"></strong> sebesar <strong id="approveAmount"></strong>?</p>
        <div class="form-group" style="margin-top:14px">
          <label>Catatan (opsional)</label>
          <input type="text" name="admin_note" class="form-input" placeholder="Misal: No. referensi transfer...">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('approveModal')">Batal</button>
        <button type="submit" class="btn btn-success">Ya, Setujui</button>
      </div>
    </form>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay hidden" id="rejectModal">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header"><h3>Tolak Withdraw</h3><button class="btn-close" onclick="closeModal('rejectModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="reject_withdraw">
      <input type="hidden" name="current_tab" value="<?= e($tab) ?>">
      <input type="hidden" name="wd_id" id="rejectWdId">
      <div class="modal-body">
        <p>Tolak withdraw dari <strong id="rejectUser"></strong>? Dana akan dikembalikan ke saldo member.</p>
        <div class="form-group" style="margin-top:14px">
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

<script>
function openApproveModal(id, user, amount) {
  document.getElementById('approveWdId').value = id;
  document.getElementById('approveUser').textContent = user;
  document.getElementById('approveAmount').textContent = amount;
  document.getElementById('approveModal').classList.replace('hidden','visible');
}
function openRejectModal(id, user) {
  document.getElementById('rejectWdId').value = id;
  document.getElementById('rejectUser').textContent = user;
  document.getElementById('rejectModal').classList.replace('hidden','visible');
}
function closeModal(id) { document.getElementById(id).classList.replace('visible','hidden'); }
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
