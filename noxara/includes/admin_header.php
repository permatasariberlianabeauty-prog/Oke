<?php
/**
 * NOXARA - Admin Panel Header
 * @var string $pageTitle
 */
if (!isset($pageTitle)) $pageTitle = 'Admin Panel';
$adminName = SessionManager::get('admin_name', 'Admin');
$adminRole = SessionManager::adminRole();
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<?= CSRF::meta() ?>
<title><?= e($pageTitle) ?> - NOXARA Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">
</head>
<body class="dark-theme admin-body">
<div id="toast-container" aria-live="polite"></div>
<div id="modal-overlay" class="modal-overlay hidden"></div>
<?= renderPopupContainer() ?>

<div class="admin-layout">
<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-header">
    <span class="logo-text orbitron">NOXARA</span>
    <span class="admin-badge">ADMIN</span>
  </div>
  <div class="admin-profile">
    <div class="avatar-placeholder-sm"><?= strtoupper(substr($adminName,0,1)) ?></div>
    <div>
      <div class="admin-name"><?= e($adminName) ?></div>
      <div class="admin-role-badge"><?= strtoupper($adminRole) ?></div>
    </div>
  </div>
  <nav class="admin-nav">
    <a href="<?= BASE_URL ?>/admin/index.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
      Dashboard
    </a>
    <div class="admin-nav-group">Member</div>
    <a href="<?= BASE_URL ?>/admin/members.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" stroke-width="2"/></svg>
      Data Member
    </a>
    <div class="admin-nav-group">Keuangan</div>
    <a href="<?= BASE_URL ?>/admin/deposits.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Deposit
    </a>
    <a href="<?= BASE_URL ?>/admin/withdrawals.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Withdraw
    </a>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M8 6H3v12h5V6zM14 2H10v16h4V2zM21 9h-4v9h4V9z" stroke="currentColor" stroke-width="2"/></svg>
      Laporan
    </a>
    <div class="admin-nav-group">Konten</div>
    <a href="<?= BASE_URL ?>/admin/products.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8" stroke="currentColor" stroke-width="2"/></svg>
      Produk Mining
    </a>
    <a href="<?= BASE_URL ?>/admin/ads.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><polygon points="5 3 19 12 5 21 5 3" stroke="currentColor" stroke-width="2"/></svg>
      Iklan
    </a>
    <a href="<?= BASE_URL ?>/admin/banners.php" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/></svg>
      Banner
    </a>
    <a href="<?= BASE_URL ?>/admin/vouchers.php" class="admin-nav-item">Voucher</a>
    <a href="<?= BASE_URL ?>/admin/missions.php" class="admin-nav-item">Misi</a>
    <a href="<?= BASE_URL ?>/admin/daily_rewards.php" class="admin-nav-item">Hadiah Harian</a>
    <a href="<?= BASE_URL ?>/admin/notifications.php" class="admin-nav-item">Notifikasi</a>
    <a href="<?= BASE_URL ?>/admin/chat.php" class="admin-nav-item">Chat</a>
    <a href="<?= BASE_URL ?>/admin/legal_pages.php" class="admin-nav-item">Halaman Legal</a>
    <div class="admin-nav-group">Pengaturan</div>
    <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item">Pengaturan Umum</a>
    <a href="<?= BASE_URL ?>/admin/vip_settings.php" class="admin-nav-item">VIP Settings</a>
    <a href="<?= BASE_URL ?>/admin/commission_settings.php" class="admin-nav-item">Komisi Referral</a>
    <a href="<?= BASE_URL ?>/admin/popup_settings.php" class="admin-nav-item">Popup Settings</a>
    <a href="<?= BASE_URL ?>/admin/cron_status.php" class="admin-nav-item">Cron Status</a>
    <a href="<?= BASE_URL ?>/admin/backup.php" class="admin-nav-item">Backup</a>
    <a href="<?= BASE_URL ?>/admin/admin_security.php" class="admin-nav-item">Keamanan Admin</a>
    <a href="<?= BASE_URL ?>/admin/logout.php" class="admin-nav-item admin-logout">Keluar</a>
  </nav>
</aside>

<div class="admin-main">
<!-- Admin Topbar -->
<header class="admin-topbar">
  <button class="btn-icon admin-sidebar-toggle" id="adminSidebarToggle">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <h1 class="admin-topbar-title"><?= e($pageTitle) ?></h1>
  <div class="admin-topbar-right">
    <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-xs btn-ghost">Lihat Website</a>
  </div>
</header>
<div class="admin-content">
