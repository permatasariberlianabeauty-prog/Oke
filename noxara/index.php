<?php
define('ROOT_PATH', __DIR__);
define('SKIP_INSTALL_CHECK', false);
require_once ROOT_PATH . '/config/bootstrap.php';
if (SessionManager::isLoggedIn()) redirect(BASE_URL . '/pages/dashboard.php');

$platformStats = []; $marqueeItems = []; $banners = []; $featuredProds = [];
try {
    $db = db();
    $piRows = $db->fetchAll("SELECT setting_key, setting_value FROM platform_info");
    foreach ($piRows as $r) $platformStats[$r['setting_key']] = $r['setting_value'];
    $mq = $db->fetchOne("SELECT * FROM marquee_settings LIMIT 1");
    if ($mq && $mq['is_enabled']) {
        if (!empty($mq['custom_messages'])) {
            foreach (explode("\n", $mq['custom_messages']) as $msg) {
                if (trim($msg)) $marqueeItems[] = trim($msg);
            }
        }
        if ($mq['include_deposits']) {
            $deps = $db->fetchAll("SELECT u.full_name, d.amount FROM deposits d JOIN users u ON u.id=d.user_id WHERE d.status='confirmed' ORDER BY d.confirmed_at DESC LIMIT 5");
            foreach ($deps as $d) $marqueeItems[] = maskName($d['full_name']) . ' deposit ' . formatRupiah((float)$d['amount']);
        }
        if ($mq['include_purchases']) {
            $purs = $db->fetchAll("SELECT u.full_name, p.name FROM user_products up JOIN users u ON u.id=up.user_id JOIN products p ON p.id=up.product_id ORDER BY up.created_at DESC LIMIT 5");
            foreach ($purs as $p) $marqueeItems[] = maskName($p['full_name']) . ' membeli ' . $p['name'];
        }
    }
    $banners = $db->fetchAll("SELECT * FROM banners WHERE is_active=1 ORDER BY sort_order ASC LIMIT 8");
    $featuredProds = $db->fetchAll("SELECT p.*, pc.name as cat_name FROM products p JOIN product_categories pc ON pc.id=p.category_id WHERE p.is_active=1 ORDER BY p.sort_order ASC LIMIT 3");
} catch (Exception $e) {}

$totalMembers = $platformStats['total_members'] ?? '284.750';
$totalPayout  = $platformStats['total_payout']  ?? '15800000000';
$since        = $platformStats['platform_since'] ?? '2024';
$rating       = $platformStats['platform_rating'] ?? '4.9';
try { $contact = db()->fetchOne("SELECT * FROM contact_settings LIMIT 1"); } catch(Exception $e){ $contact = []; }
$waUrl = $contact['whatsapp'] ?? '';
$tgUrl = $contact['telegram'] ?? '';
$igUrl = $contact['instagram'] ?? '';
$pageTitle = 'NOXARA — Invest Smarter, Grow Faster';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="theme-color" content="#0A0E1A">
<meta name="description" content="NOXARA — Platform investasi digital terpercaya. Profit harian, komisi referral 3 level, sistem VIP eksklusif.">
<title><?= e($pageTitle) ?></title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/mobile.css">
<style>
/* ===== LANDING PREMIUM STYLES ===== */
*,*::before,*::after{box-sizing:border-box}
body{overflow-x:hidden;background:#0A0E1A;}

/* Noise overlay */
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");pointer-events:none;z-index:0;opacity:.4;}

/* Grid lines bg */
body::after{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,212,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,212,255,0.03) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0;}

/* Particle canvas */
#nx-particles{position:fixed;inset:0;z-index:1;pointer-events:none;}

/* Topbar */
.land-topbar{position:fixed;top:0;left:0;right:0;z-index:200;height:64px;display:flex;align-items:center;background:rgba(10,14,26,0.85);backdrop-filter:blur(24px) saturate(180%);-webkit-backdrop-filter:blur(24px) saturate(180%);border-bottom:1px solid rgba(255,255,255,0.06);box-shadow:0 1px 0 rgba(0,212,255,0.08),0 4px 30px rgba(0,0,0,0.4);}
.land-topbar-inner{max-width:1200px;margin:0 auto;padding:0 20px;width:100%;display:flex;align-items:center;justify-content:space-between;}
.land-logo{font-family:'Orbitron',monospace;font-size:22px;font-weight:900;letter-spacing:4px;background:linear-gradient(135deg,#00D4FF,#7B2FFF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 12px rgba(0,212,255,0.5));text-decoration:none;}
.land-nav{display:flex;align-items:center;gap:10px;}

/* Marquee */
.land-marquee{margin-top:64px;background:linear-gradient(90deg,rgba(0,212,255,0.06),rgba(123,47,255,0.06),rgba(0,212,255,0.06));border-top:1px solid rgba(0,212,255,0.15);border-bottom:1px solid rgba(123,47,255,0.1);height:38px;display:flex;align-items:center;overflow:hidden;position:relative;z-index:2;}
.land-marquee::before,.land-marquee::after{content:'';position:absolute;top:0;width:80px;height:100%;z-index:3;}
.land-marquee::before{left:0;background:linear-gradient(90deg,#0A0E1A,transparent);}
.land-marquee::after{right:0;background:linear-gradient(-90deg,#0A0E1A,transparent);}
.land-marquee-inner{display:flex;align-items:center;animation:marquee 30s linear infinite;white-space:nowrap;color:#00D4FF;font-size:12px;font-weight:600;letter-spacing:.5px;}
.land-marquee-sep{margin:0 20px;opacity:.4;}

/* Hero */
.land-hero{position:relative;z-index:2;min-height:100vh;display:flex;align-items:center;padding:80px 20px 60px;}
.land-hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse at 20% 60%,rgba(0,212,255,0.1) 0%,transparent 55%),radial-gradient(ellipse at 80% 30%,rgba(123,47,255,0.12) 0%,transparent 55%);pointer-events:none;}
.land-hero-inner{max-width:1200px;margin:0 auto;width:100%;display:flex;align-items:center;gap:60px;}
.land-hero-text{flex:1;position:relative;}

/* Eyebrow */
.hero-eyebrow{display:inline-flex;align-items:center;gap:8px;background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.25);color:#00D4FF;font-size:11px;font-weight:700;letter-spacing:2.5px;padding:7px 16px;border-radius:30px;margin-bottom:22px;text-transform:uppercase;animation:glow-pulse 3s ease-in-out infinite;}
.hero-eyebrow svg{animation:antenna-pulse 1.5s ease-in-out infinite;}

/* Hero title */
.hero-h1{font-family:'Orbitron',monospace;font-size:clamp(30px,5.5vw,58px);font-weight:900;line-height:1.08;margin-bottom:18px;letter-spacing:-1px;}
.hero-h1 .line1{color:#F1F5F9;display:block;}
.hero-h1 .gradient-text{background:linear-gradient(135deg,#00D4FF 0%,#7B2FFF 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:block;filter:drop-shadow(0 0 30px rgba(0,212,255,0.3));}
.hero-desc{font-size:16px;color:#94A3B8;line-height:1.75;margin-bottom:36px;max-width:500px;}

/* Hero CTA */
.hero-cta{display:flex;gap:14px;flex-wrap:wrap;}

/* Hero Visual */
.land-hero-visual{flex:0 0 380px;display:flex;align-items:center;justify-content:center;position:relative;}
.robot-glow-ring{position:absolute;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(0,212,255,0.12) 0%,rgba(123,47,255,0.08) 40%,transparent 70%);animation:glow-pulse 3s ease-in-out infinite;}
.robot-orbit{position:absolute;width:280px;height:280px;border-radius:50%;border:1px dashed rgba(0,212,255,0.2);animation:spin 20s linear infinite;}
.robot-orbit::before{content:'';position:absolute;top:-4px;left:50%;width:8px;height:8px;background:#00D4FF;border-radius:50%;box-shadow:0 0 10px #00D4FF;transform:translateX(-50%);}
.robot-svg-wrap{position:relative;z-index:2;animation:float 3.5s ease-in-out infinite;filter:drop-shadow(0 0 30px rgba(0,212,255,0.25));}
.robot-svg-wrap svg{width:240px;height:240px;}

/* Floating coins */
.float-coin{position:absolute;font-size:20px;opacity:0;animation:coin-orbit 6s ease-in-out infinite;}
.float-coin:nth-child(1){top:10%;right:5%;animation-delay:0s;}
.float-coin:nth-child(2){bottom:20%;left:0%;animation-delay:2s;}
.float-coin:nth-child(3){top:50%;right:-5%;animation-delay:4s;}

/* Stats section */
.land-stats{position:relative;z-index:2;padding:64px 20px;background:linear-gradient(135deg,rgba(15,22,41,0.8),rgba(13,21,40,0.9));border-top:1px solid rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.05);}
.land-stats-grid{max-width:900px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:32px;text-align:center;}
.land-stat-item{position:relative;padding:20px 10px;}
.land-stat-item::after{content:'';position:absolute;right:0;top:20%;height:60%;width:1px;background:linear-gradient(to bottom,transparent,rgba(255,255,255,0.1),transparent);}
.land-stat-item:last-child::after{display:none;}
.land-stat-num{font-family:'Space Grotesk',sans-serif;font-size:36px;font-weight:700;color:#00D4FF;line-height:1;text-shadow:0 0 20px rgba(0,212,255,0.3);}
.land-stat-num.purple{color:#7B2FFF;text-shadow:0 0 20px rgba(123,47,255,0.3);}
.land-stat-num.gold{color:#FFD700;text-shadow:0 0 20px rgba(255,215,0,0.3);}
.land-stat-num.green{color:#00E676;text-shadow:0 0 20px rgba(0,230,118,0.3);}
.land-stat-label{font-size:13px;color:#64748B;margin-top:8px;font-weight:500;}

/* Section wrapper */
.land-section{position:relative;z-index:2;padding:80px 20px;}
.land-section-inner{max-width:1200px;margin:0 auto;}
.land-section-title{font-size:clamp(24px,3vw,38px);font-weight:800;text-align:center;margin-bottom:10px;color:#F1F5F9;}
.land-section-sub{text-align:center;color:#64748B;font-size:15px;margin-bottom:52px;max-width:600px;margin-left:auto;margin-right:auto;}

/* Steps */
.land-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;position:relative;}
.land-steps::before{content:'';position:absolute;top:36px;left:calc(16.67% + 36px);right:calc(16.67% + 36px);height:2px;background:linear-gradient(90deg,#00D4FF,#7B2FFF);opacity:.3;}
.land-step{background:linear-gradient(135deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.07);border-radius:20px;padding:32px 24px 28px;text-align:center;position:relative;transition:all .3s;}
.land-step:hover{transform:translateY(-4px);border-color:rgba(0,212,255,0.2);box-shadow:0 12px 40px rgba(0,0,0,0.3),0 0 0 1px rgba(0,212,255,0.1);}
.land-step::before{content:'';position:absolute;inset:0;border-radius:20px;background:linear-gradient(135deg,rgba(0,212,255,0.03),transparent);opacity:0;transition:opacity .3s;}
.land-step:hover::before{opacity:1;}
.step-num-badge{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#00D4FF,#7B2FFF);display:flex;align-items:center;justify-content:center;font-family:'Orbitron',monospace;font-weight:700;font-size:18px;color:#fff;margin:0 auto 20px;box-shadow:0 0 20px rgba(0,212,255,0.4),0 4px 16px rgba(0,0,0,0.3);}
.step-title{font-size:17px;font-weight:700;margin-bottom:10px;color:#F1F5F9;}
.step-desc{font-size:13px;color:#64748B;line-height:1.65;}

/* Products */
.land-products{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
.land-product-card{background:linear-gradient(135deg,rgba(255,255,255,0.04),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.08);border-radius:20px;overflow:hidden;transition:all .3s;position:relative;}
.land-product-card:hover{transform:translateY(-6px);border-color:rgba(0,212,255,0.3);box-shadow:0 20px 50px rgba(0,0,0,0.4),0 0 0 1px rgba(0,212,255,0.15),0 0 40px rgba(0,212,255,0.05);}
.land-product-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#00D4FF,#7B2FFF,transparent);}
.land-prod-header{padding:24px 20px 16px;background:linear-gradient(135deg,rgba(0,212,255,0.06),rgba(123,47,255,0.04));}
.land-prod-icon{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,rgba(0,212,255,0.15),rgba(123,47,255,0.15));border:1px solid rgba(0,212,255,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:12px;font-size:24px;}
.land-prod-name{font-size:18px;font-weight:800;color:#F1F5F9;margin-bottom:4px;}
.land-prod-cat{font-size:11px;color:#00D4FF;font-weight:600;letter-spacing:1px;text-transform:uppercase;}
.land-prod-body{padding:16px 20px 20px;}
.land-prod-stat{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);}
.land-prod-stat:last-of-type{border-bottom:none;}
.land-prod-stat-label{font-size:12px;color:#64748B;}
.land-prod-stat-val{font-family:'Space Grotesk',sans-serif;font-size:14px;font-weight:700;color:#00D4FF;}
.land-prod-stat-val.green{color:#00E676;}

/* Referral section */
.land-ref-section{background:linear-gradient(135deg,rgba(0,212,255,0.04),rgba(123,47,255,0.04));border:1px solid rgba(255,255,255,0.06);border-radius:24px;padding:48px 40px;margin-top:0;}
.land-ref-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;}
.ref-comm-table{width:100%;border-collapse:collapse;}
.ref-comm-table th{background:rgba(0,212,255,0.08);color:#00D4FF;font-size:11px;font-weight:700;letter-spacing:1.5px;padding:12px 16px;text-align:left;border-bottom:1px solid rgba(0,212,255,0.15);}
.ref-comm-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:14px;color:#94A3B8;}
.ref-comm-table tr:last-child td{border-bottom:none;}
.ref-badge-l1{color:#00D4FF;font-weight:700;}
.ref-badge-l2{color:#7B2FFF;font-weight:700;}
.ref-badge-l3{color:#64748B;font-weight:700;}
.ref-pct{font-family:'Space Grotesk',sans-serif;font-weight:700;color:#00E676;}
.ref-visual{display:flex;flex-direction:column;align-items:center;gap:0;}
.ref-tree-node{display:flex;flex-direction:column;align-items:center;position:relative;width:100%;}
.ref-tree-node-box{background:linear-gradient(135deg,rgba(0,212,255,0.1),rgba(123,47,255,0.1));border:1px solid rgba(0,212,255,0.25);border-radius:12px;padding:10px 20px;font-size:13px;font-weight:600;color:#F1F5F9;white-space:nowrap;position:relative;}
.ref-tree-node-box.me{background:linear-gradient(135deg,rgba(0,212,255,0.2),rgba(123,47,255,0.2));border-color:rgba(0,212,255,0.5);box-shadow:0 0 20px rgba(0,212,255,0.2);}
.ref-connector{width:2px;height:24px;background:linear-gradient(to bottom,#00D4FF,#7B2FFF);margin:0 auto;}
.ref-level-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;width:100%;}
.ref-level-box{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:8px 10px;text-align:center;font-size:12px;color:#64748B;}
.ref-level-box .pct{display:block;font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700;color:#00E676;margin-bottom:2px;}

/* FAQ */
.land-faq-list{max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:12px;}
.land-faq-item{background:linear-gradient(135deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;transition:border-color .2s;}
.land-faq-item:hover,.land-faq-item.open{border-color:rgba(0,212,255,0.2);}
.land-faq-q{padding:18px 20px;font-weight:600;font-size:14px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;color:#F1F5F9;gap:12px;}
.land-faq-icon{width:28px;height:28px;border-radius:8px;background:rgba(0,212,255,0.1);border:1px solid rgba(0,212,255,0.2);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#00D4FF;font-size:18px;font-weight:300;transition:all .2s;}
.land-faq-item.open .land-faq-icon{background:rgba(0,212,255,0.2);transform:rotate(45deg);}
.land-faq-a{max-height:0;overflow:hidden;transition:max-height .35s ease;font-size:14px;color:#64748B;line-height:1.7;}
.land-faq-item.open .land-faq-a{max-height:200px;}
.land-faq-a-inner{padding:0 20px 18px;}

/* CTA section */
.land-cta{position:relative;z-index:2;padding:80px 20px;text-align:center;overflow:hidden;}
.land-cta::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(0,212,255,0.08) 0%,rgba(123,47,255,0.06) 40%,transparent 70%);pointer-events:none;}
.land-cta-title{font-family:'Orbitron',monospace;font-size:clamp(22px,3.5vw,40px);font-weight:900;margin-bottom:16px;background:linear-gradient(135deg,#F1F5F9,#00D4FF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.land-cta-sub{font-size:15px;color:#64748B;margin-bottom:36px;max-width:500px;margin-left:auto;margin-right:auto;}
.land-cta-btns{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;}

/* Footer */
.land-footer{position:relative;z-index:2;background:rgba(13,21,40,0.95);border-top:1px solid rgba(255,255,255,0.06);padding:56px 20px 32px;}
.land-footer-inner{max-width:1200px;margin:0 auto;}
.land-footer-top{display:flex;gap:48px;flex-wrap:wrap;margin-bottom:40px;}
.land-footer-brand{flex:1;min-width:220px;}
.land-footer-brand .brand-logo{font-family:'Orbitron',monospace;font-size:24px;font-weight:900;letter-spacing:4px;background:linear-gradient(135deg,#00D4FF,#7B2FFF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:block;margin-bottom:12px;filter:drop-shadow(0 0 10px rgba(0,212,255,0.3));}
.land-footer-brand p{color:#64748B;font-size:13px;line-height:1.65;max-width:260px;margin-bottom:20px;}
.land-footer-socials{display:flex;gap:10px;}
.social-icon-btn{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;color:#64748B;transition:all .2s;text-decoration:none;}
.social-icon-btn:hover{background:rgba(0,212,255,0.1);border-color:rgba(0,212,255,0.3);color:#00D4FF;transform:translateY(-2px);}
.land-footer-col h5{font-size:11px;font-weight:700;letter-spacing:2px;color:#475569;text-transform:uppercase;margin-bottom:16px;}
.land-footer-col a{display:block;color:#64748B;font-size:13px;margin-bottom:10px;text-decoration:none;transition:color .2s;}
.land-footer-col a:hover{color:#00D4FF;}
.land-footer-bottom{border-top:1px solid rgba(255,255,255,0.05);padding-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.land-footer-bottom p{font-size:12px;color:#475569;}

/* Responsive */
@media(max-width:960px){
.land-hero-inner{flex-direction:column;text-align:center;gap:40px;}
.land-hero-visual{flex:none;}
.hero-cta{justify-content:center;}
.hero-desc{margin:0 auto 32px;}
.land-stats-grid{grid-template-columns:repeat(2,1fr);gap:20px;}
.land-steps{grid-template-columns:1fr;gap:16px;}
.land-steps::before{display:none;}
.land-products{grid-template-columns:1fr 1fr;}
.land-ref-grid{grid-template-columns:1fr;gap:28px;}
.land-footer-top{gap:28px;}
}
@media(max-width:600px){
.land-stats-grid{grid-template-columns:1fr 1fr;gap:12px;}
.land-products{grid-template-columns:1fr;}
.land-hero-visual{display:none;}
.land-footer-top{flex-direction:column;}
.land-section{padding:56px 16px;}
}

/* Keyframe for coin orbit */
@keyframes coin-orbit{0%,100%{opacity:0;transform:translateY(0) scale(.8);}30%,70%{opacity:.8;transform:translateY(-20px) scale(1);}50%{opacity:1;transform:translateY(-35px) scale(1.1);}}
@keyframes border-glow-anim{0%,100%{box-shadow:0 0 0 0 rgba(0,212,255,0);}50%{box-shadow:0 0 20px 4px rgba(0,212,255,0.2);}}
.btn-primary:not(:disabled){animation:border-glow-anim 3s ease-in-out infinite;}
</style>
</head>
<body>

<!-- Particle Canvas -->
<canvas id="nx-particles"></canvas>

<!-- Toast Container -->
<div id="toast-container" aria-live="polite"></div>

<!-- ============ TOPBAR ============ -->
<header class="land-topbar">
  <div class="land-topbar-inner">
    <a href="<?= BASE_URL ?>/" class="land-logo">NOXARA</a>
    <nav class="land-nav">
      <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-ghost btn-sm">Masuk</a>
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
        Daftar Gratis
      </a>
    </nav>
  </div>
</header>

<!-- ============ MARQUEE ============ -->
<?php if (!empty($marqueeItems)): ?>
<div class="land-marquee">
  <div class="land-marquee-inner">
    <?php foreach ($marqueeItems as $item): ?>
      <span>⚡ <?= e($item) ?></span>
      <span class="land-marquee-sep">◆</span>
    <?php endforeach; ?>
    <?php foreach ($marqueeItems as $item): ?>
      <span>⚡ <?= e($item) ?></span>
      <span class="land-marquee-sep">◆</span>
    <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="land-marquee">
  <div class="land-marquee-inner">
    <span>⚡ Selamat datang di NOXARA</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Profit harian langsung masuk saldo</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Komisi referral 3 level</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Daftar gratis, mulai investasi sekarang</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Selamat datang di NOXARA</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Profit harian langsung masuk saldo</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Komisi referral 3 level</span><span class="land-marquee-sep">◆</span>
    <span>⚡ Daftar gratis, mulai investasi sekarang</span><span class="land-marquee-sep">◆</span>
  </div>
</div>
<?php endif; ?>

<!-- ============ HERO ============ -->
<section class="land-hero">
  <div class="land-hero-bg"></div>
  <div class="land-hero-inner">

    <!-- Text -->
    <div class="land-hero-text">
      <div class="hero-eyebrow">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#00D4FF" stroke-width="2"/><path d="M12 6v6l4 2" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
        Platform Investasi Digital Terpercaya
      </div>
      <h1 class="hero-h1">
        <span class="line1">Invest Smarter,</span>
        <span class="gradient-text">Grow Faster</span>
      </h1>
      <p class="hero-desc">
        NOXARA hadir sebagai platform investasi digital paling transparan dan menguntungkan. Raih profit harian, bangun jaringan referral 3 level, dan capai level VIP tertinggi.
      </p>
      <div class="hero-cta">
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg" style="min-height:54px;font-size:16px;letter-spacing:.5px;box-shadow:0 0 30px rgba(0,212,255,0.4),0 6px 20px rgba(0,0,0,0.4);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Mulai Berinvestasi
        </a>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-cyan btn-lg" style="min-height:54px;font-size:16px;">
          Sudah Punya Akun
        </a>
      </div>

      <!-- Trust badges -->
      <div style="display:flex;align-items:center;gap:20px;margin-top:32px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#64748B;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2L3 7l9 5 9-5-9-5zM3 12l9 5 9-5M3 17l9 5 9-5" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span>Keamanan Berlapis</span>
        </div>
        <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#64748B;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00E676" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span>Profit Harian Terjamin</span>
        </div>
        <div style="display:flex;align-items:center;gap:7px;font-size:12px;color:#64748B;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#7B2FFF" stroke-width="2"/><path d="M12 8v4l2 2" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg>
          <span>Proses Cepat 24 Jam</span>
        </div>
      </div>
    </div>

    <!-- Visual / Robot -->
    <div class="land-hero-visual">
      <div class="robot-glow-ring"></div>
      <div class="robot-orbit"></div>
      <!-- Floating coins -->
      <div class="float-coin" style="top:8%;right:2%">💰</div>
      <div class="float-coin" style="bottom:15%;left:-2%;animation-delay:2s">⚡</div>
      <div class="float-coin" style="top:55%;right:-3%;animation-delay:4s">💎</div>

      <div class="robot-svg-wrap">
        <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <radialGradient id="rg1" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#00D4FF" stop-opacity=".3"/><stop offset="100%" stop-color="#7B2FFF" stop-opacity="0"/></radialGradient>
            <radialGradient id="eyeGrad" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#00D4FF"/><stop offset="100%" stop-color="#0066AA"/></radialGradient>
            <radialGradient id="eyeGrad2" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#7B2FFF"/><stop offset="100%" stop-color="#4400AA"/></radialGradient>
            <filter id="glow-f"><feGaussianBlur stdDeviation="2.5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
          </defs>
          <!-- Glow halo -->
          <circle cx="100" cy="100" r="96" fill="url(#rg1)" opacity=".5"/>
          <!-- Antenna -->
          <line x1="100" y1="32" x2="100" y2="14" stroke="#00D4FF" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="100" cy="10" r="6" fill="#00D4FF" filter="url(#glow-f)"><animate attributeName="r" values="5;8;5" dur="1.5s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0.4;1" dur="1.5s" repeatCount="indefinite"/></circle>
          <!-- Head -->
          <rect x="55" y="32" width="90" height="70" rx="16" fill="#0D1528" stroke="#00D4FF" stroke-width="2"/>
          <rect x="55" y="32" width="90" height="4" rx="2" fill="url(#eyeGrad)" opacity=".6"/>
          <!-- Eye L -->
          <circle cx="82" cy="64" r="12" fill="#0A0E1A" stroke="#00D4FF" stroke-width="1.5"/>
          <circle cx="82" cy="64" r="7" fill="url(#eyeGrad)" filter="url(#glow-f)"><animate attributeName="opacity" values="1;0.3;1" dur="2.5s" repeatCount="indefinite"/></circle>
          <circle cx="84" cy="62" r="2" fill="white" opacity=".8"/>
          <!-- Eye R -->
          <circle cx="118" cy="64" r="12" fill="#0A0E1A" stroke="#7B2FFF" stroke-width="1.5"/>
          <circle cx="118" cy="64" r="7" fill="url(#eyeGrad2)" filter="url(#glow-f)"><animate attributeName="opacity" values="1;0.3;1" dur="2.5s" begin=".8s" repeatCount="indefinite"/></circle>
          <circle cx="120" cy="62" r="2" fill="white" opacity=".8"/>
          <!-- Mouth -->
          <rect x="80" y="84" width="40" height="8" rx="4" fill="#00D4FF" opacity=".5"><animate attributeName="opacity" values=".5;.9;.5" dur="3s" repeatCount="indefinite"/></rect>
          <!-- Body -->
          <rect x="45" y="108" width="110" height="68" rx="16" fill="#0D1528" stroke="#7B2FFF" stroke-width="2"/>
          <!-- Chest display -->
          <rect x="65" y="118" width="70" height="45" rx="10" fill="#080F1E" stroke="rgba(0,212,255,.2)" stroke-width="1.5"/>
          <rect x="72" y="126" width="25" height="5" rx="2.5" fill="#00D4FF" opacity=".5"><animate attributeName="width" values="25;40;25" dur="2s" repeatCount="indefinite"/></rect>
          <rect x="72" y="135" width="40" height="5" rx="2.5" fill="#7B2FFF" opacity=".4"><animate attributeName="width" values="40;20;40" dur="2.5s" repeatCount="indefinite"/></rect>
          <rect x="72" y="144" width="30" height="5" rx="2.5" fill="#00D4FF" opacity=".3"><animate attributeName="width" values="30;45;30" dur="3s" repeatCount="indefinite"/></rect>
          <!-- Left arm -->
          <rect x="18" y="112" width="25" height="52" rx="12" fill="#0D1528" stroke="#00D4FF" stroke-width="1.5"><animateTransform attributeName="transform" type="rotate" values="-8 30 138;8 30 138;-8 30 138" dur="2s" repeatCount="indefinite"/></rect>
          <circle cx="30" cy="168" r="9" fill="#0D1528" stroke="#00D4FF" stroke-width="1.5"/>
          <!-- Right arm -->
          <rect x="157" y="112" width="25" height="52" rx="12" fill="#0D1528" stroke="#00D4FF" stroke-width="1.5"><animateTransform attributeName="transform" type="rotate" values="8 170 138;-8 170 138;8 170 138" dur="2s" begin=".5s" repeatCount="indefinite"/></rect>
          <circle cx="170" cy="168" r="9" fill="#0D1528" stroke="#00D4FF" stroke-width="1.5"/>
          <!-- Legs -->
          <rect x="62" y="178" width="28" height="20" rx="8" fill="#0D1528" stroke="#7B2FFF" stroke-width="1.5"/>
          <rect x="110" y="178" width="28" height="20" rx="8" fill="#0D1528" stroke="#7B2FFF" stroke-width="1.5"/>
          <!-- Coins floating from robot -->
          <g filter="url(#glow-f)">
            <circle cx="148" cy="90" r="6" fill="#FFD700" opacity=".7"><animate attributeName="cy" values="90;70;90" dur="3s" repeatCount="indefinite"/><animate attributeName="opacity" values=".7;0;.7" dur="3s" repeatCount="indefinite"/></circle>
            <text x="145" y="94" font-size="8" fill="#0A0E1A" font-weight="bold">$</text>
          </g>
          <g filter="url(#glow-f)">
            <circle cx="52" cy="100" r="5" fill="#00D4FF" opacity=".6"><animate attributeName="cy" values="100;78;100" dur="2.5s" begin="1s" repeatCount="indefinite"/><animate attributeName="opacity" values=".6;0;.6" dur="2.5s" begin="1s" repeatCount="indefinite"/></circle>
          </g>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- ============ STATS ============ -->
<section class="land-stats">
  <div class="land-stats-grid">
    <div>
      <div class="land-stat-num" id="statMembers"><?= number_format((int)$totalMembers) ?>+</div>
      <div class="land-stat-label">Total Member Aktif</div>
    </div>
    <div>
      <div class="land-stat-num purple">Rp <?= number_format((float)$totalPayout / 1000000, 1) ?>M+</div>
      <div class="land-stat-label">Total Payout</div>
    </div>
    <div>
      <div class="land-stat-num gold"><?= e($rating) ?><span style="font-size:18px">/5</span></div>
      <div class="land-stat-label">Rating Pengguna</div>
    </div>
    <div>
      <div class="land-stat-num green">Sejak <?= e($since) ?></div>
      <div class="land-stat-label">Beroperasi</div>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="land-section" id="cara-kerja">
  <div class="land-section-inner">
    <h2 class="land-section-title">Cara Kerja NOXARA</h2>
    <p class="land-section-sub">Tiga langkah mudah memulai perjalanan investasi digital Anda</p>
    <div class="land-steps">
      <div class="land-step">
        <div class="step-num-badge">1</div>
        <div class="step-title">Daftar Akun Gratis</div>
        <p class="step-desc">Buat akun dalam 2 menit. Isi data diri, verifikasi, dan dapatkan saldo gratis langsung sebagai hadiah pendaftaran.</p>
      </div>
      <div class="land-step">
        <div class="step-num-badge">2</div>
        <div class="step-title">Deposit & Beli Paket</div>
        <p class="step-desc">Pilih paket mining sesuai budget Anda. Mulai dari paket STONE I hingga GOLD IV dengan ROI kompetitif.</p>
      </div>
      <div class="land-step">
        <div class="step-num-badge">3</div>
        <div class="step-title">Klaim Profit Setiap Hari</div>
        <p class="step-desc">Klik tombol klaim setiap hari dan lihat saldo Anda berkembang. Tarik ke rekening bank kapanpun Anda mau.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ PRODUCTS ============ -->
<?php if (!empty($featuredProds)): ?>
<section class="land-section" style="padding-top:0">
  <div class="land-section-inner">
    <h2 class="land-section-title">Paket Mining Pilihan</h2>
    <p class="land-section-sub">Paket investasi dengan profit harian konsisten dan modal terjamin kembali</p>
    <div class="land-products">
      <?php
      $prodIcons = ['⛏️','🔩','💎'];
      foreach ($featuredProds as $idx => $prod):
        $roi = (float)$prod['price'] > 0 ? round(((float)$prod['profit_per_day'] * (int)$prod['duration_days'] / (float)$prod['price']) * 100, 1) : 0;
      ?>
      <div class="land-product-card">
        <div class="land-prod-header">
          <div class="land-prod-icon"><?= $prodIcons[$idx % 3] ?></div>
          <div class="land-prod-name"><?= e($prod['name']) ?></div>
          <div class="land-prod-cat"><?= e($prod['cat_name'] ?? '') ?></div>
        </div>
        <div class="land-prod-body">
          <div class="land-prod-stat">
            <span class="land-prod-stat-label">Harga Paket</span>
            <span class="land-prod-stat-val"><?= formatRupiah((float)$prod['price']) ?></span>
          </div>
          <div class="land-prod-stat">
            <span class="land-prod-stat-label">Profit / Hari</span>
            <span class="land-prod-stat-val green">+<?= formatRupiah((float)$prod['profit_per_day']) ?></span>
          </div>
          <div class="land-prod-stat">
            <span class="land-prod-stat-label">Durasi</span>
            <span class="land-prod-stat-val"><?= (int)$prod['duration_days'] ?> Hari</span>
          </div>
          <div class="land-prod-stat" style="border-bottom:none;">
            <span class="land-prod-stat-label">Total ROI</span>
            <span class="land-prod-stat-val" style="color:#FFD700;"><?= $roi ?>%</span>
          </div>
          <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-full" style="margin-top:16px;min-height:48px;">
            Mulai Investasi
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:28px">
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-cyan" style="font-size:14px;">
        Lihat Semua Paket →
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ REFERRAL ============ -->
<section class="land-section" style="padding-top:0">
  <div class="land-section-inner">
    <h2 class="land-section-title">Komisi Referral 3 Level</h2>
    <p class="land-section-sub">Ajak teman dan dapatkan komisi otomatis dari setiap transaksi jaringan Anda</p>
    <div class="land-ref-section">
      <div class="land-ref-grid">
        <div>
          <table class="ref-comm-table">
            <thead>
              <tr>
                <th>Level</th>
                <th>Komisi Deposit</th>
                <th>Komisi Paket</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="ref-badge-l1">Level 1</span><br><small style="color:#64748B;font-size:11px">Referral langsung</small></td>
                <td><span class="ref-pct">2%</span></td>
                <td><span class="ref-pct">3%</span></td>
              </tr>
              <tr>
                <td><span class="ref-badge-l2">Level 2</span><br><small style="color:#64748B;font-size:11px">Downline L2</small></td>
                <td><span class="ref-pct">1%</span></td>
                <td><span class="ref-pct">1.5%</span></td>
              </tr>
              <tr>
                <td><span class="ref-badge-l3">Level 3</span><br><small style="color:#64748B;font-size:11px">Downline L3</small></td>
                <td><span class="ref-pct">0.5%</span></td>
                <td><span class="ref-pct">0.75%</span></td>
              </tr>
            </tbody>
          </table>
          <div style="margin-top:20px">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-cyan" style="width:100%;min-height:48px;">
              Mulai Referral Sekarang →
            </a>
          </div>
        </div>
        <!-- Tree visual -->
        <div class="ref-visual">
          <div class="ref-tree-node">
            <div class="ref-tree-node-box me">🧑 Kamu</div>
          </div>
          <div class="ref-connector"></div>
          <div class="ref-level-row">
            <div class="ref-level-box"><span class="pct">2%</span>Teman A</div>
            <div class="ref-level-box"><span class="pct">2%</span>Teman B</div>
            <div class="ref-level-box"><span class="pct">2%</span>Teman C</div>
          </div>
          <div class="ref-connector" style="height:16px"></div>
          <div class="ref-level-row" style="grid-template-columns:repeat(3,1fr)">
            <div class="ref-level-box"><span class="pct" style="font-size:14px">1%</span>L2</div>
            <div class="ref-level-box"><span class="pct" style="font-size:14px">1%</span>L2</div>
            <div class="ref-level-box"><span class="pct" style="font-size:14px">1%</span>L2</div>
          </div>
          <div class="ref-connector" style="height:16px"></div>
          <div class="ref-level-row" style="grid-template-columns:repeat(3,1fr)">
            <div class="ref-level-box" style="opacity:.7"><span class="pct" style="font-size:12px">.5%</span>L3</div>
            <div class="ref-level-box" style="opacity:.7"><span class="pct" style="font-size:12px">.5%</span>L3</div>
            <div class="ref-level-box" style="opacity:.7"><span class="pct" style="font-size:12px">.5%</span>L3</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="land-section" style="padding-top:0">
  <div class="land-section-inner">
    <h2 class="land-section-title">Pertanyaan Umum</h2>
    <p class="land-section-sub">Semua yang perlu Anda ketahui tentang NOXARA</p>
    <div class="land-faq-list">
      <?php
      $faqs = [
        ['q'=>'Apakah NOXARA aman dan terpercaya?','a'=>'Ya. NOXARA menggunakan enkripsi SSL, PIN 6 digit untuk setiap penarikan, dan sistem audit ledger yang transparan. Semua transaksi tercatat dan bisa diverifikasi.'],
        ['q'=>'Berapa minimum deposit di NOXARA?','a'=>'Deposit minimum mulai dari Rp 50.000 dengan paket STONE I. Anda bisa memulai investasi dengan modal terjangkau dan meningkatkan paket seiring waktu.'],
        ['q'=>'Kapan profit harian bisa diklaim?','a'=>'Profit bisa diklaim setiap hari setelah pukul 00:00 WIB. Cukup buka dashboard dan klik tombol Klaim Profit. Saldo langsung masuk ke wallet utama Anda.'],
        ['q'=>'Berapa lama proses penarikan?','a'=>'Penarikan diproses pada jam 08:00–20:00 WIB dan biasanya selesai dalam 1–6 jam kerja. Biaya admin sesuai level VIP (1–15%).'],
        ['q'=>'Bagaimana cara kerja sistem referral?','a'=>'Bagikan link referral unik Anda. Setiap kali downline melakukan deposit atau beli paket, Anda otomatis mendapat komisi hingga 3 level tanpa batas.'],
      ];
      foreach ($faqs as $i => $faq):
      ?>
      <div class="land-faq-item" id="faq<?= $i ?>">
        <div class="land-faq-q" onclick="toggleFaq(<?= $i ?>)">
          <span><?= e($faq['q']) ?></span>
          <div class="land-faq-icon">+</div>
        </div>
        <div class="land-faq-a"><div class="land-faq-a-inner"><?= e($faq['a']) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="land-cta">
  <h2 class="land-cta-title">Siap Mulai Berinvestasi?</h2>
  <p class="land-cta-sub">Bergabunglah dengan lebih dari 284.000+ member aktif NOXARA dan raih kebebasan finansial Anda sekarang.</p>
  <div class="land-cta-btns">
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg" style="min-height:54px;font-size:16px;box-shadow:0 0 40px rgba(0,212,255,0.4),0 8px 24px rgba(0,0,0,0.5);">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      Daftar Gratis Sekarang
    </a>
    <?php if (!empty($waUrl)): ?>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$waUrl) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg" style="min-height:54px;font-size:16px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.168-.006-.36-.009-.57-.009a1.104 1.104 0 00-.792.372c-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zm-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884zm8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Hubungi via WhatsApp
    </a>
    <?php endif; ?>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="land-footer">
  <div class="land-footer-inner">
    <div class="land-footer-top">
      <div class="land-footer-brand">
        <a href="<?= BASE_URL ?>/" class="brand-logo">NOXARA</a>
        <p>Platform investasi digital terpercaya. Invest Smarter, Grow Faster — bersama lebih dari 284.000 member aktif.</p>
        <div class="land-footer-socials">
          <?php if (!empty($waUrl)): ?>
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$waUrl) ?>" target="_blank" rel="noopener" class="social-icon-btn" title="WhatsApp">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.168-.006-.36-.009-.57-.009a1.104 1.104 0 00-.792.372c-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zm-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884zm8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
          <?php endif; ?>
          <?php if (!empty($tgUrl)): ?>
          <a href="https://t.me/<?= ltrim(e($tgUrl),'@') ?>" target="_blank" rel="noopener" class="social-icon-btn" title="Telegram">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
          </a>
          <?php endif; ?>
          <?php if (!empty($igUrl)): ?>
          <a href="https://instagram.com/<?= ltrim(e($igUrl),'@') ?>" target="_blank" rel="noopener" class="social-icon-btn" title="Instagram">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="land-footer-col">
        <h5>Platform</h5>
        <a href="<?= BASE_URL ?>/auth/register.php">Daftar Akun</a>
        <a href="<?= BASE_URL ?>/auth/login.php">Masuk</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="<?= BASE_URL ?>/pages/faq.php">FAQ</a>
      </div>
      <div class="land-footer-col">
        <h5>Legal</h5>
        <a href="<?= BASE_URL ?>/pages/info.php?tab=syarat-ketentuan">Syarat & Ketentuan</a>
        <a href="<?= BASE_URL ?>/pages/info.php?tab=kebijakan-privasi">Kebijakan Privasi</a>
        <a href="<?= BASE_URL ?>/pages/info.php?tab=kebijakan-withdraw">Kebijakan Withdraw</a>
        <a href="<?= BASE_URL ?>/pages/contact.php">Kontak</a>
      </div>
    </div>
    <div class="land-footer-bottom">
      <p>&copy; <?= date('Y') ?> NOXARA. All rights reserved. Platform investasi digital terpercaya.</p>
      <div style="display:flex;gap:16px;">
        <a href="<?= BASE_URL ?>/pages/info.php?tab=syarat-ketentuan">Terms</a>
        <a href="<?= BASE_URL ?>/pages/info.php?tab=kebijakan-privasi">Privacy</a>
      </div>
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/animations.js"></script>
<script>
// ===== PARTICLE BACKGROUND =====
(function(){
  var canvas = document.getElementById('nx-particles');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var W, H, particles = [];

  function resize(){ W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
  window.addEventListener('resize', resize); resize();

  var colors = ['rgba(0,212,255,', 'rgba(123,47,255,', 'rgba(0,230,118,'];
  for (var i = 0; i < 60; i++) {
    particles.push({
      x: Math.random()*1920, y: Math.random()*1080,
      r: Math.random()*1.5+0.3,
      vx: (Math.random()-.5)*.3, vy: (Math.random()-.5)*.3,
      c: colors[Math.floor(Math.random()*colors.length)],
      a: Math.random()*.5+.1
    });
  }

  function draw(){
    ctx.clearRect(0,0,W,H);
    particles.forEach(function(p){
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
      if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
      ctx.fillStyle = p.c + p.a + ')';
      ctx.fill();
    });
    requestAnimationFrame(draw);
  }
  draw();
})();

// ===== FAQ =====
function toggleFaq(i) {
  var item = document.getElementById('faq'+i);
  var isOpen = item.classList.contains('open');
  document.querySelectorAll('.land-faq-item.open').forEach(function(el){ el.classList.remove('open'); });
  if (!isOpen) item.classList.add('open');
}

// ===== SCROLL REVEAL =====
var reveals = document.querySelectorAll('.reveal, .land-step, .land-product-card, .land-faq-item, .land-stat-item');
var io = new IntersectionObserver(function(entries){
  entries.forEach(function(e){
    if(e.isIntersecting){
      e.target.style.opacity='1';
      e.target.style.transform='translateY(0)';
      io.unobserve(e.target);
    }
  });
}, {threshold:.15});
reveals.forEach(function(el){
  el.style.opacity='0';
  el.style.transform='translateY(28px)';
  el.style.transition='opacity .6s ease, transform .6s ease';
  io.observe(el);
});

// ===== TOPBAR SCROLL =====
var topbar = document.querySelector('.land-topbar');
window.addEventListener('scroll', function(){
  if(window.scrollY > 40) { topbar.style.background='rgba(10,14,26,0.98)'; }
  else { topbar.style.background='rgba(10,14,26,0.85)'; }
}, {passive:true});

// Service Worker
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/service-worker.js').catch(function(){});
</script>
</body>
</html>
