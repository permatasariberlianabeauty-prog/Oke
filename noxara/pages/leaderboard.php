<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId    = SessionManager::userId();
$activeTab = clean(getVal('tab', 'deposit'));

// Top Deposit
$topDeposit = db()->fetchAll(
    'SELECT u.id, u.username, u.full_name, u.vip_level, uw.total_deposit
     FROM users u JOIN user_wallets uw ON uw.user_id = u.id
     WHERE u.is_active = 1 AND uw.total_deposit > 0
     ORDER BY uw.total_deposit DESC LIMIT 10'
);

// Top Referral
$topReferral = db()->fetchAll(
    'SELECT u.id, u.username, u.full_name, u.vip_level,
     COUNT(r.id) as total_ref,
     COALESCE(SUM(c.commission_amount), 0) as total_earned
     FROM users u
     LEFT JOIN referrals r ON r.referrer_id = u.id AND r.level = 1
     LEFT JOIN commissions c ON c.user_id = u.id
     WHERE u.is_active = 1
     GROUP BY u.id ORDER BY total_ref DESC, total_earned DESC LIMIT 10'
);

// Top Profit
$topProfit = db()->fetchAll(
    'SELECT u.id, u.username, u.full_name, u.vip_level, uw.total_profit
     FROM users u JOIN user_wallets uw ON uw.user_id = u.id
     WHERE u.is_active = 1 AND uw.total_profit > 0
     ORDER BY uw.total_profit DESC LIMIT 10'
);

// Current user rank
$userRankDeposit  = 0;
$userRankReferral = 0;
$userRankProfit   = 0;
foreach ($topDeposit as $i => $u) { if ((int)$u['id'] === $userId) { $userRankDeposit = $i+1; break; } }
foreach ($topReferral as $i => $u) { if ((int)$u['id'] === $userId) { $userRankReferral = $i+1; break; } }
foreach ($topProfit as $i => $u) { if ((int)$u['id'] === $userId) { $userRankProfit = $i+1; break; } }

if (!$userRankDeposit) {
    $r = db()->fetchOne(
        'SELECT COUNT(*)+1 as rank FROM user_wallets WHERE total_deposit > (SELECT total_deposit FROM user_wallets WHERE user_id = ?)',
        'i', [$userId]
    );
    $userRankDeposit = (int)($r['rank'] ?? 0);
}

$pageTitle = 'Leaderboard';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Leaderboard 🏆</h1>
  </div>

  <div class="tabs" id="lbTabs">
    <button class="tab-btn <?= $activeTab==='deposit'?'active':'' ?>" data-tab="lb-deposit">Top Deposit</button>
    <button class="tab-btn <?= $activeTab==='referral'?'active':'' ?>" data-tab="lb-referral">Top Referral</button>
    <button class="tab-btn <?= $activeTab==='profit'?'active':'' ?>" data-tab="lb-profit">Top Profit</button>
  </div>

  <?php
  $tabs = [
    ['deposit', 'lb-deposit', $topDeposit, 'total_deposit', 'total_deposit'],
    ['referral', 'lb-referral', $topReferral, 'total_ref', 'total_ref'],
    ['profit', 'lb-profit', $topProfit, 'total_profit', 'total_profit'],
  ];
  $valueLabels = ['deposit'=>'Total Deposit','referral'=>'Total Referral','profit'=>'Total Profit'];
  foreach ($tabs as [$tabKey, $tabId, $data, $valueKey, $sortKey]):
  ?>
  <div class="tab-content <?= $activeTab===$tabKey?'active':'' ?>" id="<?= $tabId ?>">
    <!-- Podium Top 3 -->
    <?php
    $top3 = array_slice($data, 0, 3);
    $podiumOrder = [1, 0, 2]; // 2nd, 1st, 3rd visual order
    ?>
    <?php if (!empty($top3)): ?>
    <div class="podium-wrap">
      <?php
      $podiumPositions = [];
      if (isset($top3[1])) $podiumPositions[] = ['data'=>$top3[1],'rank'=>2,'height'=>'140px'];
      if (isset($top3[0])) $podiumPositions[] = ['data'=>$top3[0],'rank'=>1,'height'=>'180px'];
      if (isset($top3[2])) $podiumPositions[] = ['data'=>$top3[2],'rank'=>3,'height'=>'110px'];
      foreach ($podiumPositions as $pos):
        $u = $pos['data'];
        $isMe = (int)$u['id'] === $userId;
        $rankEmoji = $pos['rank']===1?'🥇':($pos['rank']===2?'🥈':'🥉');
      ?>
      <div class="podium-item podium-rank-<?= $pos['rank'] ?> <?= $isMe?'podium-me':'' ?>">
        <div class="podium-avatar">
          <div class="avatar-circle"><?= strtoupper(substr(e($u['username']),0,1)) ?></div>
          <span class="podium-rank-emoji"><?= $rankEmoji ?></span>
        </div>
        <div class="podium-info">
          <span class="podium-name"><?= e(maskName($u['full_name'] ?? $u['username'])) ?></span>
          <span class="vip-badge-xs vip-<?= (int)$u['vip_level'] ?>">VIP<?= (int)$u['vip_level'] ?></span>
          <span class="podium-value cyan">
            <?= $tabKey==='referral'
              ? e($u['total_ref']) . ' orang'
              : e(formatRupiah((float)$u[$valueKey])) ?>
          </span>
        </div>
        <div class="podium-block" style="height:<?= $pos['height'] ?>"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Rank 4-10 Table -->
    <?php $rest = array_slice($data, 3); ?>
    <?php if (!empty($rest)): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th><?= e($valueLabels[$tabKey]) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rest as $i => $u):
            $rank  = $i + 4;
            $isMe  = (int)$u['id'] === $userId;
          ?>
          <tr class="<?= $isMe?'row-me':'' ?>">
            <td><strong><?= $rank ?></strong></td>
            <td>
              <div class="rank-user">
                <div class="avatar-circle-sm"><?= strtoupper(substr(e($u['username']),0,1)) ?></div>
                <span><?= e(maskName($u['full_name'] ?? $u['username'])) ?></span>
                <span class="vip-badge-xs vip-<?= (int)$u['vip_level'] ?>">VIP<?= (int)$u['vip_level'] ?></span>
                <?= $isMe ? '<span class="me-tag">Anda</span>' : '' ?>
              </div>
            </td>
            <td class="cyan">
              <?= $tabKey==='referral'
                ? e($u['total_ref']) . ' orang'
                : e(formatRupiah((float)$u[$valueKey])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- User's own rank if not in top 10 -->
    <?php
    $myRank = ['deposit'=>$userRankDeposit,'referral'=>$userRankReferral,'profit'=>$userRankProfit][$tabKey];
    $inTop10 = false;
    foreach ($data as $u) { if ((int)$u['id'] === $userId) { $inTop10=true; break; } }
    ?>
    <?php if (!$inTop10 && $myRank > 0): ?>
    <div class="my-rank-banner card">
      <span>Peringkat Anda:</span>
      <strong class="cyan">#<?= $myRank ?></strong>
      <span>Tingkatkan untuk masuk Top 10!</span>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>

</div>

<script>
document.querySelectorAll('.tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.tab).classList.add('active');
  });
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
