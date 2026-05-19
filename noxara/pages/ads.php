<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId = SessionManager::userId();
$today  = date('Y-m-d');

// Ambil konfigurasi iklan
$maxWatchPerDay = (int)getSetting('ads_max_per_day', '5');
$cooldownSecs   = (int)getSetting('ads_cooldown_seconds', '30');

// Hitung sudah nonton hari ini
$todayWatchRow = db()->fetchOne(
    'SELECT COUNT(*) as cnt FROM ad_watches WHERE user_id = ? AND DATE(created_at) = CURDATE()',
    'i', [$userId]
);
$todayCount = (int)($todayWatchRow['cnt'] ?? 0);
$canWatch   = $todayCount < $maxWatchPerDay;

// Waktu nonton terakhir untuk cooldown
$lastWatch = db()->fetchOne(
    'SELECT created_at FROM ad_watches WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
    'i', [$userId]
);
$lastWatchTime  = $lastWatch ? strtotime($lastWatch['created_at']) : 0;
$secondsSinceLast = time() - $lastWatchTime;
$cooldownLeft   = max(0, $cooldownSecs - $secondsSinceLast);

// POST: tandai sudah nonton
if (isPost() && isset($_POST['watch_ad'])) {
    CSRF::verify();
    $adId = (int)postVal('ad_id', 0);

    if (!$canWatch) {
        setFlashPopup('error', 'Batas nonton iklan hari ini sudah tercapai (' . $maxWatchPerDay . ' kali).', 'Limit Tercapai');
        redirect(BASE_URL . '/pages/ads.php');
    }
    if ($cooldownLeft > 0) {
        setFlashPopup('error', 'Tunggu ' . $cooldownLeft . ' detik sebelum nonton iklan berikutnya.', 'Cooldown');
        redirect(BASE_URL . '/pages/ads.php');
    }

    $ad = db()->fetchOne('SELECT * FROM ads WHERE id = ? AND is_active = 1 LIMIT 1', 'i', [$adId]);
    if ($ad) {
        // Catat tontonan
        db()->execute(
            'INSERT INTO ad_watches (user_id, ad_id, reward_amount, ip_address) VALUES (?,?,?,?)',
            'iids', [$userId, $adId, (float)$ad['reward'], getClientIp()]
        );

        // Kredit reward ke saldo
        if ((float)$ad['reward'] > 0) {
            creditBalance($userId, WALLET_FREE, (float)$ad['reward'], TX_BONUS_AD,
                'ad', $adId, 'Reward nonton iklan: ' . $ad['title']);
        }

        // Update mission watch_ads
        updateMissionProgress($userId, MISSION_WATCH_ADS, 1);

        setFlashPopup('success', 'Reward ' . formatRupiah((float)$ad['reward']) . ' berhasil ditambahkan ke saldo gratis!', 'Iklan Selesai 🎉');
    } else {
        setFlashPopup('error', 'Iklan tidak ditemukan.', 'Gagal');
    }
    redirect(BASE_URL . '/pages/ads.php');
}

$ads = db()->fetchAll('SELECT * FROM ads WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');

// Riwayat nonton hari ini
$watchHistory = db()->fetchAll(
    'SELECT aw.*, a.title FROM ad_watches aw JOIN ads a ON a.id = aw.ad_id
     WHERE aw.user_id = ? AND DATE(aw.created_at) = CURDATE() ORDER BY aw.created_at DESC',
    'i', [$userId]
);

$pageTitle = 'Tonton Iklan';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Tonton Iklan</h1>
  </div>

  <!-- Progress -->
  <div class="card ads-progress-card">
    <div class="ads-progress-row">
      <div>
        <span class="ads-progress-label">Ditonton Hari Ini</span>
        <span class="ads-progress-value"><?= $todayCount ?> / <?= $maxWatchPerDay ?></span>
      </div>
      <?php if ($cooldownLeft > 0): ?>
      <div class="cooldown-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#FFB800" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#FFB800" stroke-width="2" stroke-linecap="round"/></svg>
        Cooldown: <span id="cooldownTimer" data-seconds="<?= $cooldownLeft ?>">--</span>s
      </div>
      <?php endif; ?>
    </div>
    <div class="progress-bar">
      <div class="progress-fill cyan" style="width:<?= $maxWatchPerDay>0?round($todayCount/$maxWatchPerDay*100):0 ?>%"></div>
    </div>
    <?php if (!$canWatch): ?>
    <p class="text-muted" style="margin-top:8px;font-size:.85rem">
      Batas iklan harian tercapai. Kembali besok!
    </p>
    <?php endif; ?>
  </div>

  <!-- Ads List -->
  <?php if (empty($ads)): ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M23 7l-7 5 7 5V7z" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="1" y="5" width="15" height="14" rx="2" stroke="#7B2FFF" stroke-width="1.5"/></svg>
    <p>Belum ada iklan tersedia.</p>
  </div>
  <?php else: ?>
  <div class="ads-grid">
    <?php foreach ($ads as $ad):
      $duration = (int)($ad['watch_duration'] ?? 15);
      $reward   = (float)$ad['reward'];
      // Cek apakah sudah nonton iklan ini hari ini
      $watchedToday = false;
      foreach ($watchHistory as $wh) {
          if ((int)$wh['ad_id'] === (int)$ad['id']) { $watchedToday = true; break; }
      }
    ?>
    <div class="ad-card card <?= !$canWatch || $watchedToday ? 'ad-done' : '' ?>">
      <?php if (!empty($ad['image'])): ?>
      <div class="ad-img-wrap">
        <img src="<?= uploadUrl($ad['image']) ?>" alt="<?= e($ad['title']) ?>" class="ad-img" loading="lazy">
      </div>
      <?php else: ?>
      <div class="ad-img-placeholder">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M23 7l-7 5 7 5V7z" stroke="#00D4FF" stroke-width="1.5"/><rect x="1" y="5" width="15" height="14" rx="2" stroke="#00D4FF" stroke-width="1.5"/></svg>
      </div>
      <?php endif; ?>
      <div class="ad-body">
        <h3 class="ad-title"><?= e($ad['title']) ?></h3>
        <?php if (!empty($ad['description'])): ?>
        <p class="ad-desc"><?= e($ad['description']) ?></p>
        <?php endif; ?>
        <div class="ad-meta">
          <span class="ad-reward cyan">+<?= e(formatRupiah($reward)) ?></span>
          <span class="ad-duration">⏱ <?= $duration ?> detik</span>
        </div>
        <?php if ($watchedToday): ?>
        <div class="btn btn-ghost btn-full btn-disabled" style="min-height:48px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Sudah Ditonton
        </div>
        <?php elseif (!$canWatch): ?>
        <div class="btn btn-ghost btn-full btn-disabled" style="min-height:48px">Batas Tercapai</div>
        <?php else: ?>
        <button type="button" class="btn btn-primary btn-full"
          style="min-height:48px"
          data-ad-id="<?= (int)$ad['id'] ?>"
          data-duration="<?= $duration ?>"
          data-ad-url="<?= e($ad['url'] ?? '') ?>"
          onclick="startWatchAd(this)">
          Tonton & Dapatkan Reward
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Riwayat -->
  <?php if (!empty($watchHistory)): ?>
  <div class="section">
    <h2 class="section-title">Riwayat Hari Ini</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Waktu</th><th>Iklan</th><th>Reward</th></tr></thead>
        <tbody>
          <?php foreach ($watchHistory as $wh): ?>
          <tr>
            <td><?= e(formatDate($wh['created_at'], 'H:i')) ?></td>
            <td><?= e($wh['title']) ?></td>
            <td class="cyan">+<?= e(formatRupiah((float)$wh['reward_amount'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- Watch Modal -->
<div class="modal hidden" id="watchModal" role="dialog" aria-modal="true">
  <div class="modal-card">
    <div class="modal-header">
      <h2 class="modal-title">Tonton Iklan</h2>
    </div>
    <div class="modal-body" style="text-align:center">
      <div id="adContent" class="ad-content-area"></div>
      <div class="watch-timer-wrap">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#00D4FF" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
        Tunggu <span id="watchCountdown" class="orbitron cyan">0</span> detik
      </div>
      <form method="post" action="" id="watchForm">
        <?= CSRF::field() ?>
        <input type="hidden" name="ad_id" id="watchAdId" value="">
        <button type="submit" name="watch_ad" value="1"
          id="watchSubmitBtn" class="btn btn-primary btn-full" style="min-height:48px" disabled>
          Klaim Reward
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Cooldown timer
var coolEl = document.getElementById('cooldownTimer');
if (coolEl) {
  var secs = parseInt(coolEl.dataset.seconds || 0);
  var ci = setInterval(function(){
    secs--;
    if (secs <= 0) { clearInterval(ci); location.reload(); return; }
    coolEl.textContent = secs;
  }, 1000);
}

function startWatchAd(btn) {
  var adId    = btn.dataset.adId;
  var dur     = parseInt(btn.dataset.duration || 15);
  var adUrl   = btn.dataset.adUrl;

  document.getElementById('watchAdId').value = adId;
  var countdown = document.getElementById('watchCountdown');
  var submitBtn = document.getElementById('watchSubmitBtn');
  var adContent = document.getElementById('adContent');
  submitBtn.disabled = true;

  // Render ad content
  if (adUrl && adUrl.includes('youtube')) {
    var vid = adUrl.match(/(?:v=|youtu\.be\/)([^&?/]+)/);
    if (vid) adContent.innerHTML = '<iframe width="100%" height="180" src="https://www.youtube.com/embed/'+vid[1]+'?autoplay=1&mute=1" frameborder="0" allowfullscreen></iframe>';
  } else if (adUrl) {
    adContent.innerHTML = '<a href="'+adUrl+'" target="_blank" rel="noopener" class="btn btn-outline-cyan btn-full">Buka Iklan ↗</a>';
  } else {
    adContent.innerHTML = '<div class="ad-placeholder-anim"></div>';
  }

  document.getElementById('watchModal').classList.remove('hidden');
  document.getElementById('modal-overlay').classList.remove('hidden');

  countdown.textContent = dur;
  var t = setInterval(function(){
    dur--;
    countdown.textContent = dur;
    if (dur <= 0) {
      clearInterval(t);
      submitBtn.disabled = false;
      submitBtn.textContent = '🎁 Klaim Reward';
    }
  }, 1000);
}

document.getElementById('modal-overlay').addEventListener('click', function(){
  document.getElementById('watchModal').classList.add('hidden');
  document.getElementById('modal-overlay').classList.add('hidden');
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
