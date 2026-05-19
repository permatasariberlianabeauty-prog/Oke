<?php
/**
 * NOXARA Admin - Laporan Keuangan
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_FINANCE);

$pageTitle = 'Laporan Keuangan';

$tab      = $_GET['tab'] ?? 'summary';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// ── EXPORT ────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    if (!CSRF::verify($_GET['csrf'] ?? '')) {
        die('Token tidak valid.');
    }
    if ($exportType === 'deposit') {
        $rows = db()->fetchAll(
            "SELECT d.id,u.username,u.full_name,d.amount,d.unique_code,d.total_amount,d.status,d.created_at,d.confirmed_at
             FROM deposits d JOIN users u ON u.id=d.user_id
             WHERE d.created_at BETWEEN ? AND ? ORDER BY d.id DESC",
            'ss', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="deposit_report_' . date('Ymd') . '.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['ID','Username','Nama','Jumlah','Kode Unik','Total Transfer','Status','Tanggal','Dikonfirmasi']);
        foreach ($rows as $r) fputcsv($f,[$r['id'],$r['username'],$r['full_name'],$r['amount'],$r['unique_code'],$r['total_amount'],$r['status'],$r['created_at'],$r['confirmed_at']]);
        fclose($f); exit;
    } elseif ($exportType === 'withdraw') {
        $rows = db()->fetchAll(
            "SELECT w.id,u.username,u.full_name,w.amount,w.fee,w.net_amount,w.status,w.created_at,w.processed_at
             FROM withdrawals w JOIN users u ON u.id=w.user_id
             WHERE w.created_at BETWEEN ? AND ? ORDER BY w.id DESC",
            'ss', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="withdraw_report_' . date('Ymd') . '.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['ID','Username','Nama','Jumlah','Fee','Net','Status','Tanggal','Diproses']);
        foreach ($rows as $r) fputcsv($f,[$r['id'],$r['username'],$r['full_name'],$r['amount'],$r['fee'],$r['net_amount'],$r['status'],$r['created_at'],$r['processed_at']]);
        fclose($f); exit;
    }
}

// ── SUMMARY DATA ──────────────────────────────────────────────
$dtFrom = $dateFrom . ' 00:00:00';
$dtTo   = $dateTo   . ' 23:59:59';

$totalIncome = (float)(db()->fetchOne(
    "SELECT COALESCE(SUM(amount),0) as t FROM deposits WHERE status='confirmed' AND confirmed_at BETWEEN ? AND ?",
    'ss', [$dtFrom, $dtTo]
)['t'] ?? 0);

$totalWithdraw = (float)(db()->fetchOne(
    "SELECT COALESCE(SUM(net_amount),0) as t FROM withdrawals WHERE status='approved' AND processed_at BETWEEN ? AND ?",
    'ss', [$dtFrom, $dtTo]
)['t'] ?? 0);

$totalProfit = (float)(db()->fetchOne(
    "SELECT COALESCE(SUM(amount),0) as t FROM transaction_ledger WHERE transaction_type='profit' AND created_at BETWEEN ? AND ?",
    'ss', [$dtFrom, $dtTo]
)['t'] ?? 0);

$netProfit = $totalIncome - $totalWithdraw - $totalProfit;

// Chart data (daily deposit for last 30 days)
$chartRows = db()->fetchAll(
    "SELECT DATE(confirmed_at) as date, SUM(amount) as total FROM deposits WHERE status='confirmed' AND confirmed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(confirmed_at) ORDER BY date ASC"
);
$chartData = [];
foreach ($chartRows as $cr) {
    $chartData[$cr['date']] = (float)$cr['total'];
}

// Paginated tables
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = ADMIN_PER_PAGE;

$deposits = [];
$withdrawals = [];
$profits = [];

if ($tab === 'deposit' || $tab === 'summary') {
    $totalDRows = (int)(db()->fetchOne("SELECT COUNT(*) as cnt FROM deposits d WHERE d.created_at BETWEEN ? AND ?", 'ss', [$dtFrom,$dtTo])['cnt'] ?? 0);
    $pagD = paginate($totalDRows, $page, $perPage);
    $deposits = db()->fetchAll(
        "SELECT d.*, u.username FROM deposits d JOIN users u ON u.id=d.user_id WHERE d.created_at BETWEEN ? AND ? ORDER BY d.id DESC LIMIT ? OFFSET ?",
        'ssii', [$dtFrom, $dtTo, $perPage, $pagD['offset']]
    );
}
if ($tab === 'withdraw') {
    $totalWRows = (int)(db()->fetchOne("SELECT COUNT(*) as cnt FROM withdrawals w WHERE w.created_at BETWEEN ? AND ?", 'ss', [$dtFrom,$dtTo])['cnt'] ?? 0);
    $pagW = paginate($totalWRows, $page, $perPage);
    $withdrawals = db()->fetchAll(
        "SELECT w.*, u.username FROM withdrawals w JOIN users u ON u.id=w.user_id WHERE w.created_at BETWEEN ? AND ? ORDER BY w.id DESC LIMIT ? OFFSET ?",
        'ssii', [$dtFrom, $dtTo, $perPage, $pagW['offset']]
    );
}
if ($tab === 'profit') {
    $profits = db()->fetchAll(
        "SELECT tl.*, u.username FROM transaction_ledger tl JOIN users u ON u.id=tl.user_id WHERE tl.transaction_type='profit' AND tl.created_at BETWEEN ? AND ? ORDER BY tl.id DESC LIMIT ?",
        'ssi', [$dtFrom, $dtTo, $perPage]
    );
}

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Date Filter -->
<div class="admin-card" style="margin-bottom:20px">
  <form method="GET" class="filter-row">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <label style="font-size:13px;color:#94a3b8">Dari:</label>
    <input type="date" name="date_from" class="form-input" value="<?= e($dateFrom) ?>">
    <label style="font-size:13px;color:#94a3b8">Sampai:</label>
    <input type="date" name="date_to" class="form-input" value="<?= e($dateTo) ?>">
    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    <a href="?tab=<?= e($tab) ?>&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-ghost">Bulan Ini</a>
    <a href="?tab=<?= e($tab) ?>&date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-ghost">Hari Ini</a>
  </form>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(34,197,94,.12);color:#22c55e"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div>
    <div class="stat-info"><div class="stat-label">Total Income (Deposit)</div><div class="stat-value"><?= formatRupiah($totalIncome) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(239,68,68,.12);color:#ef4444"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div>
    <div class="stat-info"><div class="stat-label">Total Withdraw</div><div class="stat-value"><?= formatRupiah($totalWithdraw) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(123,47,255,.12);color:#7B2FFF"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div>
    <div class="stat-info"><div class="stat-label">Total Profit Dibagikan</div><div class="stat-value"><?= formatRupiah($totalProfit) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(0,212,255,.12);color:#00D4FF"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div>
    <div class="stat-info"><div class="stat-label">Net Profit Platform</div><div class="stat-value" style="color:<?= $netProfit >= 0 ? '#22c55e' : '#ef4444' ?>"><?= formatRupiah($netProfit) ?></div></div>
  </div>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom:20px">
  <a href="?tab=summary&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="tab <?= $tab==='summary'?'active':'' ?>">Ringkasan</a>
  <a href="?tab=deposit&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="tab <?= $tab==='deposit'?'active':'' ?>">Deposit</a>
  <a href="?tab=withdraw&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="tab <?= $tab==='withdraw'?'active':'' ?>">Withdraw</a>
  <a href="?tab=profit&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>" class="tab <?= $tab==='profit'?'active':'' ?>">Profit</a>
</div>

<?php if ($tab === 'summary'): ?>
<!-- Chart -->
<div class="admin-card" style="margin-bottom:24px">
  <div class="admin-card-header"><h3>Grafik Deposit 30 Hari Terakhir</h3></div>
  <canvas id="depositChart" style="max-height:220px"></canvas>
</div>

<!-- Recent Deposits -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Deposit Terbaru (periode ini)</h3>
    <a href="?tab=deposit&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&export=deposit&csrf=<?= CSRF::token() ?>" class="btn btn-xs btn-secondary">Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Username</th><th>Jumlah</th><th>Status</th><th>Tanggal</th></tr></thead>
      <tbody>
      <?php foreach (array_slice($deposits,0,10) as $d): ?>
      <tr><td><?= (int)$d['id'] ?></td><td><?= e($d['username']) ?></td><td><?= formatRupiah((float)$d['amount']) ?></td><td><span class="badge badge-<?= $d['status']==='confirmed'?'success':($d['status']==='pending'?'warning':'error') ?>"><?= e($d['status']) ?></span></td><td><?= formatDate($d['created_at'],'d M Y H:i') ?></td></tr>
      <?php endforeach; ?>
      <?php if (empty($deposits)): ?><tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'deposit'): ?>
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Laporan Deposit</h3>
    <a href="?tab=deposit&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&export=deposit&csrf=<?= CSRF::token() ?>" class="btn btn-xs btn-secondary">Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Username</th><th>Jumlah</th><th>Total Transfer</th><th>Status</th><th>Tanggal</th><th>Dikonfirmasi</th></tr></thead>
      <tbody>
      <?php if (empty($deposits)): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
      <?php else: ?>
        <?php foreach ($deposits as $d): ?>
        <tr><td><?= (int)$d['id'] ?></td><td><?= e($d['username']) ?></td><td><?= formatRupiah((float)$d['amount']) ?></td><td><?= formatRupiah((float)$d['total_amount']) ?></td><td><span class="badge badge-<?= $d['status']==='confirmed'?'success':($d['status']==='pending'?'warning':'error') ?>"><?= e($d['status']) ?></span></td><td><?= formatDate($d['created_at'],'d M Y H:i') ?></td><td><?= $d['confirmed_at'] ? formatDate($d['confirmed_at'],'d M Y H:i') : '-' ?></td></tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'withdraw'): ?>
<div class="admin-card">
  <div class="admin-card-header">
    <h3>Laporan Withdraw</h3>
    <a href="?tab=withdraw&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>&export=withdraw&csrf=<?= CSRF::token() ?>" class="btn btn-xs btn-secondary">Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Username</th><th>Jumlah</th><th>Fee</th><th>Net</th><th>Status</th><th>Tanggal</th></tr></thead>
      <tbody>
      <?php if (empty($withdrawals)): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
      <?php else: ?>
        <?php foreach ($withdrawals as $w): ?>
        <tr><td><?= (int)$w['id'] ?></td><td><?= e($w['username']) ?></td><td><?= formatRupiah((float)$w['amount']) ?></td><td><?= formatRupiah((float)$w['fee']) ?></td><td><?= formatRupiah((float)$w['net_amount']) ?></td><td><span class="badge badge-<?= $w['status']==='approved'?'success':($w['status']==='pending'?'warning':'error') ?>"><?= e($w['status']) ?></span></td><td><?= formatDate($w['created_at'],'d M Y H:i') ?></td></tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'profit'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Laporan Profit Dibagikan</h3></div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Username</th><th>Jumlah Profit</th><th>Saldo Setelah</th><th>Tanggal</th></tr></thead>
      <tbody>
      <?php if (empty($profits)): ?><tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
      <?php else: ?>
        <?php foreach ($profits as $p): ?>
        <tr><td><?= (int)$p['id'] ?></td><td><?= e($p['username']) ?></td><td><?= formatRupiah((float)$p['amount']) ?></td><td><?= formatRupiah((float)$p['balance_after']) ?></td><td><?= formatDate($p['created_at'],'d M Y H:i') ?></td></tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
// Simple bar chart using canvas
(function(){
  const canvas = document.getElementById('depositChart');
  if (!canvas) return;
  const chartData = <?= json_encode($chartData) ?>;
  const labels = Object.keys(chartData);
  const values = Object.values(chartData);
  if (!labels.length) return;

  const ctx = canvas.getContext('2d');
  const W = canvas.offsetWidth || 800;
  const H = 200;
  canvas.width = W;
  canvas.height = H;

  const maxVal = Math.max(...values, 1);
  const barW = Math.floor((W - 40) / labels.length) - 2;

  ctx.fillStyle = '#0A0E1A';
  ctx.fillRect(0, 0, W, H);

  values.forEach((val, i) => {
    const x = 20 + i * (barW + 2);
    const barH = Math.floor((val / maxVal) * (H - 40));
    const y = H - barH - 20;
    ctx.fillStyle = '#00D4FF';
    ctx.globalAlpha = 0.7;
    ctx.fillRect(x, y, barW, barH);
    ctx.globalAlpha = 1;
  });
})();
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
