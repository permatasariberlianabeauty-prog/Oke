<?php
define('ROOT_PATH', dirname(__DIR__));
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';
$reason = SessionManager::get('block_reason', 'Akun Anda telah diblokir oleh administrator.');
SessionManager::logoutUser();
http_response_code(403);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A0E1A">
<title>Akun Diblokir | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body style="background:#0A0E1A;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;font-family:'Plus Jakarta Sans',sans-serif">
<div style="text-align:center;padding:40px 20px;max-width:480px">
  <div style="margin-bottom:24px">
    <svg width="72" height="72" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="#FF4757" stroke-width="2"/>
      <path d="M4.93 4.93l14.14 14.14" stroke="#FF4757" stroke-width="2" stroke-linecap="round"/>
    </svg>
  </div>
  <h1 style="color:#FF4757;font-family:'Orbitron',monospace;font-size:24px;margin:0 0 16px">Akun Diblokir</h1>
  <p style="color:rgba(255,255,255,.7);font-size:15px;line-height:1.6;margin:0 0 24px"><?= htmlspecialchars($reason,ENT_QUOTES,'UTF-8') ?></p>
  <div style="background:#0F1629;border:1px solid rgba(255,71,87,.2);border-radius:12px;padding:20px;margin-bottom:24px">
    <p style="color:rgba(255,255,255,.5);font-size:13px;margin:0">Jika Anda merasa ini adalah kesalahan, hubungi tim support kami untuk bantuan lebih lanjut.</p>
  </div>
  <a href="<?= BASE_URL ?>/pages/contact.php" style="display:inline-block;background:#00D4FF;color:#0A0E1A;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:700;font-size:15px">Hubungi Support</a>
</div>
</body></html>
