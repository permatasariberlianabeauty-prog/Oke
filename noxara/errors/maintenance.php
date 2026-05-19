<?php
$baseUrl = ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST'];
http_response_code(503);
header('Retry-After: 3600');
$msg = 'Website sedang dalam pemeliharaan. Silakan coba lagi nanti.';
try {
    if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
    if (file_exists(ROOT_PATH.'/config/bootstrap.php')) {
        define('SKIP_INSTALL_CHECK',true);
        define('SKIP_MAINTENANCE_CHECK',true);
        require_once ROOT_PATH.'/config/bootstrap.php';
        $msg = getSetting('maintenance_message', $msg);
    }
} catch(Throwable $e) {}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A0E1A">
<title>Pemeliharaan | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body style="background:#0A0E1A;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;font-family:'Plus Jakarta Sans',sans-serif">
<div style="text-align:center;padding:40px 20px;max-width:480px">
  <div style="margin-bottom:24px">
    <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
      <path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" stroke="#FFB800" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
  <div style="font-family:'Orbitron',monospace;font-size:28px;font-weight:900;color:#FFB800;text-shadow:0 0 20px rgba(255,184,0,.5);margin-bottom:12px">NOXARA</div>
  <h1 style="color:#fff;font-size:22px;margin:0 0 16px">Sedang dalam Pemeliharaan</h1>
  <p style="color:rgba(255,255,255,.6);font-size:15px;line-height:1.6;margin:0 0 32px"><?= htmlspecialchars($msg,ENT_QUOTES,'UTF-8') ?></p>
  <div style="background:#0F1629;border:1px solid rgba(255,184,0,.2);border-radius:12px;padding:20px">
    <p style="color:rgba(255,255,255,.5);font-size:13px;margin:0">Hubungi kami di WhatsApp atau Telegram untuk informasi lebih lanjut.</p>
  </div>
</div>
</body></html>
