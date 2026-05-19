<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/missions.php';

requireLogin();

$userId = SessionManager::userId();

if (isPost() && isset($_POST['claim_mission'])) {
    CSRF::verify();
    $umId = (int)postVal('user_mission_id', 0);
    if ($umId > 0) {
        $res = claimMissionReward($userId, $umId);
        if ($res['success']) {
            $rewardText = $res['reward_type'] === 'voucher'
                ? 'Voucher berhasil diterima!'
                : formatRupiah((float)$res['reward_value']) . ' berhasil diklaim!';
            setFlashPopup('success', 'Reward misi: ' . $rewardText, 'Misi Selesai 🏆');
        } else {
            setFlashPopup('error', $res['message'], 'Gagal');
        }
    }
    redirect(BASE_URL . '/pages/missions.php');
}

$activeTab = clean(getVal('tab', 'daily'));

// Ambil semua misi dengan progress user
function getMissionsWithProgress(int $userId, string $type): array {
    $missions = db()->fetchAll(
        'SELECT * FROM missions WHERE type = ? AND is_active = 1 ORDER BY sort_order ASC',
        's', [$type]
    );
    $period = getMissionPeriod($type);
    foreach ($missions as &$m) {
        $um = null;
        if ($type === 'milestone') {
            $um = db()->fetchOne(
                'SELECT * FROM user_missions WHERE user_id = ? AND mission_id = ? ORDER BY id DESC LIMIT 1',
                'ii', [$userId, $m['id']]
            );
        } else {
            $um = db()->fetchOne(
                'SELECT * FROM user_missions WHERE user_id = ? AND mission_id = ? AND period_start = ? ORDER BY id DESC LIMIT 1',
                'iis', [$userId, $m['id'], $period['start']]
            );
        }
        $m['user_mission'] = $um;
        $m['current_value'] = (int)($um['current_value'] ?? 0);
        $m['status']        = $um['status'] ?? 'not_started';
        $m['um_id']         = $um['id'] ?? 0;
    }
    return $missions;
}

$dailyMissions    = getMissionsWithProgress($userId, 'daily');
$weeklyMissions   = getMissionsWithProgress($userId, 'weekly');
$milestoneMissions= getMissionsWithProgress($userId, 'milestone');

$pageTitle = 'Misi';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Misi</h1>
  </div>

  <div class="tabs" id="missionTabs">
    <button class="tab-btn <?= $activeTab==='daily'?'active':'' ?>" data-tab="mission-daily">Harian</button>
    <button class="tab-btn <?= $activeTab==='weekly'?'active':'' ?>" data-tab="mission-weekly">Mingguan</button>
    <button class="tab-btn <?= $activeTab==='milestone'?'active':'' ?>" data-tab="mission-milestone">Milestone</button>
  </div>

  <?php
  $missionGroups = [
    'daily'     => [$dailyMissions,     'mission-daily',     $activeTab==='daily'],
    'weekly'    => [$weeklyMissions,    'mission-weekly',    $activeTab==='weekly'],
    'milestone' => [$milestoneMissions, 'mission-milestone', $activeTab==='milestone'],
  ];
  foreach ($missionGroups as $groupKey => [$missions, $tabId, $isActive]):
  ?>
  <div class="tab-content <?= $isActive?'active':'' ?>" id="<?= $tabId ?>">
    <?php if (empty($missions)): ?>
    <p class="text-muted text-center" style="padding:32px">Belum ada misi tersedia.</p>
    <?php else: ?>
    <div class="missions-list">
      <?php foreach ($missions as $m):
        $target  = (int)$m['target_value'];
        $current = (int)$m['current_value'];
        $progress = $target > 0 ? min(100, round($current / $target * 100)) : 0;
        $status   = $m['status'];
        $umId     = (int)$m['um_id'];
      ?>
      <div class="mission-card card <?= $status === 'claimed' ? 'mission-claimed' : '' ?>">
        <div class="mission-header">
          <div class="mission-icon">
            <?php if (!empty($m['icon'])): ?>
            <span class="mission-emoji"><?= e($m['icon']) ?></span>
            <?php else: ?>
            🎯
            <?php endif; ?>
          </div>
          <div class="mission-info">
            <h3 class="mission-title"><?= e($m['title']) ?></h3>
            <?php if (!empty($m['description'])): ?>
            <p class="mission-desc"><?= e($m['description']) ?></p>
            <?php endif; ?>
          </div>
          <div class="mission-reward">
            <?php if ($m['reward_type'] === 'voucher'): ?>
            <span class="reward-tag purple">🎟 Voucher</span>
            <?php else: ?>
            <span class="reward-tag cyan">+<?= e(formatRupiah((float)$m['reward_value'])) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="mission-progress">
          <div class="progress-bar">
            <div class="progress-fill <?= $status==='claimed'?'green':'cyan' ?>" style="width:<?= $progress ?>%"></div>
          </div>
          <div class="progress-meta">
            <span><?= $current ?> / <?= $target ?></span>
            <span><?= $progress ?>%</span>
          </div>
        </div>

        <?php if ($status === 'completed' && $umId > 0): ?>
        <form method="post" action="">
          <?= CSRF::field() ?>
          <input type="hidden" name="user_mission_id" value="<?= $umId ?>">
          <button type="submit" name="claim_mission" value="1"
            class="btn btn-primary btn-full" style="min-height:48px">
            🏆 Klaim Reward
          </button>
        </form>
        <?php elseif ($status === 'claimed'): ?>
        <div class="mission-done-badge">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#2ED573" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Sudah Diklaim
        </div>
        <?php else: ?>
        <div class="btn btn-ghost btn-full btn-disabled" style="min-height:48px">
          Selesaikan Misi
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
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
