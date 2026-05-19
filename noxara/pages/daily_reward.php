<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/missions.php';

requireLogin();

$userId = SessionManager::userId();
$today  = date('Y-m-d');

// Cek sudah klaim hari ini
$claimed = db()->fetchOne(
    'SELECT udc.*, dri.name as reward_name, dri.type as reward_type
     FROM user_daily_claims udc
     LEFT JOIN daily_reward_items dri ON dri.id = udc.reward_item_id
     WHERE udc.user_id = ? AND udc.claim_date = ? LIMIT 1',
    'is', [$userId, $today]
);
$alreadyClaimed = !empty($claimed);

$claimResult = null;

if (isPost() && isset($_POST['claim_daily'])) {
    CSRF::verify();
    if (!$alreadyClaimed) {
        $res = claimDailyReward($userId);
        if ($res['success']) {
            $claimResult = $res['item'];
            setFlashPopup('success', 'Selamat! Anda mendapatkan ' . $res['item']['name'] . '!', 'Hadiah Harian 🎁');
        } else {
            setFlashPopup('error', $res['message'], 'Gagal');
        }
    }
    redirect(BASE_URL . '/pages/daily_reward.php?claimed=1');
}

$showConfetti = isset($_GET['claimed']);

// Riwayat klaim 30 hari
$claimHistory = db()->fetchAll(
    'SELECT udc.*, dri.name as reward_name, dri.type as reward_type
     FROM user_daily_claims udc
     LEFT JOIN daily_reward_items dri ON dri.id = udc.reward_item_id
     WHERE udc.user_id = ? ORDER BY udc.claim_date DESC LIMIT 30',
    'i', [$userId]
);

// Reward items (untuk tampilan kotak hadiah)
$rewardItems = db()->fetchAll('SELECT * FROM daily_reward_items WHERE is_active = 1 ORDER BY sort_order ASC');
$settings    = db()->fetchOne('SELECT * FROM daily_reward_settings LIMIT 1');
$isEnabled   = $settings && $settings['is_enabled'];

$pageTitle = 'Hadiah Harian';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Hadiah Harian</h1>
  </div>

  <!-- Gift Box Claim Area -->
  <div class="daily-reward-hero card">
    <div class="gift-box-wrap" id="giftBoxWrap">
      <?php if ($alreadyClaimed): ?>
      <div class="gift-opened">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none"><path d="M20 12v10H4V12" stroke="#00D4FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 7H2v5h20V7z" stroke="#00D4FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" stroke="#00D4FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php if ($claimed): ?>
        <p class="gift-result orbitron cyan"><?= e($claimed['reward_name'] ?? 'Hadiah') ?></p>
        <p class="gift-value">+<?= e(formatRupiah((float)($claimed['reward_value'] ?? 0))) ?></p>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <button type="button" class="gift-box-btn" id="giftBoxBtn" <?= !$isEnabled ? 'disabled' : '' ?>>
        <div class="gift-box-anim" id="giftBoxAnim">
          <svg class="gift-svg" width="80" height="80" viewBox="0 0 24 24" fill="none">
            <path d="M20 12v10H4V12" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 7H2v5h20V7z" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p class="gift-cta">Ketuk untuk membuka!</p>
        </div>
      </button>
      <?php endif; ?>
    </div>

    <form method="post" action="" id="dailyClaimForm">
      <?= CSRF::field() ?>
      <?php if (!$alreadyClaimed && $isEnabled): ?>
      <button type="submit" name="claim_daily" value="1"
        class="btn btn-primary btn-full btn-lg btn-glow" id="claimDailyBtn">
        🎁 Klaim Hadiah Harian
      </button>
      <?php elseif ($alreadyClaimed): ?>
      <div class="claimed-info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sudah diklaim hari ini. Kembali besok!
      </div>
      <?php else: ?>
      <div class="notice-banner notice-warning">Fitur hadiah harian tidak aktif.</div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Possible Rewards -->
  <?php if (!empty($rewardItems)): ?>
  <div class="card">
    <h2 class="card-title">Kemungkinan Hadiah</h2>
    <div class="reward-items-grid">
      <?php foreach ($rewardItems as $item): ?>
      <div class="reward-item">
        <div class="reward-item-icon">
          <?php if (!empty($item['icon'])): ?>
          <span class="reward-emoji"><?= e($item['icon']) ?></span>
          <?php else: ?>
          🎁
          <?php endif; ?>
        </div>
        <div class="reward-item-info">
          <span class="reward-item-name"><?= e($item['name']) ?></span>
          <span class="reward-item-value cyan"><?= e(formatRupiah((float)$item['value'])) ?></span>
          <span class="reward-item-prob text-muted"><?= e($item['probability']) ?>% peluang</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Claim History -->
  <?php if (!empty($claimHistory)): ?>
  <div class="section">
    <h2 class="section-title">Riwayat Klaim</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Tanggal</th><th>Hadiah</th><th>Nilai</th></tr></thead>
        <tbody>
          <?php foreach ($claimHistory as $ch): ?>
          <tr>
            <td><?= e(formatDate($ch['claim_date'])) ?></td>
            <td><?= e($ch['reward_name'] ?? '-') ?></td>
            <td class="cyan">+<?= e(formatRupiah((float)$ch['reward_value'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- Confetti container -->
<div id="confettiContainer" class="confetti-container hidden" aria-hidden="true"></div>

<script>
<?php if ($showConfetti && !$alreadyClaimed): ?>
// This runs before alreadyClaimed check is refreshed; show confetti on redirect
<?php endif; ?>

// Animate gift box on click
var giftBtn = document.getElementById('giftBoxBtn');
if (giftBtn) {
  giftBtn.addEventListener('click', function(){
    var anim = document.getElementById('giftBoxAnim');
    anim.classList.add('shaking');
  });
}

// Submit triggers animation then form submit
var claimBtn = document.getElementById('claimDailyBtn');
if (claimBtn) {
  claimBtn.addEventListener('click', function(e){
    triggerConfetti();
    var anim = document.getElementById('giftBoxAnim');
    if (anim) { anim.classList.add('opening'); }
  });
}

function triggerConfetti() {
  var container = document.getElementById('confettiContainer');
  if (!container) return;
  container.classList.remove('hidden');
  container.innerHTML = '';
  var colors = ['#00D4FF','#7B2FFF','#FFD700','#FF4757','#2ED573'];
  for (var i = 0; i < 60; i++) {
    var p = document.createElement('div');
    p.className = 'confetti-piece';
    p.style.cssText = '--x:'+(Math.random()*100)+'vw;--color:'+colors[Math.floor(Math.random()*colors.length)]+';--delay:'+(Math.random()*1.5)+'s;--size:'+(6+Math.random()*8)+'px';
    container.appendChild(p);
  }
  setTimeout(function(){ container.classList.add('hidden'); container.innerHTML=''; }, 3000);
}

<?php if ($showConfetti): ?>
document.addEventListener('DOMContentLoaded', triggerConfetti);
<?php endif; ?>
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
