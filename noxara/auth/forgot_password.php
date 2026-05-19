<?php
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

if (SessionManager::isLoggedIn()) redirect(BASE_URL . '/pages/dashboard.php');

$error = ''; $success = '';
if (isPost()) {
    CSRF::verifyOrFail();
    $email = clean(postVal('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid.';
    } else {
        $user = db()->fetchOne('SELECT id, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1', 's', [$email]);
        if ($user) {
            $token = createPasswordReset((int)$user['id']);
            $resetLink = BASE_URL . '/auth/reset_password.php?token=' . $token;
            writeLog('password_resets.log', "Reset link untuk {$email}: {$resetLink}");
        }
        $success = 'Jika email terdaftar, link reset password telah dikirim. Silakan periksa log sistem (hubungi admin jika tidak menerima email).';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A0E1A"><?= CSRF::meta() ?>
<title>Lupa Password | NOXARA</title>
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
    <div class="auth-icon-wrap">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <h1 class="auth-title">Lupa Password</h1>
    <p class="auth-subtitle">Masukkan email Anda untuk mendapatkan link reset password</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if (!$success): ?>
    <form method="POST" class="auth-form">
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label">Email Terdaftar</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="#00D4FF" stroke-width="2"/><polyline points="22,6 12,13 2,6" stroke="#00D4FF" stroke-width="2"/></svg></span>
          <input type="email" name="email" class="form-input" placeholder="Email Anda" required value="<?= e(postVal('email')) ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-full">Kirim Link Reset</button>
    </form>
    <?php endif; ?>
    <p class="auth-switch"><a href="<?= BASE_URL ?>/auth/login.php" class="link-cyan">← Kembali ke halaman masuk</a></p>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body></html>
