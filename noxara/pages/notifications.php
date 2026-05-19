<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/notification.php';

requireLogin();

$userId = SessionManager::userId();

if (isPost() && isset($_POST['mark_all_read'])) {
    CSRF::verify();
    markAllRead($userId);
    setFlashPopup('success', 'Semua notifikasi telah ditandai dibaca.', 'Info');
    redirect(BASE_URL . '/pages/notifications.php');
}

if (isPost() && isset($_POST['mark_read'])) {
    CSRF::verify();
    $notifId = (int)postVal('notif_id', 0);
    if ($notifId > 0) markRead($notifId, $userId);
    redirect(BASE_URL . '/pages/notifications.php');
}

$filterType = clean(getVal('type', ''));

$perPage = 20;
$page    = max(1, (int)getVal('page', 1));

$where   = '(n.user_id = ? OR n.is_broadcast = 1)';
$params  = [$userId];
$types   = 'i';

if ($filterType !== '') {
    $where .= ' AND n.type = ?';
    $params[] = $filterType;
    $types .= 's';
}

$countRow = db()->fetchOne("SELECT COUNT(*) as cnt FROM notifications n WHERE {$where}", $types, $params);
$total    = (int)($countRow['cnt'] ?? 0);
$paginator= paginate($total, $page, $perPage);

$params2  = $params;
$params2[]= $perPage;
$params2[]= $paginator['offset'];
$types2   = $types . 'ii';

$notifications = db()->fetchAll(
    "SELECT * FROM notifications n WHERE {$where} ORDER BY n.created_at DESC LIMIT ? OFFSET ?",
    $types2, $params2
);

$unreadCount = getUnreadCount($userId);

// Distinct types for filter
$notifTypes = db()->fetchAll(
    'SELECT DISTINCT type FROM notifications WHERE (user_id = ? OR is_broadcast = 1) ORDER BY type',
    'i', [$userId]
);

$typeIcons = [
    'deposit_approved' => '💰', 'deposit_rejected' => '❌',
    'withdraw_approved'=> '💸', 'withdraw_rejected'=> '❌',
    'purchase_success' => '📦', 'profit_claimed'   => '⭐',
    'vip_upgrade'      => '👑', 'referral'         => '🤝',
    'system'           => '🔔', 'promo'            => '🎁',
];

$pageTitle = 'Notifikasi';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Notifikasi <?php if ($unreadCount > 0): ?><span class="badge-count"><?= $unreadCount ?></span><?php endif; ?></h1>
  </div>

  <!-- Actions -->
  <div class="notif-actions">
    <?php if ($unreadCount > 0): ?>
    <form method="post" action="" class="inline-form">
      <?= CSRF::field() ?>
      <button type="submit" name="mark_all_read" value="1" class="btn btn-sm btn-outline-cyan">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Tandai Semua Dibaca
      </button>
    </form>
    <?php endif; ?>
  </div>

  <!-- Filter -->
  <div class="filter-scroll">
    <a href="?type=" class="filter-chip <?= $filterType===''?'active':'' ?>">Semua</a>
    <?php foreach ($notifTypes as $nt): ?>
    <a href="?type=<?= e($nt['type']) ?>" class="filter-chip <?= $filterType===$nt['type']?'active':'' ?>">
      <?= $typeIcons[$nt['type']] ?? '🔔' ?> <?= e(ucfirst(str_replace('_',' ',$nt['type']))) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Notification List -->
  <?php if (empty($notifications)): ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <p>Tidak ada notifikasi.</p>
  </div>
  <?php else: ?>
  <div class="notif-list">
    <?php foreach ($notifications as $n):
      $icon = $typeIcons[$n['type']] ?? '🔔';
      $isUnread = !(int)$n['is_read'];
    ?>
    <div class="notif-item card <?= $isUnread ? 'notif-unread' : '' ?>">
      <div class="notif-icon-wrap">
        <span class="notif-emoji"><?= $icon ?></span>
      </div>
      <div class="notif-content">
        <div class="notif-header-row">
          <h3 class="notif-title"><?= e($n['title']) ?></h3>
          <?php if ($isUnread): ?>
          <span class="unread-dot"></span>
          <?php endif; ?>
        </div>
        <p class="notif-message"><?= e($n['message']) ?></p>
        <div class="notif-meta">
          <span class="notif-time"><?= e(timeAgo($n['created_at'])) ?></span>
          <?php if (!empty($n['action_url'])): ?>
          <a href="<?= e($n['action_url']) ?>" class="notif-action-link">Lihat →</a>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($isUnread): ?>
      <form method="post" action="" class="notif-read-form">
        <?= CSRF::field() ?>
        <input type="hidden" name="notif_id" value="<?= (int)$n['id'] ?>">
        <button type="submit" name="mark_read" value="1" class="btn-icon-xs" aria-label="Tandai dibaca" title="Tandai dibaca">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($paginator['total_pages'] > 1): ?>
  <div class="pagination">
    <?php if ($paginator['has_prev']): ?>
    <a href="?type=<?= e($filterType) ?>&page=<?= $paginator['current']-1 ?>" class="page-btn">‹</a>
    <?php endif; ?>
    <?php for ($p = max(1,$paginator['current']-2); $p <= min($paginator['total_pages'],$paginator['current']+2); $p++): ?>
    <a href="?type=<?= e($filterType) ?>&page=<?= $p ?>"
      class="page-btn <?= $p===$paginator['current']?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($paginator['has_next']): ?>
    <a href="?type=<?= e($filterType) ?>&page=<?= $paginator['current']+1 ?>" class="page-btn">›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
