<?php
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_INSTALL_CHECK', true);
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
http_response_code(404);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A0E1A">
<title>404 - Halaman Tidak Ditemukan | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="dark-theme error-body">
<div class="error-container">
  <div class="error-glitch" data-text="404">404</div>
  <h1 class="error-title">Halaman Tidak Ditemukan</h1>
  <p class="error-message">Halaman yang Anda cari tidak ada atau telah dipindahkan.</p>
  <div class="error-actions">
    <a href="<?= BASE_URL ?>/" class="btn btn-primary btn-lg">Kembali ke Beranda</a>
    <?php if (SessionManager::isLoggedIn()): ?>
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-outline-cyan btn-lg">Dashboard</a>
    <?php endif; ?>
  </div>
</div>
<style>
body{background:#0A0E1A;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;font-family:'Plus Jakarta Sans',sans-serif}
.error-container{text-align:center;padding:40px 20px}
.error-glitch{font-family:'Orbitron',monospace;font-size:clamp(80px,20vw,160px);font-weight:900;color:#00D4FF;text-shadow:0 0 40px rgba(0,212,255,.5);line-height:1;margin-bottom:16px}
.error-title{color:#fff;font-size:clamp(20px,5vw,32px);margin:0 0 12px}
.error-message{color:rgba(255,255,255,.6);font-size:16px;margin:0 0 32px}
.error-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
</style>
</body></html>
