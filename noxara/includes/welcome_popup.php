<?php
/**
 * NOXARA - Welcome Popup Renderer
 */

function renderWelcomePopup(): void
{
    $enabled = getSetting('welcome_popup_enabled', '0');
    if ($enabled !== '1') return;

    $title      = getSetting('welcome_popup_title', 'Selamat Datang di NOXARA');
    $message    = getSetting('welcome_popup_message', '');
    $waUrl      = getSetting('welcome_popup_whatsapp_url', '');
    $btnText    = getSetting('welcome_popup_button_text', 'Gabung Grup WhatsApp');
    $showMode   = getSetting('welcome_popup_show_mode', 'once_session');
    $animation  = getSetting('welcome_popup_animation', 'zoom');
    $image      = getSetting('welcome_popup_image', '');
    ?>
<div id="welcomePopup" class="welcome-popup-overlay hidden">
  <div class="welcome-popup-card animate-<?= e($animation) ?>">
    <button class="welcome-popup-close" id="welcomeClose" aria-label="Tutup">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <div class="welcome-popup-visual">
      <?php if ($image): ?>
      <img src="<?= uploadUrl($image) ?>" alt="NOXARA" class="welcome-popup-img floating">
      <?php else: ?>
      <!-- SVG Inline Mining Robot Animasi -->
      <div class="mining-robot-wrap">
        <svg class="mining-robot floating" viewBox="0 0 200 200" width="160" height="160" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <radialGradient id="coreGrad" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#00D4FF" stop-opacity="0.9"/>
              <stop offset="100%" stop-color="#7B2FFF" stop-opacity="0.4"/>
            </radialGradient>
            <filter id="glow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
          </defs>
          <!-- Body -->
          <rect x="60" y="80" width="80" height="70" rx="12" fill="#0F1629" stroke="#00D4FF" stroke-width="1.5"/>
          <!-- Head -->
          <rect x="70" y="40" width="60" height="45" rx="8" fill="#0F1629" stroke="#7B2FFF" stroke-width="1.5"/>
          <!-- Eyes -->
          <circle cx="85" cy="58" r="7" fill="url(#coreGrad)" filter="url(#glow)"><animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite"/></circle>
          <circle cx="115" cy="58" r="7" fill="url(#coreGrad)" filter="url(#glow)"><animate attributeName="opacity" values="1;0.4;1" dur="2s" begin="0.5s" repeatCount="indefinite"/></circle>
          <!-- Antenna -->
          <line x1="100" y1="40" x2="100" y2="20" stroke="#00D4FF" stroke-width="2"/>
          <circle cx="100" cy="16" r="5" fill="#00D4FF" filter="url(#glow)"><animate attributeName="r" values="5;7;5" dur="1.5s" repeatCount="indefinite"/></circle>
          <!-- Arms -->
          <rect x="30" y="90" width="30" height="10" rx="5" fill="#0F1629" stroke="#00D4FF" stroke-width="1.5"><animateTransform attributeName="transform" type="rotate" values="-10 45 95;10 45 95;-10 45 95" dur="1.5s" repeatCount="indefinite"/></rect>
          <rect x="140" y="90" width="30" height="10" rx="5" fill="#0F1629" stroke="#7B2FFF" stroke-width="1.5"><animateTransform attributeName="transform" type="rotate" values="10 155 95;-10 155 95;10 155 95" dur="1.5s" repeatCount="indefinite"/></rect>
          <!-- Chest panel -->
          <rect x="75" y="95" width="50" height="35" rx="6" fill="rgba(0,212,255,0.08)" stroke="#00D4FF" stroke-width="1" stroke-dasharray="3,3"/>
          <circle cx="100" cy="112" r="10" fill="url(#coreGrad)" filter="url(#glow)"><animate attributeName="r" values="8;12;8" dur="2s" repeatCount="indefinite"/></circle>
          <!-- Legs -->
          <rect x="72" y="148" width="22" height="30" rx="5" fill="#0F1629" stroke="#7B2FFF" stroke-width="1.5"/>
          <rect x="106" y="148" width="22" height="30" rx="5" fill="#0F1629" stroke="#7B2FFF" stroke-width="1.5"/>
          <!-- Coins floating -->
          <g><circle cx="40" cy="120" r="8" fill="#FFD700" opacity="0.8" filter="url(#glow)"><animate attributeName="cy" values="120;100;120" dur="3s" repeatCount="indefinite"/><animate attributeName="opacity" values="0.8;0.3;0.8" dur="3s" repeatCount="indefinite"/></circle><text x="36" y="125" font-size="8" fill="#0A0E1A" font-weight="bold">₿</text></g>
          <g><circle cx="162" cy="130" r="6" fill="#00D4FF" opacity="0.7" filter="url(#glow)"><animate attributeName="cy" values="130;108;130" dur="2.5s" begin="1s" repeatCount="indefinite"/><animate attributeName="opacity" values="0.7;0.2;0.7" dur="2.5s" begin="1s" repeatCount="indefinite"/></circle></g>
        </svg>
      </div>
      <?php endif; ?>
    </div>
    <div class="welcome-popup-content">
      <h2 class="welcome-popup-title orbitron"><?= e($title) ?></h2>
      <p class="welcome-popup-message"><?= nl2br(e($message)) ?></p>
    </div>
    <div class="welcome-popup-actions">
      <?php if ($waUrl): ?>
      <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg" id="welcomeWaBtn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <?= e($btnText) ?>
      </a>
      <?php endif; ?>
      <button class="btn btn-ghost btn-lg" id="welcomeDismiss">Nanti Saja</button>
    </div>
  </div>
</div>
<script>
(function(){
  const mode = <?= json_encode($showMode) ?>;
  const key  = 'nxr_welcome_shown';
  let show   = false;
  if (mode === 'always') {
    show = true;
  } else if (mode === 'once_session') {
    show = !sessionStorage.getItem(key);
    if (show) sessionStorage.setItem(key, '1');
  } else if (mode === 'once_day') {
    const last = localStorage.getItem(key);
    const today = new Date().toDateString();
    show = last !== today;
    if (show) localStorage.setItem(key, today);
  }
  if (show) {
    setTimeout(function(){
      const el = document.getElementById('welcomePopup');
      if (el) el.classList.remove('hidden');
    }, 800);
  }
  function closeWelcome() {
    const el = document.getElementById('welcomePopup');
    if (el) { el.classList.add('closing'); setTimeout(()=>el.classList.add('hidden'),400); }
  }
  document.addEventListener('click', function(e){
    if (e.target.id==='welcomeClose'||e.target.id==='welcomeDismiss') closeWelcome();
    if (e.target.id==='welcomePopup') closeWelcome();
  });
})();
</script>
<?php
}
