<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/wallet.php';
require_once INCLUDES_PATH . '/vip.php';

requireLogin();

$userId = SessionManager::userId();
$wallet = getUserWallet($userId);
$user   = db()->fetchOne('SELECT full_name, vip_level, pin FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$vip    = (int)($user['vip_level'] ?? 0);
$hasPin = !empty($user['pin']);

$minWd  = getMinWithdraw($vip);
$fee    = getWithdrawFee($vip);
$openH  = (int)getSetting('withdraw_open_hour', '8');
$closeH = (int)getSetting('withdraw_close_hour', '20');
$wdEnabled = getSetting('withdraw_enabled', '1') === '1';

if (isPost() && isset($_POST['submit_withdraw'])) {
    CSRF::verify();
    if (!$hasPin) {
        setFlashPopup('error', 'Silakan atur PIN transaksi terlebih dahulu di halaman Keamanan.', 'PIN Belum Diatur');
        redirect(BASE_URL . '/pages/security.php');
    }
    $amount     = (float)str_replace(['.', ','], ['', '.'], postVal('amount', '0'));
    $bankAccId  = (int)postVal('bank_account_id', 0);
    $pin        = postVal('pin', '');

    $res = submitWithdraw($userId, $amount, $bankAccId, $pin);
    if ($res['success']) {
        setFlashPopup('success',
            'Penarikan ' . formatRupiah($amount) . ' berhasil diajukan. Diterima: ' . formatRupiah($res['net_amount']) . ' (fee: ' . formatRupiah($res['fee']) . ')',
            'Withdraw Diajukan');
    } else {
        setFlashPopup('error', $res['message'], 'Gagal');
    }
    redirect(BASE_URL . '/pages/withdraw.php');
}

$bankAccounts = db()->fetchAll(
    'SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at ASC',
    'i', [$userId]
);

$pendingWd = db()->fetchAll(
    'SELECT w.*, ba.bank_name, ba.account_number FROM withdrawals w
     LEFT JOIN bank_accounts ba ON ba.id = w.bank_account_id
     WHERE w.user_id = ? ORDER BY w.created_at DESC LIMIT 20',
    'i', [$userId]
);

$pageTitle = 'Withdraw';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Tarik Dana</h1>
  </div>

  <!-- VIP Info -->
  <div class="vip-info-card card">
    <div class="vip-info-row">
      <div class="vip-info-item">
        <span class="vip-info-label">Level VIP</span>
        <span class="vip-badge vip-<?= $vip ?>">VIP <?= $vip ?></span>
      </div>
      <div class="vip-info-item">
        <span class="vip-info-label">Saldo Utama</span>
        <span class="vip-info-value cyan"><?= e(formatRupiah((float)$wallet['main_balance'])) ?></span>
      </div>
    </div>
    <div class="vip-info-row">
      <div class="vip-info-item">
        <span class="vip-info-label">Min. Withdraw</span>
        <span class="vip-info-value"><?= e(formatRupiah($minWd)) ?></span>
      </div>
      <div class="vip-info-item">
        <span class="vip-info-label">Biaya Admin</span>
        <span class="vip-info-value"><?= e($fee) ?>%</span>
      </div>
      <div class="vip-info-item">
        <span class="vip-info-label">Jam Layanan</span>
        <span class="vip-info-value"><?= $openH ?>:00 – <?= $closeH ?>:00</span>
      </div>
    </div>
  </div>

  <!-- Warning jika belum set PIN -->
  <?php if (!$hasPin): ?>
  <div class="notice-banner notice-warning">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="#FFB800" stroke-width="2" stroke-linecap="round"/></svg>
    <span>PIN transaksi belum diatur. <a href="<?= BASE_URL ?>/pages/security.php" class="link-cyan">Atur PIN sekarang</a> untuk bisa melakukan withdraw.</span>
  </div>
  <?php endif; ?>

  <?php if (!$wdEnabled): ?>
  <div class="notice-banner notice-error">Layanan withdraw sedang ditutup sementara oleh admin.</div>
  <?php endif; ?>

  <!-- Form Withdraw -->
  <div class="card">
    <h2 class="card-title">Form Penarikan</h2>
    <form method="post" action="" id="withdrawForm" class="form">
      <?= CSRF::field() ?>

      <!-- Nominal -->
      <div class="form-group">
        <label class="form-label" for="wd_amount">Nominal Penarikan <span class="required">*</span></label>
        <div class="input-prefix">
          <span class="input-prefix-text">Rp</span>
          <input type="text" id="wd_amount" name="amount" class="form-input input-with-prefix"
            placeholder="Contoh: 200.000" required inputmode="numeric" autocomplete="off">
        </div>
        <div class="calc-row" id="calcRow" style="display:none">
          <div class="calc-item"><span>Fee (<?= $fee ?>%):</span> <span id="calcFee" class="text-red">—</span></div>
          <div class="calc-item"><span>Diterima:</span> <span id="calcNet" class="cyan">—</span></div>
        </div>
        <p class="form-hint">Minimal: <?= e(formatRupiah($minWd)) ?></p>
      </div>

      <!-- Rekening Bank -->
      <div class="form-group">
        <label class="form-label">Rekening Tujuan <span class="required">*</span></label>
        <?php if (empty($bankAccounts)): ?>
        <div class="notice-banner notice-info">
          Belum ada rekening bank terdaftar.
          <a href="<?= BASE_URL ?>/pages/bank_account.php" class="link-cyan">Tambah Rekening</a>
        </div>
        <?php else: ?>
        <select name="bank_account_id" id="bank_account_id" class="form-input form-select" required>
          <option value="">— Pilih Rekening —</option>
          <?php foreach ($bankAccounts as $ba): ?>
          <option value="<?= (int)$ba['id'] ?>" <?= $ba['is_primary'] ? 'selected' : '' ?>>
            <?= e($ba['bank_name']) ?> — <?= e($ba['account_number']) ?> (<?= e($ba['account_name']) ?>)
            <?= $ba['is_primary'] ? '[Utama]' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/pages/bank_account.php" class="form-hint link-cyan">Kelola Rekening Bank</a>
      </div>

      <!-- PIN -->
      <div class="form-group">
        <label class="form-label" for="withdraw_pin">PIN Transaksi <span class="required">*</span></label>
        <div class="pin-input-wrap">
          <input type="password" id="withdraw_pin" name="pin" class="form-input pin-input"
            placeholder="••••••" maxlength="6" minlength="6" pattern="\d{6}" inputmode="numeric"
            autocomplete="off" required <?= !$hasPin ? 'disabled' : '' ?>>
          <button type="button" class="pin-toggle" data-target="withdraw_pin" aria-label="Tampilkan PIN">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" name="submit_withdraw" value="1"
        class="btn btn-primary btn-full btn-lg"
        <?= (!$hasPin || !$wdEnabled || empty($bankAccounts)) ? 'disabled' : '' ?>>
        Ajukan Withdraw
      </button>
    </form>
  </div>

  <!-- Riwayat Withdraw -->
  <?php if (!empty($pendingWd)): ?>
  <div class="section">
    <h2 class="section-title">Riwayat Withdraw</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nominal</th>
            <th>Diterima</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingWd as $wd): ?>
          <tr>
            <td><?= e(formatDate($wd['created_at'], 'd M Y')) ?></td>
            <td class="text-red">–<?= e(formatRupiah((float)$wd['amount'])) ?></td>
            <td class="cyan"><?= e(formatRupiah((float)$wd['net_amount'])) ?></td>
            <td><span class="status-badge status-<?= e($wd['status']) ?>"><?= e(ucfirst($wd['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
var feePercent = <?= (float)$fee ?>;

document.getElementById('wd_amount').addEventListener('input', function(){
  var raw = this.value.replace(/\D/g,'');
  this.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
  var amount = parseInt(raw) || 0;
  var calcRow = document.getElementById('calcRow');
  if (amount > 0) {
    var fee = Math.round(amount * feePercent / 100);
    var net = amount - fee;
    document.getElementById('calcFee').textContent = '–Rp ' + fee.toLocaleString('id-ID');
    document.getElementById('calcNet').textContent = 'Rp ' + net.toLocaleString('id-ID');
    calcRow.style.display = 'flex';
  } else {
    calcRow.style.display = 'none';
  }
});

// PIN toggle
document.querySelectorAll('.pin-toggle').forEach(function(btn){
  btn.addEventListener('click', function(){
    var inp = document.getElementById(this.dataset.target);
    inp.type = inp.type === 'password' ? 'text' : 'password';
  });
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
