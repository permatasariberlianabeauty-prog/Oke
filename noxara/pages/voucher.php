<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId = SessionManager::userId();
$result = null;

if (isPost() && isset($_POST['redeem_voucher'])) {
    CSRF::verify();
    $code = strtoupper(clean(postVal('voucher_code', '')));
    if (empty($code)) {
        setFlashPopup('error', 'Masukkan kode voucher.', 'Gagal');
    } else {
        $voucher = db()->fetchOne(
            'SELECT * FROM vouchers WHERE code = ? AND is_active = 1
             AND (expired_at IS NULL OR expired_at > NOW())
             AND (usage_limit = 0 OR used_count < usage_limit) LIMIT 1',
            's', [$code]
        );
        if (!$voucher) {
            setFlashPopup('error', 'Voucher tidak valid, kadaluarsa, atau sudah habis.', 'Gagal');
        } else {
            // Cek sudah punya
            $exists = db()->fetchOne(
                'SELECT id FROM user_vouchers WHERE user_id = ? AND voucher_id = ? LIMIT 1',
                'ii', [$userId, $voucher['id']]
            );
            if ($exists) {
                setFlashPopup('info', 'Voucher ini sudah ada di koleksi Anda.', 'Info');
            } else {
                db()->execute('INSERT INTO user_vouchers (user_id, voucher_id) VALUES (?,?)', 'ii', [$userId, $voucher['id']]);
                db()->execute('UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?', 'i', [$voucher['id']]);
                setFlashPopup('success', 'Voucher "' . $voucher['name'] . '" berhasil ditambahkan!', 'Voucher Diterima 🎟');
            }
        }
    }
    redirect(BASE_URL . '/pages/voucher.php');
}

// Ambil voucher user
$userVouchers = db()->fetchAll(
    'SELECT uv.*, v.name, v.code, v.type, v.discount_type, v.discount_value,
     v.max_discount, v.min_amount, v.expired_at, v.description
     FROM user_vouchers uv JOIN vouchers v ON v.id = uv.voucher_id
     WHERE uv.user_id = ? ORDER BY uv.created_at DESC',
    'i', [$userId]
);

$typeLabels = [
    'deposit' => 'Deposit', 'product' => 'Pembelian Paket',
    'withdraw'=> 'Withdraw', 'general' => 'Umum',
];

$pageTitle = 'Voucher';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Voucher</h1>
  </div>

  <!-- Redeem Form -->
  <div class="card">
    <h2 class="card-title">Masukkan Kode Voucher</h2>
    <form method="post" action="" class="form">
      <?= CSRF::field() ?>
      <div class="form-group">
        <div class="input-row">
          <input type="text" name="voucher_code" class="form-input"
            placeholder="Contoh: NOXARA2024" maxlength="50"
            autocomplete="off" style="text-transform:uppercase" required>
          <button type="submit" name="redeem_voucher" value="1" class="btn btn-primary">
            Klaim
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- My Vouchers -->
  <?php if (empty($userVouchers)): ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><line x1="7" y1="7" x2="7.01" y2="7" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg>
    <p>Belum ada voucher.</p>
    <p class="text-muted">Ikuti promo untuk mendapatkan voucher!</p>
  </div>
  <?php else: ?>
  <div class="section">
    <h2 class="section-title">Voucher Saya (<?= count($userVouchers) ?>)</h2>
    <div class="vouchers-grid">
      <?php foreach ($userVouchers as $uv):
        $isExpired = !empty($uv['expired_at']) && strtotime($uv['expired_at']) < time();
        $discountText = $uv['discount_type'] === 'percent'
          ? $uv['discount_value'] . '% off'
          : 'Diskon ' . formatRupiah((float)$uv['discount_value']);
      ?>
      <div class="voucher-card card <?= $isExpired?'voucher-expired':'' ?>">
        <div class="voucher-top">
          <div class="voucher-icon">🎟</div>
          <div class="voucher-info">
            <h3 class="voucher-name"><?= e($uv['name']) ?></h3>
            <span class="voucher-code orbitron cyan"><?= e($uv['code']) ?></span>
          </div>
          <div class="voucher-discount">
            <span class="discount-val"><?= e($discountText) ?></span>
          </div>
        </div>
        <div class="voucher-divider"></div>
        <div class="voucher-bottom">
          <div class="voucher-meta">
            <span class="voucher-type">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" stroke="currentColor" stroke-width="2"/></svg>
              <?= e($typeLabels[$uv['type']] ?? ucfirst($uv['type'])) ?>
            </span>
            <?php if (!empty($uv['min_amount']) && (float)$uv['min_amount'] > 0): ?>
            <span class="voucher-min">Min. <?= e(formatRupiah((float)$uv['min_amount'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($uv['max_discount']) && (float)$uv['max_discount'] > 0 && $uv['discount_type']==='percent'): ?>
            <span class="voucher-max">Maks. <?= e(formatRupiah((float)$uv['max_discount'])) ?></span>
            <?php endif; ?>
          </div>
          <div class="voucher-expiry <?= $isExpired?'text-red':'' ?>">
            <?php if (!empty($uv['expired_at'])): ?>
            <?= $isExpired ? '⚠ Kadaluarsa: ' : '⏱ Berlaku s/d: ' ?><?= e(formatDate($uv['expired_at'], 'd M Y')) ?>
            <?php else: ?>
            ✅ Tanpa batas waktu
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($uv['description'])): ?>
        <p class="voucher-desc text-muted"><?= e($uv['description']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
