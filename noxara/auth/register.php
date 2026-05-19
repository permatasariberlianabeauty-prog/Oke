<?php
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/referral.php';

if (SessionManager::isLoggedIn()) redirect(BASE_URL . '/pages/dashboard.php');

$error = '';
$refCode = clean(getVal('ref', ''));

if (isPost()) {
    CSRF::verifyOrFail();

    // Captcha math
    $captchaAnswer = (int)postVal('captcha_answer');
    $captchaA      = (int)SessionManager::get('captcha_a', 0);
    $captchaB      = (int)SessionManager::get('captcha_b', 0);
    if ($captchaAnswer !== ($captchaA + $captchaB)) {
        $error = 'Jawaban captcha salah.';
    } elseif (!postVal('agree_terms')) {
        $error = 'Anda wajib menyetujui syarat dan ketentuan.';
    } else {
        $result = registerUser([
            'username'      => postVal('username'),
            'email'         => postVal('email'),
            'phone'         => postVal('phone'),
            'full_name'     => postVal('full_name'),
            'password'      => postVal('password'),
            'referral_code' => postVal('referral_code'),
        ]);
        if ($result['success']) {
            SessionManager::flash('success_message', 'Akun berhasil dibuat! Silakan masuk.');
            redirect(BASE_URL . '/auth/login.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Generate captcha
$captchaA = random_int(1, 15);
$captchaB = random_int(1, 15);
SessionManager::set('captcha_a', $captchaA);
SessionManager::set('captcha_b', $captchaB);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<meta name="theme-color" content="#0A0E1A">
<?= CSRF::meta() ?>
<title>Daftar | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Orbitron:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">
</head>
<body class="dark-theme auth-body">
<div id="toast-container"></div>
<div class="auth-container">
  <div class="auth-brand">
    <a href="<?= BASE_URL ?>/" class="logo-text orbitron">NOXARA</a>
    <p class="auth-tagline">Invest Smarter, Grow Faster</p>
  </div>
  <div class="particles-bg" id="particlesBg"></div>
  <div class="auth-card">
    <h1 class="auth-title">Buat Akun</h1>
    <p class="auth-subtitle">Bergabung dan mulai berinvestasi bersama NOXARA</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form" id="registerForm" novalidate>
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="#00D4FF" stroke-width="2"/></svg></span>
          <input type="text" name="full_name" class="form-input" placeholder="Nama lengkap sesuai KTP"
            value="<?= e(postVal('full_name')) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="#00D4FF" stroke-width="2"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg></span>
          <input type="text" name="username" class="form-input" placeholder="Username (4-20 karakter)"
            value="<?= e(postVal('username')) ?>" pattern="[a-zA-Z0-9_]+" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="#00D4FF" stroke-width="2"/><polyline points="22,6 12,13 2,6" stroke="#00D4FF" stroke-width="2"/></svg></span>
          <input type="email" name="email" class="form-input" placeholder="Email aktif"
            value="<?= e(postVal('email')) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nomor HP</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.8 19.79 19.79 0 01.07 2.18 2 2 0 012.06 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z" stroke="#00D4FF" stroke-width="2"/></svg></span>
          <input type="tel" name="phone" class="form-input" placeholder="08xxxxxxxxxx"
            value="<?= e(postVal('phone')) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg></span>
          <input type="password" name="password" id="password" class="form-input" placeholder="Min 6 karakter" required>
          <button type="button" class="input-eye" id="togglePwd"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg></button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Konfirmasi Password</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg></span>
          <input type="password" name="confirm_password" id="confirmPwd" class="form-input" placeholder="Ulangi password" required>
        </div>
        <p id="pwdMatch" class="form-hint hidden"></p>
      </div>
      <div class="form-group">
        <label class="form-label">Kode Referral <span class="optional">(Opsional)</span></label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg></span>
          <input type="text" name="referral_code" class="form-input" placeholder="Kode referral"
            value="<?= e(!empty($refCode) ? $refCode : postVal('referral_code')) ?>"
            <?= !empty($refCode) ? 'readonly' : '' ?> style="text-transform:uppercase">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Verifikasi: Berapa <?= $captchaA ?> + <?= $captchaB ?>?</label>
        <div class="input-wrap">
          <input type="number" name="captcha_answer" class="form-input" placeholder="Jawaban" required min="0">
        </div>
      </div>
      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="agree_terms" required>
          Saya menyetujui <a href="<?= BASE_URL ?>/pages/info.php?tab=syarat-ketentuan" target="_blank" class="link-cyan">Syarat & Ketentuan</a>
          dan <a href="<?= BASE_URL ?>/pages/info.php?tab=kebijakan-privasi" target="_blank" class="link-cyan">Kebijakan Privasi</a>
        </label>
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-full" id="regBtn">
        <span class="btn-text">Daftar Sekarang</span>
      </button>
    </form>
    <p class="auth-switch">Sudah punya akun? <a href="<?= BASE_URL ?>/auth/login.php" class="link-cyan">Masuk di sini</a></p>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/animations.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click',function(){const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';});
document.getElementById('confirmPwd').addEventListener('input',function(){
  const p=document.getElementById('password').value;
  const hint=document.getElementById('pwdMatch');
  hint.classList.remove('hidden');
  if(this.value===p){hint.textContent='✓ Password cocok';hint.className='form-hint success';}
  else{hint.textContent='✗ Password tidak cocok';hint.className='form-hint error';}
});
document.getElementById('registerForm').addEventListener('submit',function(e){
  const p=document.getElementById('password').value;
  const c=document.getElementById('confirmPwd').value;
  if(p!==c){e.preventDefault();showToast({type:'error',message:'Konfirmasi password tidak cocok.'});return;}
  document.getElementById('regBtn').disabled=true;
  document.getElementById('regBtn').textContent='Mendaftarkan...';
});
</script>
</body></html>
