<?php
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

if (SessionManager::isLoggedIn()) redirect(BASE_URL . '/pages/dashboard.php');

$error = '';
$success = SessionManager::getFlash('success_message', '');

if (isPost()) {
    CSRF::verifyOrFail();
    $username = clean(postVal('username'));
    $password = postVal('password');
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            setFlashPopup('success', getSetting('welcome_popup_title','Selamat datang kembali!'));
            $redirect = SessionManager::getFlash('redirect_after_login', BASE_URL . '/pages/dashboard.php');
            redirect($redirect);
        } else {
            $error = $result['message'];
        }
    }
}
$pageTitle = 'Masuk';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<meta name="theme-color" content="#0A0E1A">
<?= CSRF::meta() ?>
<title>Masuk | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">
</head>
<body class="dark-theme auth-body">
<div id="toast-container"></div>

<div class="auth-container">
  <!-- Brand -->
  <div class="auth-brand">
    <a href="<?= BASE_URL ?>/" class="logo-text orbitron">NOXARA</a>
    <p class="auth-tagline">Invest Smarter, Grow Faster</p>
  </div>

  <!-- Particle Background -->
  <canvas id="particlesBg" class="particles-bg"></canvas>

  <div class="auth-card" style="position:relative;z-index:1;">
    <h1 class="auth-title">Masuk Akun</h1>
    <p class="auth-subtitle">Selamat datang kembali di NOXARA</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form" id="loginForm" novalidate>
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label" for="username">Username atau Email</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <input type="text" id="username" name="username" class="form-input" placeholder="Username atau email"
            value="<?= e(postVal('username')) ?>" autocomplete="username" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#00D4FF" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
          </span>
          <input type="password" id="password" name="password" class="form-input" placeholder="Password" autocomplete="current-password" required>
          <button type="button" class="input-eye" id="togglePwd" aria-label="Tampilkan password">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" id="eyeIcon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
          </button>
        </div>
      </div>
      <div class="form-row-between">
        <label class="checkbox-label">
          <input type="checkbox" name="remember" value="1"> Ingat saya
        </label>
        <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="link-subtle">Lupa password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-full" id="loginBtn">
        <span class="btn-text">Masuk</span>
        <span class="btn-spinner hidden"></span>
      </button>
    </form>

    <p class="auth-switch">Belum punya akun? <a href="<?= BASE_URL ?>/auth/register.php" class="link-cyan">Daftar Sekarang</a></p>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/animations.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click',function(){
  const inp=document.getElementById('password');
  inp.type=inp.type==='password'?'text':'password';
});
document.getElementById('loginForm').addEventListener('submit',function(){
  document.getElementById('loginBtn').querySelector('.btn-text').textContent='Memproses...';
  document.getElementById('loginBtn').disabled=true;
});
// Particle canvas animation
(function(){
  var c=document.getElementById('particlesBg');
  if(!c||!c.getContext)return;
  var ctx=c.getContext('2d');
  c.width=window.innerWidth; c.height=window.innerHeight;
  var pts=[];
  for(var i=0;i<60;i++){pts.push({x:Math.random()*c.width,y:Math.random()*c.height,r:Math.random()*2+0.5,vx:(Math.random()-0.5)*0.4,vy:(Math.random()-0.5)*0.4,a:Math.random()*0.4+0.1,c:Math.random()>0.5?'0,212,255':'123,47,255'});}
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    pts.forEach(function(p){
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle='rgba('+p.c+','+p.a+')';ctx.fill();
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0||p.x>c.width)p.vx*=-1;
      if(p.y<0||p.y>c.height)p.vy*=-1;
    });
    requestAnimationFrame(draw);
  }
  draw();
  window.addEventListener('resize',function(){c.width=window.innerWidth;c.height=window.innerHeight;});
})();
</script>
</body></html>