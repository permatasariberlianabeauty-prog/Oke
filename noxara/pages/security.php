<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId = SessionManager::userId();
$user   = db()->fetchOne('SELECT pin FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$hasPin = !empty($user['pin']);

// Change Password
if (isPost() && isset($_POST['change_password'])) {
    CSRF::verify();
    $oldPw  = postVal('old_password', '');
    $newPw  = postVal('new_password', '');
    $confPw = postVal('confirm_password', '');
    if ($newPw !== $confPw) {
        setFlashPopup('error', 'Konfirmasi password baru tidak cocok.', 'Gagal');
    } else {
        $res = changePassword($userId, $oldPw, $newPw);
        if ($res['success']) {
            setFlashPopup('success', 'Password berhasil diubah.', 'Berhasil');
        } else {
            setFlashPopup('error', $res['message'], 'Gagal');
        }
    }
    redirect(BASE_URL . '/pages/security.php');
}

// Set/Change PIN
if (isPost() && isset($_POST['set_pin'])) {
    CSRF::verify();
    $pin      = postVal('pin', '');
    $confPin  = postVal('confirm_pin', '');
    $oldPin   = postVal('old_pin', null);
    $res = setPin($userId, $pin, $confPin, $hasPin ? $oldPin : null);
    if ($res['success']) {
        setFlashPopup('success', $hasPin ? 'PIN berhasil diubah.' : 'PIN berhasil dibuat.', 'Berhasil');
    } else {
        setFlashPopup('error', $res['message'], 'Gagal');
    }
    redirect(BASE_URL . '/pages/security.php');
}

$pageTitle = 'Keamanan';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/profile.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Keamanan</h1>
  </div>

  <!-- Change Password -->
  <div class="card">
    <h2 class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="#00D4FF" stroke-width="2"/></svg>
      Ganti Password
    </h2>
    <form method="post" action="" class="form">
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label" for="old_password">Password Lama <span class="required">*</span></label>
        <div class="pin-input-wrap">
          <input type="password" id="old_password" name="old_password" class="form-input" required autocomplete="current-password">
          <button type="button" class="pin-toggle" data-target="old_password" aria-label="Tampilkan">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="new_password">Password Baru <span class="required">*</span></label>
        <div class="pin-input-wrap">
          <input type="password" id="new_password" name="new_password" class="form-input" required autocomplete="new-password" minlength="6">
          <button type="button" class="pin-toggle" data-target="new_password" aria-label="Tampilkan">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
        <p class="form-hint">Minimal 6 karakter.</p>
      </div>
      <div class="form-group">
        <label class="form-label" for="confirm_password">Konfirmasi Password Baru <span class="required">*</span></label>
        <div class="pin-input-wrap">
          <input type="password" id="confirm_password" name="confirm_password" class="form-input" required autocomplete="new-password" minlength="6">
          <button type="button" class="pin-toggle" data-target="confirm_password" aria-label="Tampilkan">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" name="change_password" value="1" class="btn btn-primary btn-full btn-lg">
        Ubah Password
      </button>
    </form>
  </div>

  <!-- Set / Change PIN -->
  <div class="card">
    <h2 class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 20v-6M6 20V10M18 20V4" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <?= $hasPin ? 'Ganti PIN Transaksi' : 'Buat PIN Transaksi' ?>
    </h2>
    <?php if (!$hasPin): ?>
    <div class="notice-banner notice-warning">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="#FFB800" stroke-width="2"/></svg>
      PIN belum dibuat. Buat PIN untuk mengamankan transaksi withdraw.
    </div>
    <?php endif; ?>
    <form method="post" action="" class="form">
      <?= CSRF::field() ?>
      <?php if ($hasPin): ?>
      <div class="form-group">
        <label class="form-label" for="old_pin">PIN Lama <span class="required">*</span></label>
        <div class="pin-input-wrap">
          <input type="password" id="old_pin" name="old_pin" class="form-input pin-input"
            placeholder="••••••" maxlength="6" minlength="6" inputmode="numeric" required>
          <button type="button" class="pin-toggle" data-target="old_pin" aria-label="Tampilkan PIN">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label class="form-label" for="pin">PIN Baru <span class="required">*</span></label>
        <div class="pin-dots-wrap">
          <div class="pin-dots" id="pinDots">
            <?php for ($i=0;$i<6;$i++): ?>
            <span class="pin-dot" id="dot<?= $i ?>"></span>
            <?php endfor; ?>
          </div>
          <input type="password" id="pin" name="pin" class="form-input pin-input visually-hidden-input"
            placeholder="6 digit PIN" maxlength="6" minlength="6" pattern="\d{6}"
            inputmode="numeric" autocomplete="off" required>
        </div>
        <div class="pin-numpad" id="pinNumpad">
          <?php for ($n=1;$n<=9;$n++): ?>
          <button type="button" class="numpad-btn" data-num="<?= $n ?>"><?= $n ?></button>
          <?php endfor; ?>
          <button type="button" class="numpad-btn numpad-clear" data-action="clear">✕</button>
          <button type="button" class="numpad-btn" data-num="0">0</button>
          <button type="button" class="numpad-btn numpad-del" data-action="del">⌫</button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="confirm_pin">Konfirmasi PIN Baru <span class="required">*</span></label>
        <input type="password" id="confirm_pin" name="confirm_pin" class="form-input pin-input"
          placeholder="••••••" maxlength="6" minlength="6" pattern="\d{6}"
          inputmode="numeric" autocomplete="off" required>
      </div>
      <button type="submit" name="set_pin" value="1" class="btn btn-primary btn-full btn-lg">
        <?= $hasPin ? 'Ubah PIN' : 'Buat PIN' ?>
      </button>
    </form>
  </div>

  <!-- Dark/Light Mode Toggle -->
  <div class="card">
    <h2 class="card-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="5" stroke="#FFB800" stroke-width="2"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="#FFB800" stroke-width="2" stroke-linecap="round"/></svg>
      Tema Tampilan
    </h2>
    <div class="theme-toggle-row">
      <span class="theme-label">Mode Gelap</span>
      <label class="toggle-switch" for="themeToggle">
        <input type="checkbox" id="themeToggle" role="switch" aria-label="Toggle tema">
        <span class="toggle-slider"></span>
      </label>
      <span class="theme-label">Mode Terang</span>
    </div>
    <p class="form-hint">Preferensi tema disimpan di perangkat Anda.</p>
  </div>

</div>

<script>
// PIN toggle visibility
document.querySelectorAll('.pin-toggle').forEach(function(btn){
  btn.addEventListener('click', function(){
    var inp = document.getElementById(this.dataset.target);
    inp.type = inp.type === 'password' ? 'text' : 'password';
  });
});

// PIN numpad
var pinInput  = document.getElementById('pin');
var pinDots   = document.querySelectorAll('.pin-dot');
var numpadBtns= document.querySelectorAll('.numpad-btn');

function updateDots(val) {
  pinDots.forEach(function(dot, i){
    dot.classList.toggle('filled', i < val.length);
  });
}

if (numpadBtns.length && pinInput) {
  numpadBtns.forEach(function(btn){
    btn.addEventListener('click', function(){
      var num    = this.dataset.num;
      var action = this.dataset.action;
      var val    = pinInput.value;
      if (action === 'del') {
        pinInput.value = val.slice(0,-1);
      } else if (action === 'clear') {
        pinInput.value = '';
      } else if (val.length < 6) {
        pinInput.value = val + num;
      }
      updateDots(pinInput.value);
    });
  });
  pinInput.addEventListener('input', function(){ updateDots(this.value); });
}

// Theme toggle
var themeToggle = document.getElementById('themeToggle');
var theme = localStorage.getItem('nxr_theme') || 'dark';
if (theme === 'light') {
  document.body.classList.add('light-theme');
  document.body.classList.remove('dark-theme');
  if (themeToggle) themeToggle.checked = true;
}

if (themeToggle) {
  themeToggle.addEventListener('change', function(){
    if (this.checked) {
      document.body.classList.add('light-theme');
      document.body.classList.remove('dark-theme');
      localStorage.setItem('nxr_theme', 'light');
    } else {
      document.body.classList.add('dark-theme');
      document.body.classList.remove('light-theme');
      localStorage.setItem('nxr_theme', 'dark');
    }
  });
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
