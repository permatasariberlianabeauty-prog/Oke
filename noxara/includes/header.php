<?php
/**
 * NOXARA - Header Template
 * @var string $pageTitle
 * @var string $bodyClass
 */
if (!isset($pageTitle)) $pageTitle = 'NOXARA';
if (!isset($bodyClass)) $bodyClass = '';
$siteName = getSetting('site_name', 'NOXARA');
$faviconUrl = getSetting('favicon_url', '');
$unreadCount = SessionManager::isLoggedIn() ? getUnreadCount(SessionManager::userId()) : 0;
$csrfToken   = CSRF::token();
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0A0E1A">
<meta name="description" content="<?= e(getSetting('meta_description','NOXARA - Invest Smarter, Grow Faster')) ?>">
<?= CSRF::meta() ?>
<title><?= e($pageTitle) ?> | <?= e($siteName) ?></title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<?php if ($faviconUrl): ?><link rel="icon" href="<?= e($faviconUrl) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">
</head>
<body class="dark-theme <?= e($bodyClass) ?>">

<!-- Popup Container -->
<div id="toast-container" aria-live="polite"></div>
<div id="modal-overlay" class="modal-overlay hidden"></div>

<?php echo renderPopupContainer(); ?>

<!-- Topbar -->
<header class="topbar" id="topbar">
  <div class="topbar-inner">
    <button class="btn-icon sidebar-toggle" id="sidebarToggle" aria-label="Menu">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="topbar-logo">
      <span class="logo-text orbitron">NOXARA</span>
    </a>
    <div class="topbar-actions">
      <?php if (SessionManager::isLoggedIn()): ?>
      <button class="btn-icon notif-toggle" id="notifToggle" aria-label="Notifikasi">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?php if ($unreadCount > 0): ?>
        <span class="badge-count"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
        <?php endif; ?>
      </button>
      <a href="<?= BASE_URL ?>/pages/profile.php" class="topbar-avatar">
        <?php $avatar = SessionManager::get('user_avatar',''); ?>
        <?php if ($avatar): ?>
        <img src="<?= uploadUrl($avatar) ?>" alt="Avatar" class="avatar-sm">
        <?php else: ?>
        <div class="avatar-placeholder-sm"><?= strtoupper(substr(SessionManager::get('user_name','U'),0,1)) ?></div>
        <?php endif; ?>
      </a>
      <?php else: ?>
      <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-sm btn-primary">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Marquee -->
<?php
$mq = db()->fetchOne('SELECT * FROM marquee_settings LIMIT 1');
if ($mq && $mq['is_enabled']):
    $items = [];
    if ($mq['include_deposits']) {
        $deps = db()->fetchAll('SELECT u.full_name, d.amount FROM deposits d JOIN users u ON u.id = d.user_id WHERE d.status = "confirmed" ORDER BY d.confirmed_at DESC LIMIT 5');
        foreach ($deps as $d) $items[] = '💰 ' . maskName($d['full_name']) . ' deposit ' . formatRupiah((float)$d['amount']);
    }
    if ($mq['include_purchases']) {
        $purs = db()->fetchAll('SELECT u.full_name, p.name FROM user_products up JOIN users u ON u.id = up.user_id JOIN products p ON p.id = up.product_id ORDER BY up.created_at DESC LIMIT 5');
        foreach ($purs as $p) $items[] = '⛏️ ' . maskName($p['full_name']) . ' beli paket ' . $p['name'];
    }
    if (!empty($mq['custom_messages'])) {
        foreach (explode("\n", $mq['custom_messages']) as $msg) {
            if (trim($msg)) $items[] = trim($msg);
        }
    }
    if (!empty($items)):
?>
<div class="marquee-bar" style="--marquee-speed:<?= (int)$mq['speed'] ?>s;--marquee-color:<?= e($mq['color']) ?>">
  <div class="marquee-inner">
    <span class="marquee-content">
      <?php foreach ($items as $item): ?><?= e($item) ?> &nbsp;&nbsp;•&nbsp;&nbsp; <?php endforeach; ?>
      <?php foreach ($items as $item): ?><?= e($item) ?> &nbsp;&nbsp;•&nbsp;&nbsp; <?php endforeach; ?>
    </span>
  </div>
</div>
<?php endif; endif; ?>

<!-- Notification Panel -->
<?php require_once INCLUDES_PATH . '/notification_panel.php'; ?>

<!-- Sidebar -->
<?php require_once INCLUDES_PATH . '/sidebar.php'; ?>

<main class="main-content" id="mainContent">
