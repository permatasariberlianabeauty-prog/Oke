<?php
/**
 * NOXARA - Landing Page (Homepage)
 */
define('ROOT_PATH', __DIR__);
define('SKIP_INSTALL_CHECK', false);

require_once ROOT_PATH . '/config/bootstrap.php';

// Redirect if logged in
if (SessionManager::isLoggedIn()) {
    redirect(BASE_URL . '/pages/dashboard.php');
}

// Fetch data gracefully (DB may not be available)
$platformStats  = [];
$marqueeItems   = [];
$banners        = [];
$featuredProds  = [];
$faqItems       = [];

try {
    $db = db();

    // Platform stats
    $info = $db->fetchOne("SELECT * FROM platform_info LIMIT 1");
    $platformStats = $info ?: [];

    // Marquee items
    $mq = $db->fetchOne("SELECT * FROM marquee_settings WHERE is_enabled=1 LIMIT 1");
    if ($mq) {
        if (!empty($mq['custom_messages'])) {
            foreach (explode("\n", $mq['custom_messages']) as $msg) {
                if (trim($msg)) $marqueeItems[] = trim($msg);
            }
        }
    }

    // Banners (homepage)
    $banners = $db->fetchAll("SELECT * FROM banners WHERE status='active' AND position='homepage' ORDER BY sort_order ASC LIMIT 5");

    // Featured products
    $featuredProds = $db->fetchAll("SELECT * FROM products WHERE status='active' AND is_featured=1 ORDER BY sort_order ASC LIMIT 3");
    if (empty($featuredProds)) {
        $featuredProds = $db->fetchAll("SELECT * FROM products WHERE status='active' ORDER BY sort_order ASC LIMIT 3");
    }

    // FAQ from legal_pages or static
    $faqPage = $db->fetchOne("SELECT content FROM legal_pages WHERE slug='faq' LIMIT 1");

} catch (Exception $e) {
    // DB not available — show with defaults
}

// Default stats if not from DB
$memberCount  = $platformStats['member_count']  ?? '284.750+';
$totalPayout  = $platformStats['total_payout']  ?? 'Rp15,8M+';
$rating       = $platformStats['rating']        ?? '4.9/5';
$since        = $platformStats['since_year']    ?? '2024';
$whatsappUrl  = getSetting('whatsapp_url', '#') ?? '#';
$telegramUrl  = getSetting('telegram_url', '#') ?? '#';
$instagramUrl = getSetting('instagram_url', '#') ?? '#';

$pageTitle = APP_NAME . ' — ' . APP_TAGLINE;
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0A0E1A">
<meta name="description" content="NOXARA — Platform investasi digital terpercaya. Invest Smarter, Grow Faster. Daftar sekarang dan raih profit harian.">
<meta property="og:title" content="NOXARA — Invest Smarter, Grow Faster">
<meta property="og:description" content="Platform investasi digital terpercaya. Profit harian, referral system, dan teknologi terdepan.">
<meta property="og:type" content="website">
<title><?= e($pageTitle) ?></title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">

<!-- Inline landing page overrides -->
<style>
body { overflow-x: hidden; }
.landing-topbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  background: rgba(10,14,26,0.92); backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255,255,255,0.06); height: 60px;
  display: flex; align-items: center;
}
.landing-topbar-inner {
  max-width: 1200px; margin: 0 auto; padding: 0 20px;
  display: flex; align-items: center; justify-content: space-between; width: 100%;
}
.landing-nav { display: flex; align-items: center; gap: 8px; }
.hero-section {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  padding: 80px 20px 60px; position: relative; overflow: hidden;
  background: radial-gradient(ellipse at 20% 50%, rgba(0,212,255,0.08) 0%, transparent 60%),
              radial-gradient(ellipse at 80% 50%, rgba(123,47,255,0.08) 0%, transparent 60%),
              #0A0E1A;
}
.hero-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 40px; width: 100%; }
.hero-text { flex: 1; }
.hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; background: rgba(0,212,255,0.1); border: 1px solid rgba(0,212,255,0.25); color: #00D4FF; font-size: 12px; font-weight: 700; letter-spacing: 2px; padding: 6px 14px; border-radius: 20px; margin-bottom: 20px; text-transform: uppercase; }
.hero-title { font-family: 'Orbitron', sans-serif; font-size: clamp(28px, 5vw, 52px); font-weight: 900; line-height: 1.1; margin-bottom: 16px; }
.hero-title .gradient-text { background: linear-gradient(135deg, #00D4FF, #7B2FFF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.hero-desc { font-size: 16px; color: #94A3B8; line-height: 1.7; margin-bottom: 32px; max-width: 480px; }
.hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.hero-visual { flex: 0 0 400px; display: flex; align-items: center; justify-content: center; }
.hero-robot { width: 260px; height: 260px; animation: float 3.5s ease-in-out infinite; filter: drop-shadow(0 0 40px rgba(0,212,255,0.3)); }

.stats-section { padding: 60px 20px; background: rgba(15,22,41,0.6); border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); }
.stats-grid-land { max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: repeat(4,1fr); gap: 24px; text-align: center; }
.stat-land-val { font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; color: #00D4FF; line-height: 1; }
.stat-land-label { font-size: 13px; color: #64748B; margin-top: 6px; }

.section-land { padding: 70px 20px; max-width: 1200px; margin: 0 auto; }
.section-land-title { font-size: clamp(22px, 3vw, 34px); font-weight: 800; text-align: center; margin-bottom: 8px; }
.section-land-sub { text-align: center; color: #64748B; font-size: 15px; margin-bottom: 44px; }

.steps-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; margin-top: 40px; }
.step-item-land { background: #0F1629; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 28px 24px; text-align: center; position: relative; }
.step-num { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #00D4FF, #7B2FFF); display: flex; align-items: center; justify-content: center; font-family: 'Orbitron', sans-serif; font-weight: 700; font-size: 16px; color: #fff; margin: 0 auto 16px; }
.step-title { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
.step-desc { font-size: 13px; color: #64748B; line-height: 1.6; }

.products-grid-land { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }

.ref-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
.ref-table th { background: rgba(0,212,255,0.08); color: #00D4FF; font-size: 12px; font-weight: 700; letter-spacing: 1px; padding: 10px 16px; text-align: left; border: 1px solid rgba(255,255,255,0.06); }
.ref-table td { padding: 12px 16px; border: 1px solid rgba(255,255,255,0.04); font-size: 14px; color: #94A3B8; }
.ref-table tr:nth-child(even) td { background: rgba(255,255,255,0.02); }

.faq-list { display: flex; flex-direction: column; gap: 10px; max-width: 760px; margin: 0 auto; }
.faq-item { background: #0F1629; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; overflow: hidden; }
.faq-q { padding: 16px 20px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
.faq-q::after { content: '+'; color: #00D4FF; font-size: 20px; font-weight: 300; flex-shrink: 0; transition: transform 0.2s; }
.faq-item.open .faq-q::after { transform: rotate(45deg); }
.faq-a { padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s; font-size: 14px; color: #64748B; line-height: 1.7; }
.faq-item.open .faq-a { max-height: 300px; padding: 0 20px 16px; }

.land-footer { background: #0F1629; border-top: 1px solid rgba(255,255,255,0.06); padding: 48px 20px 32px; }
.land-footer-inner { max-width: 1200px; margin: 0 auto; }
.land-footer-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 32px; flex-wrap: wrap; margin-bottom: 32px; }
.land-footer-brand .logo-text { font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 3px; background: linear-gradient(135deg,#00D4FF,#7B2FFF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.land-footer-brand p { color: #64748B; font-size: 13px; margin-top: 8px; max-width: 260px; line-height: 1.6; }
.land-footer-links h4 { font-size: 12px; font-weight: 700; letter-spacing: 2px; color: #475569; text-transform: uppercase; margin-bottom: 12px; }
.land-footer-links a { display: block; color: #94A3B8; font-size: 13px; margin-bottom: 8px; text-decoration: none; transition: color 0.2s; }
.land-footer-links a:hover { color: #00D4FF; }
.social-links { display: flex; gap: 12px; margin-top: 16px; }
.social-btn { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; color: #94A3B8; transition: all 0.2s; }
.social-btn:hover { background: rgba(0,212,255,0.1); border-color: rgba(0,212,255,0.3); color: #00D4FF; }
.land-footer-bottom { border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.land-footer-bottom p { font-size: 12px; color: #475569; }
.land-footer-bottom a { color: #64748B; font-size: 12px; text-decoration: none; }
.land-footer-bottom a:hover { color: #00D4FF; }

@media (max-width: 900px) {
  .hero-inner { flex-direction: column; text-align: center; }
  .hero-desc { margin: 0 auto 24px; }
  .hero-actions { justify-content: center; }
  .hero-visual { display: none; }
  .stats-grid-land { grid-template-columns: repeat(2,1fr); gap: 16px; }
  .steps-grid { grid-template-columns: 1fr; }
  .products-grid-land { grid-template-columns: 1fr 1fr; }
  .land-footer-top { flex-direction: column; }
}
@media (max-width: 600px) {
  .stats-grid-land { grid-template-columns: 1fr 1fr; }
  .products-grid-land { grid-template-columns: 1fr; }
  .stat-land-val { font-size: 24px; }
  .hero-actions .btn-lg { padding: 13px 22px; font-size: 14px; }
}
</style>
</head>
<body class="dark-theme">

<!-- SVG Icons Sprite -->
<?php if (file_exists(ROOT_PATH . '/assets/img/icons/icons.svg')): ?>
<?php include ROOT_PATH . '/assets/img/icons/icons.svg'; ?>
<?php endif; ?>

<!-- Particle Canvas -->
<canvas id="particle-canvas"></canvas>

<!-- Toast Container -->
<div id="toast-container" aria-live="polite"></div>

<!-- TOPBAR -->
<header class="landing-topbar">
  <div class="landing-topbar-inner">
    <a href="<?= BASE_URL ?>/" class="logo-text orbitron" style="text-decoration:none">NOXARA</a>
    <nav class="landing-nav">
      <a href="#cara-kerja" class="btn btn-ghost btn-sm" style="display:none;opacity:0" id="navCara">Cara Kerja</a>
      <a href="<?= BASE_URL ?>/auth/login.php"    class="btn btn-outline-cyan btn-sm">Masuk</a>
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">Daftar</a>
    </nav>
  </div>
</header>

<!-- MARQUEE -->
<?php if (!empty($marqueeItems)): ?>
<div class="marquee-bar" style="margin-top:60px;--marquee-speed:28s">
  <div class="marquee-inner">
    <span class="marquee-content">
      <?php foreach ($marqueeItems as $item): ?><?= e($item) ?> &nbsp;&nbsp;•&nbsp;&nbsp; <?php endforeach; ?>
      <?php foreach ($marqueeItems as $item): ?><?= e($item) ?> &nbsp;&nbsp;•&nbsp;&nbsp; <?php endforeach; ?>
    </span>
  </div>
</div>
<?php endif; ?>


<!-- HERO SECTION -->
<section class="hero-section" style="<?= empty($marqueeItems) ? 'padding-top:80px' : '' ?>">
  <div class="hero-inner">
    <div class="hero-text reveal">
      <div class="hero-eyebrow">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="#00D4FF"/></svg>
        Platform Investasi Digital Terpercaya
      </div>
      <h1 class="hero-title">
        Invest Smarter,<br>
        <span class="gradient-text">Grow Faster</span>
      </h1>
      <p class="hero-desc">
        NOXARA hadir sebagai solusi investasi digital modern. Raih profit harian, bangun jaringan referral, dan wujudkan kebebasan finansial Anda bersama kami.
      </p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg btn-glow">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Mulai Sekarang
        </a>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-cyan btn-lg">
          Sudah Punya Akun? Masuk
        </a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="mining-robot-wrap">
        <svg class="hero-robot" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
          <!-- Robot Head -->
          <rect x="60" y="30" width="80" height="65" rx="14" fill="#0F1629" stroke="#00D4FF" stroke-width="2.5"/>
          <!-- Eyes -->
          <circle cx="85" cy="58" r="10" fill="#0A0E1A" stroke="#00D4FF" stroke-width="2"/>
          <circle cx="115" cy="58" r="10" fill="#0A0E1A" stroke="#7B2FFF" stroke-width="2"/>
          <circle cx="88" cy="56" r="4" fill="#00D4FF" opacity="0.9"/>
          <circle cx="118" cy="56" r="4" fill="#7B2FFF" opacity="0.9"/>
          <!-- Mouth -->
          <rect x="82" y="78" width="36" height="8" rx="4" fill="#00D4FF" opacity="0.6"/>
          <!-- Antenna -->
          <line x1="100" y1="30" x2="100" y2="14" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="100" cy="10" r="5" fill="#00D4FF"/>
          <!-- Body -->
          <rect x="50" y="100" width="100" height="65" rx="14" fill="#0F1629" stroke="#7B2FFF" stroke-width="2.5"/>
          <!-- Chest panel -->
          <rect x="68" y="112" width="64" height="40" rx="8" fill="#0A0E1A" stroke="rgba(0,212,255,0.3)" stroke-width="1.5"/>
          <rect x="75" y="120" width="22" height="6" rx="3" fill="#00D4FF" opacity="0.4"/>
          <rect x="75" y="130" width="36" height="6" rx="3" fill="#7B2FFF" opacity="0.4"/>
          <rect x="75" y="140" width="28" height="6" rx="3" fill="#00D4FF" opacity="0.3"/>
          <!-- Left Arm -->
          <rect x="20" y="105" width="28" height="55" rx="12" fill="#0F1629" stroke="#00D4FF" stroke-width="2"/>
          <!-- Right Arm -->
          <rect x="152" y="105" width="28" height="55" rx="12" fill="#0F1629" stroke="#00D4FF" stroke-width="2"/>
          <!-- Hands -->
          <circle cx="34" cy="165" r="10" fill="#0F1629" stroke="#00D4FF" stroke-width="2"/>
          <circle cx="166" cy="165" r="10" fill="#0F1629" stroke="#00D4FF" stroke-width="2"/>
          <!-- Legs -->
          <rect x="65" y="168" width="26" height="26" rx="8" fill="#0F1629" stroke="#7B2FFF" stroke-width="2"/>
          <rect x="109" y="168" width="26" height="26" rx="8" fill="#0F1629" stroke="#7B2FFF" stroke-width="2"/>
          <!-- Glow -->
          <circle cx="100" cy="100" r="95" fill="url(#glow)" opacity="0.12"/>
          <defs>
            <radialGradient id="glow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#00D4FF"/>
              <stop offset="100%" stop-color="transparent"/>
            </radialGradient>
          </defs>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- STATS SECTION -->
<section class="stats-section">
  <div class="stats-grid-land">
    <div class="reveal">
      <div class="stat-land-val" data-count-up="284750" data-suffix="+">284.750+</div>
      <div class="stat-land-label">Total Member Aktif</div>
    </div>
    <div class="reveal reveal-delay-1">
      <div class="stat-land-val">Rp15,8M+</div>
      <div class="stat-land-label">Total Payout</div>
    </div>
    <div class="reveal reveal-delay-2">
      <div class="stat-land-val">4.9<small style="font-size:18px">/5</small></div>
      <div class="stat-land-label">Rating Pengguna</div>
    </div>
    <div class="reveal reveal-delay-3">
      <div class="stat-land-val">Sejak <?= e($since) ?></div>
      <div class="stat-land-label">Beroperasi</div>
    </div>
  </div>
</section>

<!-- BANNER SLIDER -->
<?php if (!empty($banners)): ?>
<section style="padding: 40px 20px;">
  <div style="max-width:1200px;margin:0 auto">
    <div class="banner-slider" style="max-height:240px">
      <div class="banner-track">
        <?php foreach ($banners as $banner): ?>
        <div class="banner-slide">
          <?php if (!empty($banner['link_url'])): ?><a href="<?= e($banner['link_url']) ?>"><?php endif; ?>
          <img src="<?= uploadUrl($banner['image_path']) ?>" alt="<?= e($banner['title'] ?? '') ?>" style="width:100%;height:240px;object-fit:cover;border-radius:16px">
          <?php if (!empty($banner['link_url'])): ?></a><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="banner-dots">
        <?php foreach ($banners as $i => $b): ?>
        <div class="banner-dot <?= $i === 0 ? 'active' : '' ?>"></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- HOW IT WORKS -->
<section class="section-land" id="cara-kerja">
  <h2 class="section-land-title reveal">Cara Kerja NOXARA</h2>
  <p class="section-land-sub reveal reveal-delay-1">Tiga langkah mudah untuk mulai berinvestasi dan meraih profit harian</p>
  <div class="steps-grid">
    <div class="step-item-land reveal reveal-delay-1">
      <div class="step-num">1</div>
      <div class="step-title">Daftar Akun</div>
      <p class="step-desc">Buat akun gratis dalam 2 menit. Verifikasi identitas dan mulai perjalanan investasi Anda bersama NOXARA.</p>
    </div>
    <div class="step-item-land reveal reveal-delay-2">
      <div class="step-num">2</div>
      <div class="step-title">Deposit & Beli Paket</div>
      <p class="step-desc">Lakukan deposit dan pilih paket mining sesuai budget. Setiap paket memberikan profit harian yang konsisten.</p>
    </div>
    <div class="step-item-land reveal reveal-delay-3">
      <div class="step-num">3</div>
      <div class="step-title">Klaim Profit Harian</div>
      <p class="step-desc">Klaim profit setiap hari dan tarik ke rekening bank Anda. Semakin banyak paket, semakin besar penghasilan Anda.</p>
    </div>
  </div>
</section>

<!-- PRODUCT PREVIEW -->
<?php if (!empty($featuredProds)): ?>
<section class="section-land" style="padding-top:0">
  <h2 class="section-land-title reveal">Paket Mining Pilihan</h2>
  <p class="section-land-sub reveal reveal-delay-1">Pilih paket yang sesuai dengan tujuan investasi Anda</p>
  <div class="products-grid-land">
    <?php foreach ($featuredProds as $prod): ?>
    <div class="card card-glow-cyan reveal" style="text-align:center">
      <?php if (!empty($prod['image_path'])): ?>
      <img src="<?= uploadUrl($prod['image_path']) ?>" alt="<?= e($prod['name']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:10px;margin-bottom:14px">
      <?php else: ?>
      <div style="width:100%;height:100px;background:linear-gradient(135deg,rgba(0,212,255,0.1),rgba(123,47,255,0.1));border-radius:10px;margin-bottom:14px;display:flex;align-items:center;justify-content:center;font-size:32px">⛏️</div>
      <?php endif; ?>
      <h3 style="font-size:16px;margin-bottom:10px"><?= e($prod['name']) ?></h3>
      <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:#64748B">Harga</span>
          <span style="font-family:'Space Grotesk';font-weight:700;color:#00D4FF"><?= formatRupiah((float)$prod['price']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:#64748B">Profit/Hari</span>
          <span style="font-family:'Space Grotesk';font-weight:700;color:#00E676"><?= formatRupiah((float)$prod['daily_profit']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:#64748B">Durasi</span>
          <span style="font-weight:600"><?= (int)$prod['duration_days'] ?> Hari</span>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-full">Mulai Investasi</a>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>


<!-- REFERRAL SYSTEM -->
<section class="section-land" style="padding-top:0;background:rgba(15,22,41,0.4)">
  <div style="max-width:900px;margin:0 auto">
    <h2 class="section-land-title reveal">Sistem Referral Multi-Level</h2>
    <p class="section-land-sub reveal reveal-delay-1">Ajak teman dan keluarga — dapatkan komisi dari setiap transaksi jaringan Anda</p>
    <div style="overflow-x:auto">
      <table class="ref-table reveal">
        <thead>
          <tr>
            <th>Level</th>
            <th>Hubungan</th>
            <th>Komisi Deposit</th>
            <th>Komisi Pembelian Paket</th>
          </tr>
        </thead>
        <tbody>
          <tr><td><strong style="color:#00D4FF">Level 1</strong></td><td>Referral Langsung</td><td style="color:#00E676;font-weight:700">5%</td><td style="color:#00E676;font-weight:700">3%</td></tr>
          <tr><td><strong style="color:#7B2FFF">Level 2</strong></td><td>Downline Level 2</td><td style="color:#00E676;font-weight:700">3%</td><td style="color:#00E676;font-weight:700">2%</td></tr>
          <tr><td><strong style="color:#64748B">Level 3</strong></td><td>Downline Level 3</td><td style="color:#00E676;font-weight:700">1%</td><td style="color:#00E676;font-weight:700">1%</td></tr>
        </tbody>
      </table>
    </div>
    <div style="text-align:center;margin-top:28px">
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-cyan btn-lg">Bergabung &amp; Mulai Referral</a>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section-land" style="padding-top:60px">
  <h2 class="section-land-title reveal">Pertanyaan Umum</h2>
  <p class="section-land-sub reveal reveal-delay-1">Temukan jawaban atas pertanyaan yang sering ditanyakan</p>
  <div class="faq-list">
    <?php
    $faqs = [
      ['q' => 'Apakah NOXARA aman dan terpercaya?',
       'a' => 'Ya, NOXARA beroperasi dengan standar keamanan tinggi. Data pengguna dienkripsi dan setiap transaksi memerlukan verifikasi PIN. Kami telah melayani ratusan ribu anggota sejak 2024.'],
      ['q' => 'Berapa minimum deposit di NOXARA?',
       'a' => 'Minimum deposit bervariasi sesuai paket yang dipilih. Cek halaman produk untuk detail masing-masing paket investasi.'],
      ['q' => 'Bagaimana cara klaim profit harian?',
       'a' => 'Login ke akun Anda, buka menu Dashboard, dan klik tombol Klaim pada paket yang sudah bisa diklaim. Profit langsung masuk ke wallet Anda.'],
      ['q' => 'Bagaimana cara menarik saldo?',
       'a' => 'Buka menu Withdraw, masukkan jumlah dan informasi rekening bank Anda, lalu konfirmasi dengan PIN. Penarikan diproses dalam 1x24 jam kerja.'],
      ['q' => 'Apakah ada biaya pendaftaran?',
       'a' => 'Tidak ada biaya pendaftaran. Akun NOXARA dapat dibuat secara gratis. Anda hanya perlu melakukan deposit untuk mulai berinvestasi.'],
    ];
    foreach ($faqs as $i => $faq):
    ?>
    <div class="faq-item reveal">
      <div class="faq-q" onclick="toggleFaq(this)"><?= e($faq['q']) ?></div>
      <div class="faq-a"><?= e($faq['a']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA SECTION -->
<section style="padding:60px 20px;text-align:center;background:linear-gradient(135deg,rgba(0,212,255,0.06),rgba(123,47,255,0.06));border-top:1px solid rgba(255,255,255,0.05)">
  <h2 style="font-family:'Orbitron',sans-serif;font-size:clamp(20px,3vw,32px);font-weight:900;margin-bottom:14px" class="reveal">Siap Mulai Berinvestasi?</h2>
  <p style="color:#64748B;font-size:15px;margin-bottom:28px;max-width:460px;margin-left:auto;margin-right:auto" class="reveal reveal-delay-1">Bergabunglah dengan ratusan ribu member NOXARA dan raih kebebasan finansial Anda hari ini.</p>
  <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap" class="reveal reveal-delay-2">
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg btn-glow">Daftar Gratis Sekarang</a>
    <?php if ($whatsappUrl !== '#'): ?>
    <a href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Hubungi via WhatsApp
    </a>
    <?php endif; ?>
  </div>
</section>

<!-- FOOTER -->
<footer class="land-footer">
  <div class="land-footer-inner">
    <div class="land-footer-top">
      <div class="land-footer-brand">
        <div class="logo-text" style="font-family:'Orbitron',sans-serif;font-size:22px;font-weight:700;letter-spacing:3px;background:linear-gradient(135deg,#00D4FF,#7B2FFF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">NOXARA</div>
        <p>Platform investasi digital terpercaya. Invest Smarter, Grow Faster bersama NOXARA.</p>
        <div class="social-links">
          <?php if ($whatsappUrl !== '#'): ?>
          <a href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener" class="social-btn" title="WhatsApp">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><use href="#icon-whatsapp"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($telegramUrl !== '#'): ?>
          <a href="<?= e($telegramUrl) ?>" target="_blank" rel="noopener" class="social-btn" title="Telegram">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><use href="#icon-telegram"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($instagramUrl !== '#'): ?>
          <a href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener" class="social-btn" title="Instagram">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><use href="#icon-instagram"/></svg>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="land-footer-links">
        <h4>Platform</h4>
        <a href="<?= BASE_URL ?>/auth/register.php">Daftar Akun</a>
        <a href="<?= BASE_URL ?>/auth/login.php">Masuk</a>
        <a href="#cara-kerja">Cara Kerja</a>
      </div>
      <div class="land-footer-links">
        <h4>Legal</h4>
        <a href="<?= BASE_URL ?>/pages/info.php?page=terms">Syarat &amp; Ketentuan</a>
        <a href="<?= BASE_URL ?>/pages/info.php?page=privacy">Kebijakan Privasi</a>
        <a href="<?= BASE_URL ?>/pages/faq.php">FAQ</a>
        <a href="<?= BASE_URL ?>/pages/contact.php">Kontak</a>
      </div>
    </div>
    <div class="land-footer-bottom">
      <p>&copy; <?= date('Y') ?> NOXARA. All rights reserved.</p>
      <div style="display:flex;gap:16px">
        <a href="<?= BASE_URL ?>/pages/info.php?page=terms">Terms</a>
        <a href="<?= BASE_URL ?>/pages/info.php?page=privacy">Privacy</a>
      </div>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/animations.js"></script>
<script>
// FAQ Accordion
function toggleFaq(el) {
  const item = el.parentElement;
  item.classList.toggle('open');
}

// Service Worker Registration
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js').catch(() => {});
}

// Show nav links on scroll
window.addEventListener('scroll', function() {
  const nav = document.getElementById('navCara');
  if (nav) {
    if (window.scrollY > 100) { nav.style.display = ''; nav.style.opacity = '1'; }
  }
}, { passive: true });
</script>

</body>
</html>
