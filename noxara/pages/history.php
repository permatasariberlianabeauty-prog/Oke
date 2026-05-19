<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId  = SessionManager::userId();
$perPage = PER_PAGE; // 20
$page    = max(1, (int)getVal('page', 1));
$type    = clean(getVal('type', ''));

$validTypes = ['deposit','withdraw','profit','referral_commission','bonus_daily_reward','bonus_mission','bonus_ad','bonus_register','purchase','voucher_discount','admin_adjustment'];
if (!in_array($type, $validTypes, true)) $type = '';

$where = 'WHERE tl.user_id = ?';
$params = [$userId];
$types  = 'i';

if ($type !== '') {
    $where .= ' AND tl.transaction_type = ?';
    $params[] = $type;
    $types .= 's';
}

$countRow = db()->fetchOne("SELECT COUNT(*) as cnt FROM transaction_ledger tl {$where}", $types, $params);
$total = (int)($countRow['cnt'] ?? 0);
$paginator = paginate($total, $page, $perPage);

$params2 = $params;
$params2[] = $perPage;
$params2[] = $paginator['offset'];
$types2  = $types . 'ii';

$transactions = db()->fetchAll(
    "SELECT tl.* FROM transaction_ledger tl {$where} ORDER BY tl.created_at DESC LIMIT ? OFFSET ?",
    $types2, $params2
);

// Type display labels
$typeLabels = [
    'deposit'              => 'Deposit',
    'withdraw'             => 'Withdraw',
    'profit'               => 'Profit Harian',
    'referral_commission'  => 'Komisi Referral',
    'bonus_daily_reward'   => 'Hadiah Harian',
    'bonus_mission'        => 'Reward Misi',
    'bonus_ad'             => 'Reward Iklan',
    'bonus_register'       => 'Bonus Daftar',
    'purchase'             => 'Pembelian Paket',
    'voucher_discount'     => 'Diskon Voucher',
    'admin_adjustment'     => 'Penyesuaian Admin',
    'modal_return'         => 'Pengembalian Modal',
    'withdraw_fee'         => 'Biaya Withdraw',
];

$pageTitle = 'Riwayat Transaksi';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Riwayat Transaksi</h1>
  </div>

  <!-- Filter -->
  <div class="filter-scroll">
    <a href="?type=" class="filter-chip <?= $type===''?'active':'' ?>">Semua</a>
    <a href="?type=deposit" class="filter-chip <?= $type==='deposit'?'active':'' ?>">Deposit</a>
    <a href="?type=withdraw" class="filter-chip <?= $type==='withdraw'?'active':'' ?>">Withdraw</a>
    <a href="?type=profit" class="filter-chip <?= $type==='profit'?'active':'' ?>">Profit</a>
    <a href="?type=referral_commission" class="filter-chip <?= $type==='referral_commission'?'active':'' ?>">Referral</a>
    <a href="?type=bonus_daily_reward" class="filter-chip <?= $type==='bonus_daily_reward'?'active':'' ?>">Hadiah</a>
    <a href="?type=purchase" class="filter-chip <?= $type==='purchase'?'active':'' ?>">Pembelian</a>
  </div>

  <!-- Summary -->
  <div class="history-summary">
    <span class="text-muted">Menampilkan <?= count($transactions) ?> dari <?= $total ?> transaksi</span>
  </div>

  <!-- Transaction List -->
  <?php if (empty($transactions)): ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" stroke="#7B2FFF" stroke-width="1.5"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round"/></svg>
    <p>Belum ada transaksi.</p>
  </div>
  <?php else: ?>
  <div class="tx-list">
    <?php foreach ($transactions as $tx):
      $isCredit = $tx['direction'] === 'credit';
      $label    = $typeLabels[$tx['transaction_type']] ?? ucfirst(str_replace('_',' ',$tx['transaction_type']));
    ?>
    <div class="tx-item card">
      <div class="tx-icon <?= $isCredit ? 'tx-credit-icon' : 'tx-debit-icon' ?>">
        <?php if ($isCredit): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php else: ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="#FF4757" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php endif; ?>
      </div>
      <div class="tx-info">
        <span class="tx-label"><?= e($label) ?></span>
        <span class="tx-type-tag"><?= e(strtoupper($tx['wallet_type'])) ?></span>
        <?php if (!empty($tx['description'])): ?>
        <span class="tx-desc"><?= e($tx['description']) ?></span>
        <?php endif; ?>
        <span class="tx-date"><?= e(formatDate($tx['created_at'], 'd M Y H:i')) ?></span>
      </div>
      <div class="tx-amount <?= $isCredit ? 'tx-credit' : 'tx-debit' ?>">
        <?= $isCredit ? '+' : '–' ?><?= e(formatRupiah((float)$tx['amount'])) ?>
        <span class="tx-balance-after">Saldo: <?= e(formatRupiah((float)$tx['balance_after'])) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($paginator['total_pages'] > 1): ?>
  <div class="pagination">
    <?php if ($paginator['has_prev']): ?>
    <a href="?type=<?= e($type) ?>&page=<?= $paginator['current']-1 ?>" class="page-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endif; ?>
    <?php
    $start = max(1, $paginator['current']-2);
    $end   = min($paginator['total_pages'], $paginator['current']+2);
    for ($p = $start; $p <= $end; $p++): ?>
    <a href="?type=<?= e($type) ?>&page=<?= $p ?>"
      class="page-btn <?= $p === $paginator['current'] ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($paginator['has_next']): ?>
    <a href="?type=<?= e($type) ?>&page=<?= $paginator['current']+1 ?>" class="page-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
