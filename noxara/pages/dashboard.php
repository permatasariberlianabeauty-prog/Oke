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
<div class="dashboard-bg-overlay"></div>

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
    <a href="<?= BASE_URL ?>/pages/vip.php" class="vip-badge vip-<?= (int)SessionManager::get('user_vip',0) ?>" style="box-shadow:0 0 14px rgba(0,212,255,0.25);">
      VIP <?= (int)SessionManager::get('user_vip',0) ?>
    </a>
  </div>

  <!-- Wallet Cards -->
  <div class="wallet-cards">
    <div class="wallet-card main-wallet" style="background: linear-gradient(135deg, rgba(0,212,255,0.1) 0%, var(--bg-card) 70%); border-left: 3px solid #00D4FF; box-shadow: 0 4px 24px rgba(0,0,0,0.4), -3px 0 20px rgba(0,212,255,0.15);">
      <div class="wallet-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="15" rx="3" stroke="#00D4FF" stroke-width="2"/><path d="M2 10h20" stroke="#00D4FF" stroke-width="2"/><circle cx="17" cy="15" r="1.5" fill="#00D4FF"/></svg>
      </div>
      <div class="wallet-info">
        <span class="wallet-label">Saldo Utama</span>
        <span class="wallet-amount" id="mainBalance"><?= e(formatRupiah((float)$wallet['main_balance'])) ?></span>
      </div>
      <a href="<?= BASE_URL ?>/pages/withdraw.php" class="btn btn-sm btn-outline-cyan">Tarik</a>
    </div>
    <div class="wallet-card free-wallet" style="background: linear-gradient(135deg, rgba(123,47,255,0.1) 0%, var(--bg-card) 70%); border-left: 3px solid #7B2FFF; box-shadow: 0 4px 24px rgba(0,0,0,0.4), -3px 0 20px rgba(123,47,255,0.15);">
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
  <div class="notice-banner notice-info" style="margin: 0 16px 12px; border-radius: 12px; border-left: 3px solid #FF9500; background: rgba(255,149,0,0.08); color: rgba(255,149,0,0.9);">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#FF9500" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#FF9500" stroke-width="2" stroke-linecap="round"/></svg>
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
        class="btn btn-primary btn-full btn-lg btn-claim-profit btn-glow <?= !$canClaim ? 'btn-disabled' : '' ?>"
        <?= !$canClaim ? 'disabled' : '' ?> id="claimAllBtn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#00D4FF"/></svg>
        <?= $canClaim ? 'Klaim Profit Harian' : 'Sudah Diklaim' ?>
      </button>
    </form>
    <?php if (!$canClaim): ?>
    <div class="countdown-wrap" style="background: rgba(255,149,0,0.06); border: 1px solid rgba(255,149,0,0.2); border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
      <span class="countdown-label" style="font-size:12px; color: var(--text-muted);">⏳ Klaim berikutnya dalam:</span>
      <span class="countdown-timer orbitron" id="globalCountdown" data-seconds="<?= $remainingSecs ?>" style="font-size:20px; color:#FF9500; text-shadow:0 0 12px rgba(255,149,0,0.5); letter-spacing:2px;">--:--:--</span>
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

<style>
/* ===== DASHBOARD PREMIUM ===== */
.dashboard-page { position: relative; }
.dashboard-page::before {
  content: '';
  position: fixed; inset: 0; z-index: 0;
  background:
    radial-gradient(ellipse at 10% 20%, rgba(0,212,255,0.06) 0%, transparent 45%),
    radial-gradient(ellipse at 90% 80%, rgba(123,47,255,0.06) 0%, transparent 45%);
  pointer-events: none;
}

/* Greeting */
.dashboard-greeting { position: relative; z-index: 1; padding: 20px 16px 12px; }
.greeting-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 2px; }
.greeting-name {
  font-size: 26px; font-weight: 900; letter-spacing: -0.5px;
  background: linear-gradient(135deg, #F1F5F9 40%, #00D4FF 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

/* Wallet cards */
.wallet-cards { padding: 0 16px 14px; display: flex; flex-direction: column; gap: 12px; }
.wallet-card {
  border-radius: 18px; padding: 18px 18px; display: flex; align-items: center; gap: 14px;
  position: relative; overflow: hidden;
}
.wallet-card::after {
  content: ''; position: absolute; top: 0; right: 0;
  width: 120px; height: 100%;
  background: radial-gradient(ellipse at right center, rgba(255,255,255,0.04), transparent);
  pointer-events: none;
}
.main-wallet { background: linear-gradient(135deg, rgba(0,212,255,0.12) 0%, rgba(13,21,40,1) 65%); border-left: 3px solid #00D4FF; border: 1px solid rgba(0,212,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,212,255,0.1), -2px 0 20px rgba(0,212,255,0.15); }
.free-wallet { background: linear-gradient(135deg, rgba(123,47,255,0.12) 0%, rgba(13,21,40,1) 65%); border: 1px solid rgba(123,47,255,0.2); box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 1px rgba(123,47,255,0.1), -2px 0 20px rgba(123,47,255,0.15); }
.wallet-icon { width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.main-wallet .wallet-icon { background: rgba(0,212,255,0.1); border: 1px solid rgba(0,212,255,0.2); }
.free-wallet .wallet-icon { background: rgba(123,47,255,0.1); border: 1px solid rgba(123,47,255,0.2); }
.wallet-info { flex: 1; }
.wallet-label { font-size: 11px; color: var(--text-muted); font-weight: 600; letter-spacing: 0.5px; display: block; margin-bottom: 3px; }
.wallet-amount { font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; display: block; }
.main-wallet .wallet-amount { color: #00D4FF; text-shadow: 0 0 20px rgba(0,212,255,0.4); }
.free-wallet .wallet-amount { color: #7B2FFF; text-shadow: 0 0 20px rgba(123,47,255,0.4); }

/* Notice */
.notice-banner {
  margin: 0 16px 14px; border-radius: 12px; padding: 12px 14px;
  display: flex; align-items: center; gap: 10px; font-size: 13px;
  background: rgba(255,149,0,0.07); border: 1px solid rgba(255,149,0,0.2);
  color: rgba(255,200,50,0.9); border-left: 3px solid #FF9500;
}
.notice-banner strong { color: #FFB800; }

/* Stats row */
.stats-row { padding: 0 16px 14px; display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
.stat-card {
  background: linear-gradient(135deg, rgba(255,255,255,0.04), var(--bg-card));
  border: 1px solid rgba(255,255,255,0.07); border-radius: 14px;
  padding: 14px 12px; text-align: center; position: relative; overflow: hidden;
  transition: transform .2s, border-color .2s;
}
.stat-card:hover { transform: translateY(-2px); border-color: rgba(0,212,255,0.2); }
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, rgba(0,212,255,0.4), transparent); }
.stat-label { font-size: 10px; color: var(--text-muted); font-weight: 600; letter-spacing: .5px; display: block; margin-bottom: 6px; }
.stat-value { font-family: 'Space Grotesk', sans-serif; font-size: 15px; font-weight: 700; display: block; }
.stat-value.cyan { color: #00D4FF; text-shadow: 0 0 12px rgba(0,212,255,0.4); }
.stat-value.purple { color: #7B2FFF; text-shadow: 0 0 12px rgba(123,47,255,0.4); }

/* Claim section */
.claim-section { padding: 0 16px 16px; display: flex; flex-direction: column; gap: 12px; }
.claim-form { width: 100%; }
.btn-claim-profit {
  background: linear-gradient(135deg, #00D4FF 0%, #0099CC 40%, #7B2FFF 100%) !important;
  background-size: 200% 200% !important;
  animation: gradient-shift 3s ease infinite !important;
  font-size: 17px !important; font-weight: 800 !important; letter-spacing: .5px;
  min-height: 58px !important; border-radius: 16px !important;
  box-shadow: 0 0 30px rgba(0,212,255,0.4), 0 6px 24px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.15) !important;
}
.btn-claim-profit:not(:disabled):hover {
  box-shadow: 0 0 50px rgba(0,212,255,0.6), 0 8px 30px rgba(0,0,0,0.6) !important;
  transform: translateY(-1px);
}
.btn-claim-profit:disabled { animation: none !important; opacity: .45; }

/* Countdown box */
.countdown-wrap {
  background: linear-gradient(135deg, rgba(255,149,0,0.07), rgba(255,100,0,0.04));
  border: 1px solid rgba(255,149,0,0.2); border-radius: 14px;
  padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;
}
.countdown-label { font-size: 12px; color: var(--text-muted); }
.countdown-timer {
  font-family: 'Space Grotesk', monospace; font-size: 22px; font-weight: 700;
  color: #FF9500; text-shadow: 0 0 16px rgba(255,149,0,0.5); letter-spacing: 3px;
}

/* Section */
.section { padding: 0 16px 16px; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.section-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.section-title::before { content: ''; display: block; width: 3px; height: 16px; background: linear-gradient(to bottom, #00D4FF, #7B2FFF); border-radius: 3px; }
.section-link { font-size: 12px; color: #00D4FF; font-weight: 600; }

/* Package cards */
.packages-list { display: flex; flex-direction: column; gap: 12px; }
.package-card {
  background: linear-gradient(135deg, rgba(0,212,255,0.04) 0%, var(--bg-card) 50%);
  border: 1px solid rgba(255,255,255,0.08); border-left: 3px solid #00D4FF;
  border-radius: 16px; padding: 16px; position: relative; overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.package-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,212,255,0.15); }
.package-card::before {
  content: ''; position: absolute; top: 0; right: 0; width: 100px; height: 100%;
  background: radial-gradient(ellipse at right, rgba(0,212,255,0.05), transparent);
  pointer-events: none;
}
.package-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.package-name { font-size: 15px; font-weight: 700; color: #F1F5F9; }
.package-category { font-size: 10px; color: #00D4FF; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }
.package-profit-day { font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 700; color: #00E676; text-shadow: 0 0 10px rgba(0,230,118,0.3); }
.package-progress { margin-bottom: 12px; }
.progress-bar { height: 7px; background: rgba(255,255,255,0.07); border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #00D4FF, #7B2FFF); box-shadow: 0 0 8px rgba(0,212,255,0.5); transition: width .8s ease; }
.progress-meta { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-top: 5px; }
.package-footer { display: flex; align-items: center; justify-content: space-between; }
.package-expire { font-size: 11px; color: var(--text-muted); }

/* Quick menu */
.quick-menu { padding: 0 16px 20px; display: grid; grid-template-columns: repeat(5,1fr); gap: 10px; }
.quick-item { display: flex; flex-direction: column; align-items: center; gap: 7px; cursor: pointer; text-decoration: none; }
.quick-icon {
  width: 52px; height: 52px; border-radius: 15px;
  display: flex; align-items: center; justify-content: center;
  transition: transform .2s, box-shadow .2s;
}
.quick-item:hover .quick-icon { transform: translateY(-3px); }
.quick-item span { font-size: 10px; color: var(--text-muted); font-weight: 600; text-align: center; }
.bg-cyan   { background: linear-gradient(135deg,#00D4FF,#0099BB); box-shadow: 0 4px 16px rgba(0,212,255,0.35); }
.bg-purple { background: linear-gradient(135deg,#7B2FFF,#5A1FCC); box-shadow: 0 4px 16px rgba(123,47,255,0.35); }
.bg-gold   { background: linear-gradient(135deg,#FFD700,#FF9500); box-shadow: 0 4px 16px rgba(255,215,0,0.3); }
.bg-green  { background: linear-gradient(135deg,#00E676,#00B050); box-shadow: 0 4px 16px rgba(0,230,118,0.3); }
.bg-teal   { background: linear-gradient(135deg,#00BCD4,#0097A7); box-shadow: 0 4px 16px rgba(0,188,212,0.3); }

/* Coin burst */
.coin-burst { position: fixed; inset: 0; pointer-events: none; z-index: 9998; overflow: hidden; }
.coin-particle {
  position: absolute; bottom: 30%; left: var(--x, 50%);
  font-size: var(--size, 20px); animation: coin-burst-anim 1.5s var(--delay, 0s) ease-out forwards;
}
@keyframes coin-burst-anim {
  0%   { transform: translateY(0) scale(0); opacity: 0; }
  20%  { opacity: 1; transform: translateY(-20px) scale(1); }
  100% { transform: translateY(-180px) scale(.6) rotate(720deg); opacity: 0; }
}

/* Empty state */
.empty-state { padding: 40px 20px; text-align: center; color: var(--text-muted); }
.empty-state svg { margin: 0 auto 14px; opacity: .35; }
.empty-state p { font-size: 14px; margin-bottom: 16px; }
</style>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>

