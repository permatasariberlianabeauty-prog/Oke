<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/wallet.php';
require_once INCLUDES_PATH . '/mining.php';
require_once INCLUDES_PATH . '/vip.php';
require_once INCLUDES_PATH . '/notification.php';
require_once INCLUDES_PATH . '/welcome_popup.php';

requireLogin();

$userId   = SessionManager::userId();
$userName = SessionManager::get('user_name', 'Member');
$wallet   = getUserWallet($userId);
$today    = date('Y-m-d');

// Klaim semua profit harian (bulk) via POST
$claimMsg = '';
if (isPost() && isset($_POST['claim_all'])) {
    CSRF::verify();
    $claimable = getClaimablePackages($userId);
    $totalClaimed = 0.0;
    foreach ($claimable as $pkg) {
        $res = claimDailyProfit($userId, (int)$pkg['id']);
        if ($res['success']) $totalClaimed += $res['profit'];
    }
    if ($totalClaimed > 0) {
        setFlashPopup('success', 'Profit harian ' . formatRupiah($totalClaimed) . ' berhasil diklaim!', 'Klaim Berhasil');
    } else {
        setFlashPopup('info', 'Tidak ada profit yang bisa diklaim saat ini.', 'Info');
    }
    redirect(BASE_URL . '/pages/dashboard.php');
}

// Klaim per paket
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
    redirect(BASE_URL . '/pages/dashboard.php');
}

// Reload wallet setelah klaim
$wallet         = getUserWallet($userId);
$todayProfit    = getTodayProfit($userId);
$totalProfit    = (float)$wallet['total_profit'];
$activePackages = getUserActivePackages($userId);
$activeCount    = count($activePackages);
$claimableList  = getClaimablePackages($userId);

// Banner slider
$banners = db()->fetchAll('SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 10');

// Hitung next claim countdown
$nextMidnight = strtotime('tomorrow midnight');
$remainingSecs = max(0, $nextMidnight - time());

$pageTitle = 'Dashboard';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container dashboard-page">

  <!-- Banner Slider -->
  <?php if (!empty($banners)): ?>
  <div class="banner-slider" id="bannerSlider">
    <div class="banner-track" id="bannerTrack">
      <?php foreach ($banners as $i => $banner): ?>
      <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
        <?php if (!empty($banner['link_url'])): ?>
        <a href="<?= e($banner['link_url']) ?>" target="_blank" rel="noopener">
        <?php endif; ?>
          <img src="<?= uploadUrl($banner['image_path']) ?>" alt="<?= e($banner['title']) ?>" class="banner-img" loading="lazy">
        <?php if (!empty($banner['link_url'])): ?></a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($banners) > 1): ?>
    <div class="banner-dots" id="bannerDots">
      <?php foreach ($banners as $i => $b): ?>
      <span class="banner-dot <?= $i===0?'active':'' ?>" data-slide="<?= $i ?>"></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Greeting -->
  <div class="dashboard-greeting">
    <div>
      <p class="greeting-sub">Selamat Datang,</p>
      <h1 class="greeting-name orbitron"><?= e($userName) ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/pages/vip.php" class="vip-badge vip-<?= (int)SessionManager::get('user_vip',0) ?>">
      VIP <?= (int)SessionManager::get('user_vip',0) ?>
    </a>
  </div>

  <!-- Wallet Cards -->
  <div class="wallet-cards">
    <div class="wallet-card main-wallet">
      <div class="wallet-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="15" rx="3" stroke="#00D4FF" stroke-width="2"/><path d="M2 10h20" stroke="#00D4FF" stroke-width="2"/><circle cx="17" cy="15" r="1.5" fill="#00D4FF"/></svg>
      </div>
      <div class="wallet-info">
        <span class="wallet-label">Saldo Utama</span>
        <span class="wallet-amount" id="mainBalance"><?= e(formatRupiah((float)$wallet['main_balance'])) ?></span>
      </div>
      <a href="<?= BASE_URL ?>/pages/withdraw.php" class="btn btn-sm btn-outline-cyan">Tarik</a>
    </div>
    <div class="wallet-card free-wallet">
      <div class="wallet-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#7B2FFF" stroke-width="2"/><path d="M12 8v4l3 3" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div class="wallet-info">
        <span class="wallet-label">Saldo Gratis</span>
        <span class="wallet-amount free-balance-amount"><?= e(formatRupiah((float)$wallet['free_balance'])) ?></span>
      </div>
      <a href="<?= BASE_URL ?>/pages/products.php" class="btn btn-sm btn-outline-purple">Pakai</a>
    </div>
  </div>

  <!-- Saldo Gratis Notice -->
  <div class="notice-banner notice-info">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#00D4FF" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
    <span>Saldo gratis <strong>hanya untuk membeli paket</strong> dan <strong>tidak bisa ditarik</strong>.</span>
  </div>

  <!-- Stats Row -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-label">Profit Hari Ini</span>
      <span class="stat-value cyan"><?= e(formatRupiah($todayProfit)) ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Total Profit</span>
      <span class="stat-value purple"><?= e(formatRupiah($totalProfit)) ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Paket Aktif</span>
      <span class="stat-value"><?= e($activeCount) ?></span>
    </div>
  </div>

  <!-- Claim All Profit Button -->
  <div class="claim-section">
    <?php $canClaim = !empty($claimableList); ?>
    <form method="post" action="" id="claimAllForm" class="claim-form">
      <?= CSRF::field() ?>
      <button type="submit" name="claim_all" value="1"
        class="btn btn-primary btn-full btn-lg btn-glow <?= !$canClaim ? 'btn-disabled' : '' ?>"
        <?= !$canClaim ? 'disabled' : '' ?> id="claimAllBtn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#00D4FF"/></svg>
        <?= $canClaim ? 'Klaim Profit Harian' : 'Sudah Diklaim' ?>
      </button>
    </form>
    <?php if (!$canClaim): ?>
    <div class="countdown-wrap">
      <span class="countdown-label">Klaim berikutnya dalam:</span>
      <span class="countdown-timer orbitron" id="globalCountdown" data-seconds="<?= $remainingSecs ?>">--:--:--</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Active Packages -->
  <?php if (!empty($activePackages)): ?>
  <div class="section">
    <div class="section-header">
      <h2 class="section-title">Paket Aktif</h2>
      <a href="<?= BASE_URL ?>/pages/my_packages.php" class="section-link">Lihat Semua</a>
    </div>
    <div class="packages-list">
      <?php foreach ($activePackages as $pkg):
        $progress = (int)$pkg['duration_days'] > 0
          ? min(100, round(((int)$pkg['days_claimed'] / (int)$pkg['duration_days']) * 100))
          : 0;
        $alreadyClaimed = ($pkg['last_claim_date'] === $today);
        $pkgRemaining   = $alreadyClaimed ? $remainingSecs : 0;
      ?>
      <div class="package-card card">
        <div class="package-header">
          <div class="package-info">
            <h3 class="package-name"><?= e($pkg['product_name']) ?></h3>
            <span class="package-category"><?= e($pkg['category_name']) ?></span>
          </div>
          <span class="package-profit-day">
            +<?= e(formatRupiah((float)$pkg['profit_per_day'])) ?>/hari
          </span>
        </div>
        <div class="package-progress">
          <div class="progress-bar">
            <div class="progress-fill cyan" style="width:<?= $progress ?>%"></div>
          </div>
          <div class="progress-meta">
            <span><?= e($pkg['days_claimed']) ?>/<?= e($pkg['duration_days']) ?> hari</span>
            <span><?= $progress ?>%</span>
          </div>
        </div>
        <div class="package-footer">
          <span class="package-expire">Berakhir: <?= e(formatDate($pkg['expired_at'])) ?></span>
          <form method="post" action="" class="inline-form package-claim-form">
            <?= CSRF::field() ?>
            <input type="hidden" name="package_id" value="<?= (int)$pkg['id'] ?>">
            <?php if (!$alreadyClaimed): ?>
            <button type="submit" name="claim_package" value="1"
              class="btn btn-sm btn-primary btn-claim-pkg" data-pkg-id="<?= (int)$pkg['id'] ?>">
              Klaim
            </button>
            <?php else: ?>
            <span class="countdown-timer-sm orbitron" data-seconds="<?= $pkgRemaining ?>">--:--:--</span>
            <?php endif; ?>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="4" stroke="#7B2FFF" stroke-width="1.5"/><path d="M9 12h6M12 9v6" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round"/></svg>
    <p>Belum ada paket aktif.</p>
    <a href="<?= BASE_URL ?>/pages/products.php" class="btn btn-primary">Beli Paket Sekarang</a>
  </div>
  <?php endif; ?>

  <!-- Quick Menu -->
  <div class="quick-menu">
    <a href="<?= BASE_URL ?>/pages/deposit.php" class="quick-item">
      <div class="quick-icon bg-cyan"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="#0A0E1A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <span>Deposit</span>
    </a>
    <a href="<?= BASE_URL ?>/pages/withdraw.php" class="quick-item">
      <div class="quick-icon bg-purple"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
      <span>Withdraw</span>
    </a>
    <a href="<?= BASE_URL ?>/pages/products.php" class="quick-item">
      <div class="quick-icon bg-gold"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="8" width="18" height="13" rx="2" stroke="#0A0E1A" stroke-width="2"/><path d="M16 8V6a4 4 0 0 0-8 0v2" stroke="#0A0E1A" stroke-width="2"/></svg></div>
      <span>Paket</span>
    </a>
    <a href="<?= BASE_URL ?>/pages/referral.php" class="quick-item">
      <div class="quick-icon bg-green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="7" r="3" stroke="#0A0E1A" stroke-width="2"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="#0A0E1A" stroke-width="2"/><path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85" stroke="#0A0E1A" stroke-width="2" stroke-linecap="round"/></svg></div>
      <span>Referral</span>
    </a>
    <a href="<?= BASE_URL ?>/pages/history.php" class="quick-item">
      <div class="quick-icon bg-teal"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" stroke="#0A0E1A" stroke-width="2"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="#0A0E1A" stroke-width="2" stroke-linecap="round"/></svg></div>
      <span>Riwayat</span>
    </a>
  </div>

</div><!-- /.page-container -->

<!-- Coin Burst Overlay (JS animasi klaim) -->
<div id="coinBurst" class="coin-burst hidden" aria-hidden="true"></div>

<script>
// Countdown timer universal
function startCountdown(el, seconds) {
  function update() {
    if (seconds <= 0) { el.textContent = '00:00:00'; return; }
    var h = Math.floor(seconds / 3600);
    var m = Math.floor((seconds % 3600) / 60);
    var s = seconds % 60;
    el.textContent = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    seconds--;
    setTimeout(update, 1000);
  }
  update();
}

document.addEventListener('DOMContentLoaded', function(){
  // Global countdown
  var gc = document.getElementById('globalCountdown');
  if (gc) startCountdown(gc, parseInt(gc.dataset.seconds||0));

  // Per-package countdown
  document.querySelectorAll('.countdown-timer-sm').forEach(function(el){
    startCountdown(el, parseInt(el.dataset.seconds||0));
  });

  // Banner auto-slide
  var track = document.getElementById('bannerTrack');
  var dots   = document.querySelectorAll('.banner-dot');
  var slides = document.querySelectorAll('.banner-slide');
  if (slides.length > 1) {
    var cur = 0;
    setInterval(function(){
      slides[cur].classList.remove('active');
      if(dots[cur]) dots[cur].classList.remove('active');
      cur = (cur + 1) % slides.length;
      slides[cur].classList.add('active');
      if(dots[cur]) dots[cur].classList.add('active');
    }, 4000);
    dots.forEach(function(dot){
      dot.addEventListener('click', function(){
        slides[cur].classList.remove('active');
        if(dots[cur]) dots[cur].classList.remove('active');
        cur = parseInt(dot.dataset.slide);
        slides[cur].classList.add('active');
        dot.classList.add('active');
      });
    });
  }

  // Coin burst animation on claim
  document.querySelectorAll('.package-claim-form, #claimAllForm').forEach(function(form){
    form.addEventListener('submit', function(){
      triggerCoinBurst();
    });
  });
});

function triggerCoinBurst() {
  var burst = document.getElementById('coinBurst');
  if (!burst) return;
  burst.innerHTML = '';
  burst.classList.remove('hidden');
  for (var i = 0; i < 20; i++) {
    var coin = document.createElement('div');
    coin.className = 'coin-particle';
    coin.style.cssText = '--x:' + (Math.random()*100) + 'vw;--delay:' + (Math.random()*0.8) + 's;--size:' + (16+Math.random()*16) + 'px';
    coin.textContent = '💰';
    burst.appendChild(coin);
  }
  setTimeout(function(){ burst.classList.add('hidden'); burst.innerHTML=''; }, 2000);
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
