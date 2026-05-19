<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mining.php';

requireLogin();

$userId = SessionManager::userId();
$today  = date('Y-m-d');

if (isPost() && isset($_POST['claim_package'])) {
    CSRF::verify();
    $pkgId = (int)postVal('package_id', 0);
    if ($pkgId > 0) {
        $res = claimDailyProfit($userId, $pkgId);
        if ($res['success']) {
            setFlashPopup('success', 'Profit ' . formatRupiah($res['profit']) . ' berhasil diklaim!', 'Profit Diklaim 🎉');
        } else {
            setFlashPopup('error', $res['message'], 'Gagal');
        }
    }
    redirect(BASE_URL . '/pages/my_packages.php');
}

$activeTab = clean(getVal('tab', 'active'));

$activePackages = db()->fetchAll(
    'SELECT up.*, p.name as product_name, p.image, pc.name as category_name
     FROM user_products up JOIN products p ON p.id = up.product_id
     JOIN product_categories pc ON pc.id = p.category_id
     WHERE up.user_id = ? AND up.status = "active" ORDER BY up.created_at DESC',
    'i', [$userId]
);

$completedPackages = db()->fetchAll(
    'SELECT up.*, p.name as product_name FROM user_products up JOIN products p ON p.id = up.product_id
     WHERE up.user_id = ? AND up.status IN ("completed","expired") ORDER BY up.updated_at DESC LIMIT 30',
    'i', [$userId]
);

// Profit history (mining_logs)
$profitLogs = db()->fetchAll(
    'SELECT ml.*, p.name as product_name FROM mining_logs ml
     JOIN user_products up ON up.id = ml.user_product_id
     JOIN products p ON p.id = up.product_id
     WHERE ml.user_id = ? ORDER BY ml.claim_date DESC LIMIT 30',
    'i', [$userId]
);

$nextMidnight  = strtotime('tomorrow midnight');
$remainingSecs = max(0, $nextMidnight - time());

$pageTitle = 'Paket Saya';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Paket Saya</h1>
  </div>

  <div class="tabs" id="pkgTabs">
    <button class="tab-btn <?= $activeTab==='active'?'active':'' ?>" data-tab="pkg-active">Aktif (<?= count($activePackages) ?>)</button>
    <button class="tab-btn <?= $activeTab==='history'?'active':'' ?>" data-tab="pkg-history">Selesai (<?= count($completedPackages) ?>)</button>
    <button class="tab-btn <?= $activeTab==='profit'?'active':'' ?>" data-tab="pkg-profit">Log Profit</button>
  </div>

  <!-- Aktif -->
  <div class="tab-content <?= $activeTab==='active'?'active':'' ?>" id="pkg-active">
    <?php if (empty($activePackages)): ?>
    <div class="empty-state">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="4" stroke="#7B2FFF" stroke-width="1.5"/><path d="M9 12h6M12 9v6" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round"/></svg>
      <p>Belum ada paket aktif.</p>
      <a href="<?= BASE_URL ?>/pages/products.php" class="btn btn-primary">Beli Paket</a>
    </div>
    <?php else: ?>
    <div class="packages-list">
      <?php foreach ($activePackages as $pkg):
        $progress = (int)$pkg['duration_days'] > 0
          ? min(100, round(((int)$pkg['days_claimed'] / (int)$pkg['duration_days']) * 100)) : 0;
        $alreadyClaimed = ($pkg['last_claim_date'] === $today);
      ?>
      <div class="package-card card">
        <div class="package-header">
          <div class="package-info">
            <h3 class="package-name"><?= e($pkg['product_name']) ?></h3>
            <span class="package-category"><?= e($pkg['category_name']) ?></span>
          </div>
          <div class="package-profit">
            <span class="profit-day">+<?= e(formatRupiah((float)$pkg['profit_per_day'])) ?></span>
            <span class="profit-day-label">/hari</span>
          </div>
        </div>

        <div class="package-meta-row">
          <div class="pkg-meta-item">
            <span class="label">Modal</span>
            <span><?= e(formatRupiah((float)$pkg['price_paid'])) ?></span>
          </div>
          <div class="pkg-meta-item">
            <span class="label">Profit Terkumpul</span>
            <span class="cyan"><?= e(formatRupiah((float)$pkg['total_profit_earned'])) ?></span>
          </div>
          <div class="pkg-meta-item">
            <span class="label">Terakhir Klaim</span>
            <span><?= $pkg['last_claim_date'] ? e(formatDate($pkg['last_claim_date'])) : 'Belum pernah' ?></span>
          </div>
          <div class="pkg-meta-item">
            <span class="label">Berakhir</span>
            <span><?= e(formatDate($pkg['expired_at'])) ?></span>
          </div>
        </div>

        <div class="package-progress">
          <div class="progress-bar">
            <div class="progress-fill cyan" style="width:<?= $progress ?>%"></div>
          </div>
          <div class="progress-meta">
            <span><?= (int)$pkg['days_claimed'] ?>/<?= (int)$pkg['duration_days'] ?> hari (<?= $progress ?>%)</span>
          </div>
        </div>

        <div class="package-claim-row">
          <?php if (!$alreadyClaimed): ?>
          <form method="post" action="" class="inline-form">
            <?= CSRF::field() ?>
            <input type="hidden" name="package_id" value="<?= (int)$pkg['id'] ?>">
            <button type="submit" name="claim_package" value="1"
              class="btn btn-primary btn-full" style="min-height:48px">
              Klaim Profit Harian
            </button>
          </form>
          <?php else: ?>
          <div class="claimed-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Sudah diklaim hari ini. Berikutnya:
            <span class="countdown-timer-sm orbitron" data-seconds="<?= $remainingSecs ?>">--:--:--</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Selesai/Expired -->
  <div class="tab-content <?= $activeTab==='history'?'active':'' ?>" id="pkg-history">
    <?php if (empty($completedPackages)): ?>
    <p class="text-muted text-center" style="padding:32px">Belum ada paket selesai.</p>
    <?php else: ?>
    <div class="packages-list">
      <?php foreach ($completedPackages as $pkg): ?>
      <div class="package-card card package-done">
        <div class="package-header">
          <div class="package-info">
            <h3 class="package-name"><?= e($pkg['product_name']) ?></h3>
            <span class="status-badge status-<?= e($pkg['status']) ?>"><?= e(ucfirst($pkg['status'])) ?></span>
          </div>
          <div class="package-profit">
            <span class="profit-day cyan"><?= e(formatRupiah((float)$pkg['total_profit_earned'])) ?></span>
            <span class="profit-day-label">total profit</span>
          </div>
        </div>
        <div class="package-meta-row">
          <div class="pkg-meta-item"><span class="label">Modal</span><span><?= e(formatRupiah((float)$pkg['price_paid'])) ?></span></div>
          <div class="pkg-meta-item"><span class="label">Durasi</span><span><?= (int)$pkg['duration_days'] ?> hari</span></div>
          <div class="pkg-meta-item"><span class="label">Berakhir</span><span><?= e(formatDate($pkg['expired_at'])) ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Log Profit -->
  <div class="tab-content <?= $activeTab==='profit'?'active':'' ?>" id="pkg-profit">
    <?php if (empty($profitLogs)): ?>
    <p class="text-muted text-center" style="padding:32px">Belum ada log profit.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Tanggal</th><th>Paket</th><th>Profit</th></tr>
        </thead>
        <tbody>
          <?php foreach ($profitLogs as $log): ?>
          <tr>
            <td><?= e(formatDate($log['claim_date'])) ?></td>
            <td><?= e($log['product_name']) ?></td>
            <td class="cyan">+<?= e(formatRupiah((float)$log['amount'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
document.querySelectorAll('.tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.tab).classList.add('active');
  });
});

function startCountdown(el, seconds) {
  function update() {
    if (seconds <= 0) { el.textContent = '00:00:00'; return; }
    var h=Math.floor(seconds/3600), m=Math.floor((seconds%3600)/60), s=seconds%60;
    el.textContent=String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
    seconds--; setTimeout(update,1000);
  }
  update();
}
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.countdown-timer-sm').forEach(function(el){
    startCountdown(el, parseInt(el.dataset.seconds||0));
  });
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
