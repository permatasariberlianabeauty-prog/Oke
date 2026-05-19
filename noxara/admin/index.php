<?php
/**
 * NOXARA Admin - Dashboard
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin();

$pageTitle = 'Dashboard';

// Stats
$totalMembers = db()->fetchOne('SELECT COUNT(*) as cnt FROM users WHERE is_active = 1')['cnt'] ?? 0;

$todayDeposit = db()->fetchOne(
    "SELECT COALESCE(SUM(amount),0) as total FROM deposits WHERE status='confirmed' AND DATE(confirmed_at)=CURDATE()"
)['total'] ?? 0;

$pendingWithdraw = db()->fetchOne(
    "SELECT COUNT(*) as cnt FROM withdrawals WHERE status='pending'"
)['cnt'] ?? 0;

$activePackages = db()->fetchOne(
    "SELECT COUNT(*) as cnt FROM user_products WHERE status='active'"
)['cnt'] ?? 0;

$pendingDeposits = db()->fetchAll(
    "SELECT d.*, u.username, u.full_name FROM deposits d
     JOIN users u ON u.id = d.user_id
     WHERE d.status='pending' ORDER BY d.created_at DESC LIMIT 10"
);

$pendingWithdrawals = db()->fetchAll(
    "SELECT w.*, u.username, u.full_name, b.bank_name, b.account_number
     FROM withdrawals w
     JOIN users u ON u.id = w.user_id
     JOIN bank_accounts b ON b.id = w.bank_account_id
     WHERE w.status='pending' ORDER BY w.created_at DESC LIMIT 10"
);

$recentMembers = db()->fetchAll(
    "SELECT u.*, uw.main_balance, uw.total_deposit
     FROM users u
     LEFT JOIN user_wallets uw ON uw.user_id = u.id
     ORDER BY u.created_at DESC LIMIT 10"
);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(0,212,255,.12);color:#00D4FF">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-label">Total Member</div>
      <div class="stat-value"><?= number_format((int)$totalMembers) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(34,197,94,.12);color:#22c55e">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-label">Deposit Hari Ini</div>
      <div class="stat-value"><?= formatRupiah((float)$todayDeposit, true) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(251,191,36,.12);color:#fbbf24">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-label">Withdraw Pending</div>
      <div class="stat-value"><?= number_format((int)$pendingWithdraw) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(123,47,255,.12);color:#7B2FFF">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8" stroke="currentColor" stroke-width="2"/></svg>
    </div>
    <div class="stat-info">
      <div class="stat-label">Paket Aktif</div>
      <div class="stat-value"><?= number_format((int)$activePackages) ?></div>
    </div>
  </div>
</div>

<div class="quick-actions" style="margin-bottom:24px">
  <a href="<?= BASE_URL ?>/admin/deposits.php" class="btn btn-sm btn-primary">Konfirmasi Deposit</a>
  <a href="<?= BASE_URL ?>/admin/withdrawals.php" class="btn btn-sm btn-secondary">Proses Withdraw</a>
  <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-sm btn-ghost">Data Member</a>
  <a href="<?= BASE_URL ?>/admin/chat.php" class="btn btn-sm btn-ghost">Live Chat</a>
</div>

<div class="admin-grid-2">
  <!-- Deposit Pending -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Deposit Menunggu Konfirmasi</h3>
      <a href="<?= BASE_URL ?>/admin/deposits.php" class="btn btn-xs btn-ghost">Lihat Semua</a>
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Member</th><th>Jumlah</th><th>Tgl</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($pendingDeposits)): ?>
          <tr><td colspan="5" class="text-center text-muted">Tidak ada deposit pending</td></tr>
        <?php else: ?>
          <?php foreach ($pendingDeposits as $d): ?>
          <tr>
            <td><?= e($d['id']) ?></td>
            <td>
              <div class="fw-600"><?= e($d['username']) ?></div>
              <div class="text-xs text-muted"><?= e($d['full_name']) ?></div>
            </td>
            <td><?= formatRupiah((float)$d['amount']) ?></td>
            <td><?= formatDate($d['created_at'], 'd M H:i') ?></td>
            <td>
              <a href="<?= BASE_URL ?>/admin/deposits.php?id=<?= (int)$d['id'] ?>" class="btn btn-xs btn-primary">Proses</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Withdraw Pending -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Withdraw Menunggu Persetujuan</h3>
      <a href="<?= BASE_URL ?>/admin/withdrawals.php" class="btn btn-xs btn-ghost">Lihat Semua</a>
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Member</th><th>Jumlah</th><th>Bank</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (empty($pendingWithdrawals)): ?>
          <tr><td colspan="5" class="text-center text-muted">Tidak ada withdraw pending</td></tr>
        <?php else: ?>
          <?php foreach ($pendingWithdrawals as $w): ?>
          <tr>
            <td><?= e($w['id']) ?></td>
            <td>
              <div class="fw-600"><?= e($w['username']) ?></div>
              <div class="text-xs text-muted"><?= e($w['full_name']) ?></div>
            </td>
            <td><?= formatRupiah((float)$w['net_amount']) ?></td>
            <td class="text-xs"><?= e($w['bank_name']) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/admin/withdrawals.php?id=<?= (int)$w['id'] ?>" class="btn btn-xs btn-primary">Proses</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Member Terbaru -->
<div class="admin-card" style="margin-top:24px">
  <div class="admin-card-header">
    <h3>Member Terbaru</h3>
    <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-xs btn-ghost">Lihat Semua</a>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Username</th><th>Nama</th><th>VIP</th><th>Total Deposit</th><th>Saldo</th><th>Bergabung</th></tr></thead>
      <tbody>
      <?php if (empty($recentMembers)): ?>
        <tr><td colspan="7" class="text-center text-muted">Belum ada member</td></tr>
      <?php else: ?>
        <?php foreach ($recentMembers as $m): ?>
        <tr>
          <td><?= e($m['id']) ?></td>
          <td><a href="<?= BASE_URL ?>/admin/members.php?id=<?= (int)$m['id'] ?>"><?= e($m['username']) ?></a></td>
          <td><?= e($m['full_name']) ?></td>
          <td><span class="badge badge-vip">VIP <?= (int)$m['vip_level'] ?></span></td>
          <td><?= formatRupiah((float)($m['total_deposit'] ?? 0)) ?></td>
          <td><?= formatRupiah((float)($m['main_balance'] ?? 0)) ?></td>
          <td><?= formatDate($m['created_at'], 'd M Y') ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
