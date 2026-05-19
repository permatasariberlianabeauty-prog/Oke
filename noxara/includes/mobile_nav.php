<?php
/**
 * NOXARA - Bottom Mobile Navigation + FAB
 */
$cur = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!-- FAB Menu Overlay -->
<div class="fab-overlay hidden" id="fabOverlay"></div>
<div class="fab-menu hidden" id="fabMenu">
  <div class="fab-menu-inner">
    <button class="fab-close" id="fabClose">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <div class="fab-grid">
      <a href="<?= BASE_URL ?>/pages/products.php" class="fab-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke="#00D4FF" stroke-width="2"/></svg>
        <span>Beli Paket</span>
      </a>
      <a href="<?= BASE_URL ?>/pages/my_packages.php" class="fab-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#7B2FFF" stroke-width="2"/><path d="M12 6v6l4 2" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Paket Aktif</span>
      </a>
      <a href="<?= BASE_URL ?>/pages/my_packages.php#klaim" class="fab-item fab-item-highlight">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="#00D4FF" stroke-width="2" fill="rgba(0,212,255,0.15)"/></svg>
        <span>Klaim Profit</span>
      </a>
      <a href="<?= BASE_URL ?>/pages/deposit.php" class="fab-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Deposit</span>
      </a>
      <a href="<?= BASE_URL ?>/pages/withdraw.php" class="fab-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Withdraw</span>
      </a>
      <a href="<?= BASE_URL ?>/pages/chat.php" class="fab-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Chat Admin</span>
      </a>
    </div>
  </div>
</div>

<!-- Bottom Navigation -->
<nav class="bottom-nav" role="navigation" aria-label="Navigasi Bawah">
  <a href="<?= BASE_URL ?>/pages/dashboard.php" class="bottom-nav-item <?= $cur==='dashboard.php'?'active':'' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
    <span>Home</span>
  </a>
  <a href="<?= BASE_URL ?>/pages/products.php" class="bottom-nav-item <?= $cur==='products.php'?'active':'' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" stroke="currentColor" stroke-width="2"/></svg>
    <span>Produk</span>
  </a>
  <!-- FAB Center Button -->
  <button class="bottom-nav-fab" id="fabBtn" aria-label="Menu Aksi">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="white" stroke-width="2.5" stroke-linecap="round"/></svg>
  </button>
  <a href="<?= BASE_URL ?>/pages/my_packages.php" class="bottom-nav-item <?= $cur==='my_packages.php'?'active':'' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    <span>Profit</span>
  </a>
  <a href="<?= BASE_URL ?>/pages/referral.php" class="bottom-nav-item <?= $cur==='referral.php'?'active':'' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span>Referral</span>
  </a>
  <a href="<?= BASE_URL ?>/pages/profile.php" class="bottom-nav-item <?= $cur==='profile.php'?'active':'' ?>">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span>Akun</span>
  </a>
</nav>
