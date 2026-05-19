<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/vip.php';

requireLogin();

$userId = SessionManager::userId();
$user   = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$currentVip  = (int)($user['vip_level'] ?? 0);
$wallet      = getUserWallet($userId);
$totalDeposit= (float)$wallet['total_deposit'];

$vipLevels = getVipLevels();

// Temukan next level
$nextLevel = null;
foreach ($vipLevels as $vl) {
    if ((int)$vl['level'] > $currentVip) {
        $nextLevel = $vl;
        break;
    }
}

// Progress ke next level
$progressPercent = 0;
$progressLabel   = '';
if ($nextLevel) {
    $currentLevelData = getVipLevel($currentVip);
    $minCurrent = (float)($currentLevelData['min_deposit'] ?? 0);
    $minNext    = (float)$nextLevel['min_deposit'];
    $range      = $minNext - $minCurrent;
    if ($range > 0) {
        $done = max(0, $totalDeposit - $minCurrent);
        $progressPercent = min(100, round($done / $range * 100));
    }
    $progressLabel = formatRupiah($totalDeposit) . ' / ' . formatRupiah($minNext);
}

$vipColors = ['#888','#00D4FF','#7B2FFF','#FFB800','#FF4757','#2ED573'];

$pageTitle = 'Level VIP';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Level VIP</h1>
  </div>

  <!-- Current VIP -->
  <div class="card current-vip-card">
    <div class="current-vip-header">
      <div class="vip-crown">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M2 20h20M5 20l2-10 5 5 5-7 2 12" stroke="<?= e($vipColors[$currentVip] ?? '#888') ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="current-vip-info">
        <span class="current-vip-label">Level VIP Anda</span>
        <span class="current-vip-name orbitron" style="color:<?= e($vipColors[$currentVip]??'#888') ?>">
          VIP <?= $currentVip ?>
          <?php
          $currentLevelRow = getVipLevel($currentVip);
          if ($currentLevelRow) echo ' — ' . e($currentLevelRow['name']);
          ?>
        </span>
        <span class="current-vip-deposit">Total Deposit: <strong class="cyan"><?= e(formatRupiah($totalDeposit)) ?></strong></span>
      </div>
    </div>

    <?php if ($nextLevel): ?>
    <div class="vip-progress-section">
      <div class="vip-progress-label">
        <span>Progress ke VIP <?= (int)$nextLevel['level'] ?></span>
        <span><?= $progressPercent ?>%</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $progressPercent ?>%;background:<?= e($vipColors[(int)$nextLevel['level']]??'#00D4FF') ?>"></div>
      </div>
      <p class="form-hint"><?= e($progressLabel) ?></p>
      <p class="form-hint">Butuh deposit tambahan: <strong class="cyan"><?= e(formatRupiah(max(0,(float)$nextLevel['min_deposit']-$totalDeposit))) ?></strong></p>
    </div>
    <?php else: ?>
    <div class="notice-banner notice-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#2ED573" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Anda sudah mencapai level VIP tertinggi! 🎉
    </div>
    <?php endif; ?>
  </div>

  <!-- VIP Level Cards -->
  <div class="vip-levels-list">
    <?php foreach ($vipLevels as $vl):
      $lvl     = (int)$vl['level'];
      $isCurrent = $lvl === $currentVip;
      $isAchieved= $lvl <= $currentVip;
      $color   = $vipColors[$lvl] ?? '#888';
    ?>
    <div class="vip-level-card card <?= $isCurrent?'vip-current':'' ?> <?= $isAchieved?'vip-achieved':'' ?>">
      <div class="vip-level-header" style="border-left:3px solid <?= $color ?>">
        <div class="vip-level-badge" style="background:<?= $color ?>22;color:<?= $color ?>">
          VIP <?= $lvl ?>
        </div>
        <div class="vip-level-info">
          <h3 class="vip-level-name" style="color:<?= $color ?>"><?= e($vl['name']) ?></h3>
          <span class="vip-level-req">Min. Deposit: <?= e(formatRupiah((float)$vl['min_deposit'])) ?></span>
        </div>
        <?php if ($isCurrent): ?>
        <span class="vip-current-badge">Saat Ini</span>
        <?php elseif ($isAchieved): ?>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#2ED573" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php else: ?>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="<?= $color ?>" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="<?= $color ?>" stroke-width="2"/></svg>
        <?php endif; ?>
      </div>
      <div class="vip-level-benefits">
        <div class="benefit-row">
          <span>Min. Withdraw</span>
          <span class="cyan"><?= e(formatRupiah((float)$vl['min_withdraw'])) ?></span>
        </div>
        <div class="benefit-row">
          <span>Biaya Admin</span>
          <span><?= e($vl['withdraw_fee_percent']) ?>%</span>
        </div>
        <?php if (!empty($vl['description'])): ?>
        <p class="vip-desc text-muted"><?= e($vl['description']) ?></p>
        <?php endif; ?>
      </div>
      <?php if (!$isAchieved && !$isCurrent): ?>
      <a href="<?= BASE_URL ?>/pages/deposit.php" class="btn btn-sm btn-outline-cyan btn-full">
        Deposit untuk Naik Level
      </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
