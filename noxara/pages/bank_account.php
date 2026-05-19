<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId   = SessionManager::userId();
$maxBanks = 3;
$user     = db()->fetchOne('SELECT full_name FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$fullName = $user['full_name'] ?? '';

// Add bank
if (isPost() && isset($_POST['add_bank'])) {
    CSRF::verify();
    $bankName   = clean(postVal('bank_name', ''));
    $accNumber  = preg_replace('/[^0-9]/', '', postVal('account_number', ''));
    $accName    = clean(postVal('account_name', ''));

    $errors = [];
    if (empty($bankName)) $errors[] = 'Nama bank wajib diisi.';
    if (strlen($accNumber) < 6) $errors[] = 'Nomor rekening tidak valid.';
    if (empty($accName)) $errors[] = 'Nama pemilik rekening wajib diisi.';

    // Validasi nama harus sama dengan full_name
    if (!empty($accName) && strtolower(trim($accName)) !== strtolower(trim($fullName))) {
        $errors[] = 'Nama pemilik rekening harus sesuai nama lengkap akun Anda: "' . $fullName . '"';
    }

    // Cek max
    $count = db()->fetchOne('SELECT COUNT(*) as cnt FROM bank_accounts WHERE user_id = ?', 'i', [$userId]);
    if ((int)($count['cnt'] ?? 0) >= $maxBanks) {
        $errors[] = 'Maksimal ' . $maxBanks . ' rekening bank.';
    }

    // Cek duplikat nomor rekening
    if (empty($errors)) {
        $dup = db()->fetchOne('SELECT id FROM bank_accounts WHERE user_id = ? AND account_number = ? LIMIT 1', 'is', [$userId, $accNumber]);
        if ($dup) $errors[] = 'Nomor rekening ini sudah terdaftar.';
    }

    if (empty($errors)) {
        $isPrimary = db()->fetchOne('SELECT COUNT(*) as cnt FROM bank_accounts WHERE user_id = ?', 'i', [$userId]);
        $primary   = (int)($isPrimary['cnt'] ?? 0) === 0 ? 1 : 0;
        db()->execute(
            'INSERT INTO bank_accounts (user_id, bank_name, account_number, account_name, is_primary) VALUES (?,?,?,?,?)',
            'isssi', [$userId, $bankName, $accNumber, $accName, $primary]
        );
        setFlashPopup('success', 'Rekening ' . $bankName . ' berhasil ditambahkan.', 'Rekening Ditambahkan');
    } else {
        setFlashPopup('error', implode('<br>', $errors), 'Gagal');
    }
    redirect(BASE_URL . '/pages/bank_account.php');
}

// Set Primary
if (isPost() && isset($_POST['set_primary'])) {
    CSRF::verify();
    $bankId = (int)postVal('bank_id', 0);
    if ($bankId > 0) {
        db()->execute('UPDATE bank_accounts SET is_primary = 0 WHERE user_id = ?', 'i', [$userId]);
        db()->execute('UPDATE bank_accounts SET is_primary = 1 WHERE id = ? AND user_id = ?', 'ii', [$bankId, $userId]);
        setFlashPopup('success', 'Rekening utama berhasil diubah.', 'Berhasil');
    }
    redirect(BASE_URL . '/pages/bank_account.php');
}

// Delete Bank
if (isPost() && isset($_POST['delete_bank'])) {
    CSRF::verify();
    $bankId = (int)postVal('bank_id', 0);
    if ($bankId > 0) {
        // Cek pending withdrawal
        $pendingWd = db()->fetchOne(
            'SELECT id FROM withdrawals WHERE bank_account_id = ? AND status = "pending" LIMIT 1',
            'i', [$bankId]
        );
        if ($pendingWd) {
            setFlashPopup('error', 'Tidak dapat menghapus rekening yang memiliki withdraw pending.', 'Gagal');
        } else {
            db()->execute('DELETE FROM bank_accounts WHERE id = ? AND user_id = ?', 'ii', [$bankId, $userId]);
            setFlashPopup('success', 'Rekening berhasil dihapus.', 'Berhasil');
        }
    }
    redirect(BASE_URL . '/pages/bank_account.php');
}

$bankAccounts = db()->fetchAll(
    'SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at ASC',
    'i', [$userId]
);

// Popular banks
$popularBanks = ['BCA','BNI','BRI','Mandiri','CIMB Niaga','Danamon','Permata','BSI','BTN','Bank Jago','OVO','GoPay','DANA','LinkAja','ShopeePay'];

$pageTitle = 'Rekening Bank';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/profile.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Rekening Bank</h1>
  </div>

  <!-- Bank List -->
  <?php if (!empty($bankAccounts)): ?>
  <div class="banks-list">
    <?php foreach ($bankAccounts as $ba): ?>
    <div class="bank-account-card card <?= $ba['is_primary']?'bank-primary':'' ?>">
      <div class="bank-acc-header">
        <div class="bank-acc-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="15" rx="3" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="15" r="1.5" fill="currentColor"/></svg>
        </div>
        <div class="bank-acc-info">
          <span class="bank-acc-name cyan"><?= e($ba['bank_name']) ?></span>
          <span class="bank-acc-number"><?= e($ba['account_number']) ?></span>
          <span class="bank-acc-holder"><?= e($ba['account_name']) ?></span>
        </div>
        <?php if ($ba['is_primary']): ?>
        <span class="primary-badge">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Utama
        </span>
        <?php endif; ?>
      </div>
      <div class="bank-acc-actions">
        <?php if (!$ba['is_primary']): ?>
        <form method="post" action="" class="inline-form">
          <?= CSRF::field() ?>
          <input type="hidden" name="bank_id" value="<?= (int)$ba['id'] ?>">
          <button type="submit" name="set_primary" value="1" class="btn btn-sm btn-outline-cyan">
            Jadikan Utama
          </button>
        </form>
        <?php endif; ?>
        <form method="post" action="" class="inline-form" onsubmit="return confirm('Hapus rekening ini?')">
          <?= CSRF::field() ?>
          <input type="hidden" name="bank_id" value="<?= (int)$ba['id'] ?>">
          <button type="submit" name="delete_bank" value="1" class="btn btn-sm btn-outline-red">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Hapus
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="notice-banner notice-info">Belum ada rekening terdaftar. Tambahkan rekening untuk melakukan withdraw.</div>
  <?php endif; ?>

  <!-- Add Bank Form -->
  <?php if (count($bankAccounts) < $maxBanks): ?>
  <div class="card">
    <h2 class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Tambah Rekening Baru
    </h2>
    <p class="form-hint" style="margin-bottom:12px">
      Maksimal <?= $maxBanks ?> rekening. Nama pemilik harus sesuai nama lengkap akun Anda: <strong class="cyan"><?= e($fullName) ?></strong>
    </p>
    <form method="post" action="" class="form">
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label" for="bank_name">Nama Bank <span class="required">*</span></label>
        <input type="text" id="bank_name" name="bank_name" class="form-input"
          placeholder="Contoh: BCA" list="bankList" required maxlength="50">
        <datalist id="bankList">
          <?php foreach ($popularBanks as $pb): ?>
          <option value="<?= e($pb) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="form-group">
        <label class="form-label" for="account_number">Nomor Rekening <span class="required">*</span></label>
        <input type="text" id="account_number" name="account_number" class="form-input"
          placeholder="Contoh: 1234567890" inputmode="numeric" required maxlength="30">
      </div>
      <div class="form-group">
        <label class="form-label" for="account_name">Nama Pemilik Rekening <span class="required">*</span></label>
        <input type="text" id="account_name" name="account_name" class="form-input"
          placeholder="<?= e($fullName) ?>" required maxlength="100"
          value="<?= e($fullName) ?>">
        <p class="form-hint">Harus sama persis dengan nama lengkap akun Anda.</p>
      </div>
      <button type="submit" name="add_bank" value="1" class="btn btn-primary btn-full btn-lg">
        Tambah Rekening
      </button>
    </form>
  </div>
  <?php else: ?>
  <div class="notice-banner notice-info">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#00D4FF" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
    Batas maksimal <?= $maxBanks ?> rekening sudah tercapai.
  </div>
  <?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
