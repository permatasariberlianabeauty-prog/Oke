<?php
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

if (SessionManager::isLoggedIn()) redirect(BASE_URL . '/pages/dashboard.php');

$token = clean(getVal('token'));
$error = ''; $success = '';

if (empty($token)) redirect(BASE_URL . '/auth/forgot_password.php');

$reset = db()->fetchOne('SELECT * FROM password_resets WHERE token = ? AND is_used = 0 AND expired_at > NOW() LIMIT 1', 's', [$token]);
if (!$reset) {
    $error = 'Link reset tidak valid atau sudah kadaluarsa. Minta link baru.';
}

if (isPost() && $reset) {
    CSRF::verifyOrFail();
    $newPwd  = postVal('password');
    $confPwd = postVal('confirm_password');
    if ($newPwd !== $confPwd) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $result = resetPasswordByToken($token, $newPwd);
        if ($result['success']) {
            SessionManager::flash('success_message', 'Password berhasil diubah. Silakan masuk dengan password baru.');
            redirect(BASE_URL . '/auth/login.php');
        } else {
            $error = $result['message'];
        }
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A0E1A"><?= CSRF::meta() ?>
<title>Reset Password | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">
</head>
<body class="dark-theme auth-body">
<div class="auth-container">
  <div class="auth-brand">
    <a href="<?= BASE_URL ?>/" class="logo-text orbitron">NOXARA</a>
  </div>
  <div class="auth-card">
    <h1 class="auth-title">Buat Password Baru</h1>
    <p class="auth-subtitle">Masukkan password baru untuk akun Anda</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($reset && !$error): ?>
    <form method="POST" class="auth-form" id="resetForm">
      <?= CSRF::field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="form-group">
        <label class="form-label">Password Baru</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg></span>
          <input type="password" name="password" id="pwd" class="form-input" placeholder="Min 6 karakter" required>
          <button type="button" class="input-eye" onclick="const i=document.getElementById('pwd');i.type=i.type==='password'?'text':'password'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Konfirmasi Password</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg></span>
          <input type="password" name="confirm_password" id="cpwd" class="form-input" placeholder="Ulangi password" required>
        </div>
        <p id="pmatch" class="form-hint hidden"></p>
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-full">Simpan Password Baru</button>
    </form>
    <?php endif; ?>
    <p class="auth-switch"><a href="<?= BASE_URL ?>/auth/login.php" class="link-cyan">← Kembali ke halaman masuk</a></p>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
document.getElementById('cpwd')?.addEventListener('input',function(){
  const h=document.getElementById('pmatch');
  h.classList.remove('hidden');
  if(this.value===document.getElementById('pwd').value){h.textContent='✓ Password cocok';h.className='form-hint success';}
  else{h.textContent='✗ Tidak cocok';h.className='form-hint error';}
});
</script>
</body></html>
