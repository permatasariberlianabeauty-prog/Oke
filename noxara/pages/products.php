<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mining.php';
require_once INCLUDES_PATH . '/vip.php';

requireLogin();

$userId = SessionManager::userId();
$user   = db()->fetchOne('SELECT vip_level FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$vip    = (int)($user['vip_level'] ?? 0);
$wallet = getUserWallet($userId);

if (isPost() && isset($_POST['buy_product'])) {
    CSRF::verify();
    $productId   = (int)postVal('product_id', 0);
    $voucherCode = clean(postVal('voucher_code', ''));
    if ($productId > 0) {
        $res = purchaseProduct($userId, $productId, $voucherCode ?: null);
        if ($res['success']) {
            setFlashPopup('success', 'Paket berhasil dibeli! Mulai klaim profit harian Anda.', 'Pembelian Berhasil 🎉');
        } else {
            setFlashPopup('error', $res['message'], 'Pembelian Gagal');
        }
    }
    redirect(BASE_URL . '/pages/products.php');
}

$categories = db()->fetchAll('SELECT * FROM product_categories WHERE is_active = 1 ORDER BY sort_order ASC');
$allProducts = db()->fetchAll(
    'SELECT p.*, pc.name as category_name, pc.slug as category_slug
     FROM products p JOIN product_categories pc ON pc.id = p.category_id
     WHERE p.is_active = 1 ORDER BY p.category_id ASC, p.price ASC'
);

// Group by category
$byCategory = [];
foreach ($allProducts as $prod) {
    $byCategory[$prod['category_id']][] = $prod;
}

$activeTab = clean(getVal('cat', (string)($categories[0]['id'] ?? '')));

$pageTitle = 'Paket Investasi';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Paket Investasi</h1>
  </div>

  <!-- Saldo Info -->
  <div class="saldo-row">
    <div class="saldo-mini-item">
      <span class="saldo-mini-label">Saldo Utama</span>
      <span class="cyan"><?= e(formatRupiah((float)$wallet['main_balance'])) ?></span>
    </div>
    <div class="saldo-mini-item">
      <span class="saldo-mini-label">Saldo Gratis</span>
      <span class="purple"><?= e(formatRupiah((float)$wallet['free_balance'])) ?></span>
    </div>
    <div class="saldo-mini-item">
      <span class="saldo-mini-label">VIP Anda</span>
      <span class="vip-badge vip-<?= $vip ?>">VIP <?= $vip ?></span>
    </div>
  </div>

  <!-- Tabs -->
  <?php if (!empty($categories)): ?>
  <div class="tabs" id="categoryTabs">
    <?php foreach ($categories as $cat): ?>
    <button class="tab-btn <?= $activeTab == $cat['id'] ? 'active' : '' ?>"
      data-tab="cat-<?= (int)$cat['id'] ?>">
      <?= e($cat['name']) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Products per category -->
  <?php foreach ($categories as $cat): ?>
  <div class="tab-content <?= $activeTab == $cat['id'] ? 'active' : '' ?>" id="cat-<?= (int)$cat['id'] ?>">
    <div class="products-grid">
      <?php
      $prods = $byCategory[$cat['id']] ?? [];
      if (empty($prods)):
      ?>
      <p class="text-muted text-center" style="grid-column:1/-1">Belum ada paket dalam kategori ini.</p>
      <?php else: ?>
      <?php foreach ($prods as $prod):
        $totalROI = round(((float)$prod['profit_per_day'] * (int)$prod['duration_days'] / (float)$prod['price']) * 100, 1);
        $canBuy   = $vip >= (int)$prod['min_vip_level'];
        $vipReq   = (int)$prod['min_vip_level'];
      ?>
      <div class="product-card card <?= !$canBuy ? 'product-locked' : '' ?>">
        <?php if (!empty($prod['image'])): ?>
        <div class="product-img-wrap">
          <img src="<?= uploadUrl($prod['image']) ?>" alt="<?= e($prod['name']) ?>" class="product-img" loading="lazy">
        </div>
        <?php endif; ?>
        <?php if ($vipReq > 0): ?>
        <span class="product-vip-badge">VIP <?= $vipReq ?>+</span>
        <?php endif; ?>
        <div class="product-body">
          <h3 class="product-name"><?= e($prod['name']) ?></h3>
          <?php if (!empty($prod['description'])): ?>
          <p class="product-desc"><?= e($prod['description']) ?></p>
          <?php endif; ?>
          <div class="product-stats">
            <div class="pstat">
              <span class="pstat-label">Harga</span>
              <span class="pstat-value cyan"><?= e(formatRupiah((float)$prod['price'])) ?></span>
            </div>
            <div class="pstat">
              <span class="pstat-label">Profit/Hari</span>
              <span class="pstat-value green">+<?= e(formatRupiah((float)$prod['profit_per_day'])) ?></span>
            </div>
            <div class="pstat">
              <span class="pstat-label">Durasi</span>
              <span class="pstat-value"><?= (int)$prod['duration_days'] ?> Hari</span>
            </div>
            <div class="pstat">
              <span class="pstat-label">Total ROI</span>
              <span class="pstat-value gold"><?= $totalROI ?>%</span>
            </div>
          </div>
          <?php if ($canBuy): ?>
          <button type="button" class="btn btn-primary btn-full"
            data-product-id="<?= (int)$prod['id'] ?>"
            data-product-name="<?= e($prod['name']) ?>"
            data-price="<?= e(formatRupiah((float)$prod['price'])) ?>"
            data-profit="<?= e(formatRupiah((float)$prod['profit_per_day'])) ?>"
            data-duration="<?= (int)$prod['duration_days'] ?>"
            onclick="openBuyModal(this)">
            Beli Sekarang
          </button>
          <?php else: ?>
          <a href="<?= BASE_URL ?>/pages/vip.php" class="btn btn-outline-purple btn-full">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/></svg>
            Butuh VIP <?= $vipReq ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<!-- Buy Confirm Modal -->
<div class="modal hidden" id="buyModal" role="dialog" aria-modal="true">
  <div class="modal-card">
    <div class="modal-header">
      <h2 class="modal-title" id="buyModalTitle">Konfirmasi Pembelian</h2>
      <button class="modal-close" onclick="closeBuyModal()" aria-label="Tutup">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="buy-summary" id="buySummary"></div>
      <div class="saldo-usage-info notice-banner notice-info">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#00D4FF" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
        Saldo gratis digunakan terlebih dahulu, kekurangan diambil dari saldo utama.
      </div>
      <form method="post" action="" id="buyForm" class="form mt-16">
        <?= CSRF::field() ?>
        <input type="hidden" name="product_id" id="buyProductId" value="">
        <div class="form-group">
          <label class="form-label" for="buy_voucher">Kode Voucher (Opsional)</label>
          <input type="text" id="buy_voucher" name="voucher_code" class="form-input"
            placeholder="Masukkan kode voucher" maxlength="50" autocomplete="off">
        </div>
        <button type="submit" name="buy_product" value="1" class="btn btn-primary btn-full btn-lg">
          Konfirmasi Beli
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.tab).classList.add('active');
  });
});

function openBuyModal(btn) {
  var name = btn.dataset.productName;
  var price = btn.dataset.price;
  var profit = btn.dataset.profit;
  var dur = btn.dataset.duration;
  document.getElementById('buyProductId').value = btn.dataset.productId;
  document.getElementById('buyModalTitle').textContent = 'Beli ' + name;
  document.getElementById('buySummary').innerHTML =
    '<div class="buy-row"><span>Paket</span><strong>' + name + '</strong></div>' +
    '<div class="buy-row"><span>Harga</span><strong class="cyan">' + price + '</strong></div>' +
    '<div class="buy-row"><span>Profit/Hari</span><strong class="green">' + profit + '</strong></div>' +
    '<div class="buy-row"><span>Durasi</span><strong>' + dur + ' Hari</strong></div>';
  document.getElementById('buyModal').classList.remove('hidden');
  document.getElementById('modal-overlay').classList.remove('hidden');
}

function closeBuyModal() {
  document.getElementById('buyModal').classList.add('hidden');
  document.getElementById('modal-overlay').classList.add('hidden');
}

document.getElementById('modal-overlay').addEventListener('click', closeBuyModal);
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
