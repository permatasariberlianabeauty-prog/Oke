<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/referral.php';

requireLogin();

$userId = SessionManager::userId();
$user   = db()->fetchOne('SELECT referral_code FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$refCode = $user['referral_code'] ?? '';
$refLink = BASE_URL . '/auth/register.php?ref=' . $refCode;

$stats = getReferralStats($userId);

// Commission rates
$commissions = db()->fetchAll(
    'SELECT * FROM commission_settings WHERE is_active = 1 ORDER BY level ASC'
);

// Downline table
$downlines = db()->fetchAll(
    'SELECT u.username, u.full_name, u.vip_level, u.created_at, u.is_active, r.level
     FROM referrals r JOIN users u ON u.id = r.referred_id
     WHERE r.referrer_id = ? ORDER BY r.level ASC, u.created_at DESC LIMIT 50',
    'i', [$userId]
);

// Referral tree (depth 3)
$tree = getReferralTree($userId, 3);

$pageTitle = 'Program Referral';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Program Referral</h1>
  </div>

  <!-- Referral Link -->
  <div class="card referral-link-card">
    <h2 class="card-title">Link Referral Anda</h2>
    <div class="ref-link-wrap">
      <input type="text" class="form-input ref-link-input" id="refLinkInput"
        value="<?= e($refLink) ?>" readonly aria-label="Link referral">
      <button type="button" class="btn btn-primary" id="copyRefLink" onclick="copyRefLink()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2"/></svg>
        Salin
      </button>
    </div>
    <div class="ref-code-row">
      <span class="ref-code-label">Kode:</span>
      <span class="ref-code orbitron"><?= e($refCode) ?></span>
      <button type="button" class="btn-icon" onclick="copyCode('<?= e($refCode) ?>')" aria-label="Salin kode">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="13" height="13" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="#00D4FF" stroke-width="2"/></svg>
      </button>
    </div>
    <div class="share-buttons">
      <a href="https://wa.me/?text=<?= urlencode('Bergabung dengan NOXARA, investasi cerdas! Daftar pakai link saya: ' . $refLink) ?>"
        target="_blank" rel="noopener" class="btn btn-sm btn-wa">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        WhatsApp
      </a>
      <a href="https://t.me/share/url?url=<?= urlencode($refLink) ?>&text=<?= urlencode('Bergabung NOXARA - Investasi Cerdas!') ?>"
        target="_blank" rel="noopener" class="btn btn-sm btn-tg">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
        Telegram
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-label">Total Downline</span>
      <span class="stat-value cyan"><?= e($stats['total_referral']) ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Aktif</span>
      <span class="stat-value green"><?= e($stats['active_referral']) ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Total Komisi</span>
      <span class="stat-value gold"><?= e(formatRupiah($stats['total_earned'])) ?></span>
    </div>
  </div>

  <!-- Commission Rates -->
  <?php if (!empty($commissions)): ?>
  <div class="card">
    <h2 class="card-title">Struktur Komisi</h2>
    <div class="commission-table">
      <?php
      $types = [];
      foreach ($commissions as $c) $types[$c['type']][] = $c;
      foreach ($types as $type => $levels):
      ?>
      <div class="commission-section">
        <h3 class="commission-type"><?= e(ucfirst(str_replace('_',' ',$type))) ?></h3>
        <div class="commission-levels">
          <?php foreach ($levels as $lv): ?>
          <div class="commission-level">
            <span class="level-badge level-<?= (int)$lv['level'] ?>">Level <?= (int)$lv['level'] ?></span>
            <span class="level-percent cyan"><?= e($lv['percent']) ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Downline Table -->
  <?php if (!empty($downlines)): ?>
  <div class="section">
    <h2 class="section-title">Daftar Downline</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Username</th><th>VIP</th><th>Level</th><th>Status</th><th>Bergabung</th></tr>
        </thead>
        <tbody>
          <?php foreach ($downlines as $dl): ?>
          <tr>
            <td><?= e($dl['username']) ?></td>
            <td><span class="vip-badge-xs vip-<?= (int)$dl['vip_level'] ?>">VIP<?= (int)$dl['vip_level'] ?></span></td>
            <td><span class="level-badge level-<?= (int)$dl['level'] ?>">L<?= (int)$dl['level'] ?></span></td>
            <td>
              <span class="status-dot <?= $dl['is_active'] ? 'active' : 'inactive' ?>">
                <?= $dl['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td><?= e(formatDate($dl['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Referral Tree -->
  <?php if (!empty($tree)): ?>
  <div class="card">
    <h2 class="card-title">Pohon Referral</h2>
    <div class="ref-tree">
      <?php
      $byParent = [];
      foreach ($tree as $node) {
          $byParent[$node['parent']][] = $node;
      }
      function renderTree(int $parentId, array &$byParent, int $depth = 0): void {
          if (!isset($byParent[$parentId])) return;
          echo '<ul class="tree-list depth-' . $depth . '">';
          foreach ($byParent[$parentId] as $node) {
              echo '<li class="tree-item">';
              echo '<div class="tree-node">';
              echo '<span class="tree-avatar">' . strtoupper(substr(e($node['username']),0,1)) . '</span>';
              echo '<span class="tree-username">' . e($node['username']) . '</span>';
              echo '<span class="vip-badge-xs vip-' . (int)$node['vip_level'] . '">VIP' . (int)$node['vip_level'] . '</span>';
              echo '<span class="status-dot ' . ($node['is_active']?'active':'inactive') . '"></span>';
              echo '</div>';
              renderTree((int)$node['id'], $byParent, $depth + 1);
              echo '</li>';
          }
          echo '</ul>';
      }
      renderTree($userId, $byParent);
      ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function copyRefLink() {
  var input = document.getElementById('refLinkInput');
  input.select();
  document.execCommand('copy');
  var btn = document.getElementById('copyRefLink');
  btn.textContent = 'Tersalin!';
  setTimeout(function(){ btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2"/></svg>Salin'; }, 2000);
}
function copyCode(code) {
  navigator.clipboard.writeText(code).then(function(){ if(window.showToast) showToast({type:'success',message:'Kode berhasil disalin!'}); });
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
