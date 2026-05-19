<?php
/**
 * NOXARA - Sidebar Drawer
 */
$userId = SessionManager::isLoggedIn() ? SessionManager::userId() : 0;
$wallet  = $userId ? getUserWallet($userId) : null;
$vipLvl  = SessionManager::get('user_vip', 0);
$userName = SessionManager::get('user_full', 'Member');
?>
<div class="sidebar-backdrop hidden" id="sidebarBackdrop"></div>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Menu Utama">
  <div class="sidebar-header">
    <span class="logo-text orbitron">NOXARA</span>
    <button class="btn-icon sidebar-close" id="sidebarClose">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>

  <?php if (SessionManager::isLoggedIn() && $wallet): ?>
  <div class="sidebar-profile">
    <div class="sidebar-avatar">
      <?php $av = SessionManager::get('user_avatar',''); ?>
      <?php if ($av): ?><img src="<?= uploadUrl($av) ?>" alt="Avatar">
      <?php else: ?><div class="avatar-placeholder"><?= strtoupper(substr($userName,0,1)) ?></div><?php endif; ?>
    </div>
    <div class="sidebar-user-info">
      <div class="sidebar-name"><?= e($userName) ?></div>
      <div class="vip-badge vip-<?= $vipLvl ?>">VIP <?= $vipLvl ?></div>
    </div>
  </div>
  <div class="sidebar-balance">
    <div class="balance-row">
      <span class="balance-label">Saldo Utama</span>
      <span class="balance-amount cyan"><?= formatRupiah((float)$wallet['main_balance']) ?></span>
    </div>
    <div class="balance-row">
      <span class="balance-label">Saldo Gratis</span>
      <span class="balance-amount purple"><?= formatRupiah((float)$wallet['free_balance']) ?></span>
    </div>
    <p class="free-balance-note">Saldo gratis hanya untuk beli paket dan tidak bisa ditarik</p>
  </div>
  <?php endif; ?>

  <ul class="sidebar-nav">
    <?php if (SessionManager::isLoggedIn()): ?>
    <li><a href="<?= BASE_URL ?>/pages/dashboard.php" class="nav-item <?= str_contains($_SERVER['PHP_SELF']??'','dashboard') ? 'active' : '' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
      Dashboard
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/deposit.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Isi Ulang
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/withdraw.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Tarik Dana
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/products.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke="currentColor" stroke-width="2"/></svg>
      Paket Mining
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/my_packages.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Paket Aktif
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/referral.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Referral
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/ads.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polygon points="5 3 19 12 5 21 5 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Tonton Iklan
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/daily_reward.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 12V22H4V12M22 7H2v5h20V7zM12 22V7M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Hadiah Harian
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/missions.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Misi
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/leaderboard.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M8 6H3v12h5V6zM14 2H10v16h4V2zM21 9h-4v9h4V9z" stroke="currentColor" stroke-width="2"/></svg>
      Leaderboard
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/chat.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Chat Admin
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/vip.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Level VIP
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/history.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M3.05 11a9 9 0 101.79-4.24M3 3v4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Riwayat
    </a></li>
    <li><a href="<?= BASE_URL ?>/pages/profile.php" class="nav-item">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Profil
    </a></li>
    <li><a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item nav-logout">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Keluar
    </a></li>
    <?php else: ?>
    <li><a href="<?= BASE_URL ?>/" class="nav-item">Beranda</a></li>
    <li><a href="<?= BASE_URL ?>/auth/login.php" class="nav-item">Masuk</a></li>
    <li><a href="<?= BASE_URL ?>/auth/register.php" class="nav-item">Daftar</a></li>
    <?php endif; ?>
  </ul>

  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/pages/faq.php">FAQ</a> &bull;
    <a href="<?= BASE_URL ?>/pages/info.php">Info</a> &bull;
    <a href="<?= BASE_URL ?>/pages/contact.php">Kontak</a>
  </div>
</nav>
