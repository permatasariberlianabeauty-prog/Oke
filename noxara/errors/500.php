<?php
http_response_code(500);
$baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST'];
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0A0E1A">
<title>500 - Server Error | NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
</head>
<body style="background:#0A0E1A;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;font-family:'Plus Jakarta Sans',sans-serif">
<div style="text-align:center;padding:40px 20px">
  <div style="font-family:'Orbitron',monospace;font-size:clamp(80px,20vw,140px);font-weight:900;color:#FF4757;text-shadow:0 0 40px rgba(255,71,87,.5);line-height:1;margin-bottom:16px">500</div>
  <h1 style="color:#fff;font-size:clamp(18px,5vw,28px);margin:0 0 12px">Terjadi Kesalahan Server</h1>
  <p style="color:rgba(255,255,255,.6);font-size:16px;margin:0 0 32px">Server kami sedang mengalami gangguan. Tim kami sedang memperbaikinya.</p>
  <a href="<?= htmlspecialchars($baseUrl,ENT_QUOTES,'UTF-8') ?>/" style="display:inline-block;background:#00D4FF;color:#0A0E1A;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:700;font-size:16px">Kembali ke Beranda</a>
</div>
</body></html>
