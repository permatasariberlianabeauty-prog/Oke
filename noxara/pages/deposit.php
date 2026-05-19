<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/wallet.php';
require_once INCLUDES_PATH . '/upload.php';

requireLogin();

$userId  = SessionManager::userId();
$wallet  = getUserWallet($userId);
$result  = null;

// Hitung unique code preview (random 3 digit)
$previewUniqueCode = 0;

if (isPost() && isset($_POST['submit_deposit'])) {
    CSRF::verify();
    $amount      = (float)str_replace(['.', ','], ['', '.'], postVal('amount', '0'));
    $adminBankId = (int)postVal('admin_bank_id', 0);
    $note        = clean(postVal('note', ''));
    $voucherCode = clean(postVal('voucher_code', ''));

    // Upload bukti
    $proofImage = null;
    if (!empty($_FILES['proof_image']['name'])) {
        $up = handleUpload($_FILES['proof_image'], 'deposit');
        if (!$up['success']) {
            setFlashPopup('error', $up['message'], 'Upload Gagal');
            redirect(BASE_URL . '/pages/deposit.php');
        }
        $proofImage = $up['path'];
    }

    // Voucher ID
    $voucherId = null;
    if (!empty($voucherCode)) {
        $v = db()->fetchOne('SELECT id FROM vouchers WHERE code = ? AND is_active = 1 AND type = "deposit" LIMIT 1', 's', [$voucherCode]);
        $voucherId = $v ? (int)$v['id'] : null;
    }

    $res = submitDeposit($userId, $amount, $adminBankId, $proofImage, $voucherId, $note);
    if ($res['success']) {
        setFlashPopup('success',
            'Deposit berhasil dikirim. Kode unik: <strong>' . $res['unique_code'] . '</strong>. Transfer tepat sebesar ' . formatRupiah($res['total_amount']),
            'Deposit Terkirim');
    } else {
        setFlashPopup('error', $res['message'], 'Gagal');
    }
    redirect(BASE_URL . '/pages/deposit.php');
}

// Data bank admin
$adminBanks = db()->fetchAll('SELECT * FROM admin_bank_accounts WHERE is_active = 1 ORDER BY sort_order ASC');

// Riwayat deposit pending
$pendingDeposits = db()->fetchAll(
    'SELECT d.*, ab.bank_name, ab.account_number, ab.account_name as bank_acc_name
     FROM deposits d JOIN admin_bank_accounts ab ON ab.id = d.admin_bank_id
     WHERE d.user_id = ? AND d.status = "pending" ORDER BY d.created_at DESC LIMIT 5',
    'i', [$userId]
);

// Semua riwayat deposit
$allDeposits = db()->fetchAll(
    'SELECT d.*, ab.bank_name FROM deposits d
     LEFT JOIN admin_bank_accounts ab ON ab.id = d.admin_bank_id
     WHERE d.user_id = ? ORDER BY d.created_at DESC LIMIT 20',
    'i', [$userId]
);

$minDeposit = (float)getSetting('deposit_min', '50000');
$maxDeposit = (float)getSetting('deposit_max', '100000000');

$pageTitle = 'Deposit';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Deposit</h1>
  </div>

  <!-- Saldo -->
  <div class="card saldo-mini">
    <span class="saldo-label">Saldo Utama</span>
    <span class="saldo-value cyan"><?= e(formatRupiah((float)$wallet['main_balance'])) ?></span>
  </div>

  <!-- Pending -->
  <?php if (!empty($pendingDeposits)): ?>
  <div class="notice-banner notice-warning">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="#FFB800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Anda memiliki <?= count($pendingDeposits) ?> deposit yang sedang menunggu konfirmasi.
  </div>
  <?php endif; ?>

  <!-- Form Deposit -->
  <div class="card">
    <h2 class="card-title">Form Deposit</h2>
    <form method="post" action="" enctype="multipart/form-data" id="depositForm" class="form">
      <?= CSRF::field() ?>

      <!-- Nominal -->
      <div class="form-group">
        <label class="form-label" for="amount">Nominal Deposit <span class="required">*</span></label>
        <div class="input-prefix">
          <span class="input-prefix-text">Rp</span>
          <input type="text" id="amount" name="amount" class="form-input input-with-prefix"
            placeholder="Contoh: 100.000" required
            inputmode="numeric" autocomplete="off"
            data-min="<?= $minDeposit ?>" data-max="<?= $maxDeposit ?>">
        </div>
        <p class="form-hint">Min: <?= e(formatRupiah($minDeposit)) ?> — Maks: <?= e(formatRupiah($maxDeposit)) ?></p>
      </div>

      <!-- Kode Unik Preview -->
      <div class="unique-code-preview card-inner" id="uniqueCodePreview" style="display:none">
        <div class="unique-row">
          <span>Nominal Transfer:</span>
          <span class="unique-amount cyan" id="uniqueTotal">—</span>
        </div>
        <p class="form-hint">Nominal sudah termasuk kode unik untuk verifikasi otomatis.</p>
      </div>

      <!-- Bank Admin -->
      <div class="form-group">
        <label class="form-label">Tujuan Transfer <span class="required">*</span></label>
        <?php if (!empty($adminBanks)): ?>
        <div class="bank-list" id="bankList">
          <?php foreach ($adminBanks as $bank): ?>
          <label class="bank-option" for="bank_<?= (int)$bank['id'] ?>">
            <input type="radio" id="bank_<?= (int)$bank['id'] ?>" name="admin_bank_id"
              value="<?= (int)$bank['id'] ?>" required class="bank-radio">
            <div class="bank-card">
              <div class="bank-logo-text"><?= e($bank['bank_name']) ?></div>
              <div class="bank-detail">
                <span class="bank-no"><?= e($bank['account_number']) ?></span>
                <span class="bank-holder">a/n <?= e($bank['account_name']) ?></span>
              </div>
              <?php if (!empty($bank['logo'])): ?>
              <img src="<?= uploadUrl($bank['logo']) ?>" alt="<?= e($bank['bank_name']) ?>" class="bank-logo">
              <?php endif; ?>
              <div class="bank-check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">Belum ada rekening bank tersedia.</p>
        <?php endif; ?>
      </div>

      <!-- Upload Bukti -->
      <div class="form-group">
        <label class="form-label" for="proof_image">Bukti Transfer</label>
        <div class="upload-area" id="uploadArea">
          <input type="file" id="proof_image" name="proof_image" accept=".jpg,.jpeg,.png,.webp,.pdf"
            class="upload-input" aria-label="Upload bukti transfer">
          <div class="upload-placeholder" id="uploadPlaceholder">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="#00D4FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <p>Klik atau seret file ke sini</p>
            <small>JPG, PNG, WEBP, PDF — maks 5MB</small>
          </div>
          <img id="uploadPreview" class="upload-preview hidden" alt="Preview">
        </div>
      </div>

      <!-- Kode Voucher -->
      <div class="form-group">
        <label class="form-label" for="voucher_code">Kode Voucher (Opsional)</label>
        <div class="input-row">
          <input type="text" id="voucher_code" name="voucher_code" class="form-input"
            placeholder="Masukkan kode voucher" maxlength="50" autocomplete="off">
          <button type="button" class="btn btn-sm btn-outline-cyan" id="checkVoucher">Cek</button>
        </div>
        <p class="form-hint" id="voucherMsg"></p>
      </div>

      <!-- Catatan -->
      <div class="form-group">
        <label class="form-label" for="note">Catatan (Opsional)</label>
        <textarea id="note" name="note" class="form-input form-textarea" rows="2"
          placeholder="Misal: Transfer dari BCA ke BNI" maxlength="255"></textarea>
      </div>

      <button type="submit" name="submit_deposit" value="1"
        class="btn btn-primary btn-full btn-lg" id="submitDepositBtn">
        Kirim Deposit
      </button>
    </form>
  </div>

  <!-- Riwayat Deposit -->
  <?php if (!empty($allDeposits)): ?>
  <div class="section">
    <h2 class="section-title">Riwayat Deposit</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nominal</th>
            <th>Bank</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allDeposits as $dep): ?>
          <tr>
            <td><?= e(formatDate($dep['created_at'], 'd M Y H:i')) ?></td>
            <td class="cyan"><?= e(formatRupiah((float)$dep['amount'])) ?></td>
            <td><?= e($dep['bank_name'] ?? '-') ?></td>
            <td><span class="status-badge status-<?= e($dep['status']) ?>"><?= e(ucfirst($dep['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <a href="<?= BASE_URL ?>/pages/history.php?type=deposit" class="btn btn-ghost btn-sm btn-full mt-8">
      Lihat Semua Riwayat
    </a>
  </div>
  <?php endif; ?>

</div>

<script>
// Format angka
document.getElementById('amount').addEventListener('input', function(){
  var raw = this.value.replace(/\D/g,'');
  this.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
  updateUniquePreview();
});

function updateUniquePreview(){
  var raw = document.getElementById('amount').value.replace(/\./g,'').replace(',','.');
  var amount = parseFloat(raw) || 0;
  var preview = document.getElementById('uniqueCodePreview');
  var totalEl = document.getElementById('uniqueTotal');
  if (amount >= 10000) {
    // Show approximate (real unique code generated server-side)
    var fakeUnique = Math.floor(Math.random()*900)+100;
    totalEl.textContent = 'Rp ' + (amount + fakeUnique).toLocaleString('id-ID') + ' (perkiraan)';
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
}

// File preview
document.getElementById('proof_image').addEventListener('change', function(){
  var preview = document.getElementById('uploadPreview');
  var placeholder = document.getElementById('uploadPlaceholder');
  if (this.files && this.files[0]) {
    if (this.files[0].type.startsWith('image/')) {
      var reader = new FileReader();
      reader.onload = function(e){ preview.src = e.target.result; preview.classList.remove('hidden'); placeholder.style.display='none'; };
      reader.readAsDataURL(this.files[0]);
    } else {
      placeholder.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" stroke="#00D4FF" stroke-width="1.5"/><path d="M14 2v6h6" stroke="#00D4FF" stroke-width="1.5"/></svg><p>' + this.files[0].name + '</p>';
    }
  }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
