<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$activeTab = clean(getVal('tab', 'tentang-kami'));
$validTabs = ['tentang-kami','syarat-ketentuan','kebijakan-privasi','kebijakan-withdraw'];
if (!in_array($activeTab, $validTabs, true)) $activeTab = 'tentang-kami';

// Ambil konten halaman legal
$legalPages = [];
foreach ($validTabs as $slug) {
    $page = db()->fetchOne('SELECT * FROM legal_pages WHERE slug = ? AND is_active = 1 LIMIT 1', 's', [$slug]);
    $legalPages[$slug] = $page;
}

// Platform stats
$platformInfo = [];
$statsRows = db()->fetchAll('SELECT * FROM platform_info WHERE is_active = 1');
foreach ($statsRows as $row) {
    $platformInfo[$row['key']] = $row['value'];
}

// Fallback stats dari DB
if (empty($platformInfo)) {
    $totalMembers = db()->fetchOne('SELECT COUNT(*) as cnt FROM users WHERE is_active = 1');
    $totalPayout  = db()->fetchOne('SELECT COALESCE(SUM(amount),0) as total FROM withdrawals WHERE status="approved"');
    $platformInfo = [
        'total_members'  => number_format((int)($totalMembers['cnt'] ?? 0)),
        'total_payout'   => formatRupiah((float)($totalPayout['total'] ?? 0)),
        'platform_rating'=> '4.9',
        'since_year'     => '2024',
    ];
}

$tabTitles = [
    'tentang-kami'      => 'Tentang Kami',
    'syarat-ketentuan'  => 'Syarat & Ketentuan',
    'kebijakan-privasi' => 'Kebijakan Privasi',
    'kebijakan-withdraw'=> 'Kebijakan Withdraw',
];

$pageTitle = $tabTitles[$activeTab] ?? 'Informasi';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Informasi</h1>
  </div>

  <!-- Tabs -->
  <div class="tabs tabs-scroll" id="infoTabs">
    <?php foreach ($tabTitles as $slug => $title): ?>
    <button class="tab-btn <?= $activeTab===$slug?'active':'' ?>"
      data-tab="info-<?= e(str_replace('-','_',$slug)) ?>"
      onclick="switchInfoTab('<?= e($slug) ?>')">
      <?= e($title) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- About Us Tab -->
  <div class="tab-content <?= $activeTab==='tentang-kami'?'active':'' ?>" id="info-tentang_kami">
    <!-- Platform Stats -->
    <div class="platform-stats">
      <div class="pstat-item">
        <span class="pstat-icon">👥</span>
        <span class="pstat-val orbitron cyan"><?= e($platformInfo['total_members'] ?? '0') ?>+</span>
        <span class="pstat-label">Anggota Aktif</span>
      </div>
      <div class="pstat-item">
        <span class="pstat-icon">💰</span>
        <span class="pstat-val orbitron cyan"><?= e($platformInfo['total_payout'] ?? 'Rp 0') ?></span>
        <span class="pstat-label">Total Payout</span>
      </div>
      <div class="pstat-item">
        <span class="pstat-icon">⭐</span>
        <span class="pstat-val orbitron cyan"><?= e($platformInfo['platform_rating'] ?? '4.9') ?>/5</span>
        <span class="pstat-label">Rating Platform</span>
      </div>
      <div class="pstat-item">
        <span class="pstat-icon">📅</span>
        <span class="pstat-val orbitron cyan">Sejak <?= e($platformInfo['since_year'] ?? '2024') ?></span>
        <span class="pstat-label">Beroperasi</span>
      </div>
    </div>

    <?php if (!empty($legalPages['tentang-kami']['content'])): ?>
    <div class="legal-content card">
      <?= $legalPages['tentang-kami']['content'] ?>
    </div>
    <?php else: ?>
    <div class="card legal-content">
      <h2>Tentang NOXARA</h2>
      <p>NOXARA adalah platform investasi mining digital terpercaya yang berkomitmen memberikan pengalaman investasi terbaik bagi seluruh anggota. Kami menggabungkan teknologi modern dengan sistem keamanan berlapis untuk melindungi investasi Anda.</p>
      <h3>Visi Kami</h3>
      <p>Menjadi platform investasi digital terdepan di Indonesia yang memberikan akses mudah, aman, dan menguntungkan bagi semua kalangan.</p>
      <h3>Misi Kami</h3>
      <ul>
        <li>Menyediakan paket investasi dengan ROI kompetitif dan transparan</li>
        <li>Membangun komunitas investor yang solid dan saling mendukung</li>
        <li>Memberikan layanan customer service terbaik 7 hari seminggu</li>
        <li>Terus berinovasi dalam teknologi finansial digital</li>
      </ul>
      <h3>Keunggulan NOXARA</h3>
      <ul>
        <li>✅ Profit harian yang dapat diklaim setiap hari</li>
        <li>✅ Komisi referral hingga 3 level</li>
        <li>✅ Sistem keamanan berlapis dengan PIN transaksi</li>
        <li>✅ Proses deposit & withdraw yang cepat</li>
        <li>✅ Support 24/7 melalui Live Chat dan WhatsApp</li>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <?php foreach (['syarat-ketentuan','kebijakan-privasi','kebijakan-withdraw'] as $slug): ?>
  <div class="tab-content <?= $activeTab===$slug?'active':'' ?>" id="info-<?= e(str_replace('-','_',$slug)) ?>">
    <?php if (!empty($legalPages[$slug]['content'])): ?>
    <div class="legal-content card">
      <div class="legal-meta">
        <span class="legal-updated">
          Terakhir diperbarui: <?= e(formatDate($legalPages[$slug]['updated_at'] ?? $legalPages[$slug]['created_at'] ?? date('Y-m-d'))) ?>
        </span>
      </div>
      <?= $legalPages[$slug]['content'] ?>
    </div>
    <?php else: ?>
    <div class="card legal-content">
      <div class="empty-legal">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" stroke="#7B2FFF" stroke-width="1.5"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round"/></svg>
        <p><?= e($tabTitles[$slug]) ?> belum tersedia. Silakan hubungi admin.</p>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

</div>

<script>
function switchInfoTab(slug) {
  document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
  document.querySelectorAll('.tab-content').forEach(function(c){ c.classList.remove('active'); });
  var tabId = 'info-' + slug.replace(/-/g,'_');
  var content = document.getElementById(tabId);
  if (content) content.classList.add('active');
  event.target.classList.add('active');
  history.pushState(null,'','?tab='+slug);
}

// Tabs from URL
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
      this.classList.add('active');
      var tabId = this.dataset.tab;
      document.getElementById(tabId).classList.add('active');
    });
  });
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
