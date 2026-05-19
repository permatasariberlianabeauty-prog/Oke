/**
 * NOXARA - Main JavaScript
 * Pure Vanilla JS — no external libraries
 * Version: 1.0.0
 */

'use strict';

/* ============================================================
   GLOBAL STATE
   ============================================================ */
const NX = {
  notifPollInterval: null,
  activeModals: [],
  csrfToken: null,
  isMobile: () => window.innerWidth < 768,
};

/* ============================================================
   INITIALIZATION
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  NX.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  initSidebarToggle();
  initFabMenu();
  initBottomNav();
  initNotifPanel();
  initModals();
  initFormValidation();
  initCopyButtons();
  initImagePreviews();
  initAutoResize();
  initFlashMessages();
  initCountdowns();
  startNotifPolling();
  initAdminSidebar();
  initTabSystem();
  initFilterChips();
});

/* ============================================================
   TOAST NOTIFICATION SYSTEM
   ============================================================ */
/**
 * showToast({ type, title, message, duration })
 * type: 'success' | 'error' | 'warning' | 'info'
 */
function showToast({ type = 'info', title = '', message = '', duration = 4000 } = {}) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const icons = {
    success: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#00D4FF" stroke-width="2"/><path d="M8 12l3 3 5-5" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
    error:   `<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#FF3B3B" stroke-width="2"/><path d="M15 9l-6 6M9 9l6 6" stroke="#FF3B3B" stroke-width="2" stroke-linecap="round"/></svg>`,
    warning: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 22h20L12 2z" stroke="#FF9500" stroke-width="2" stroke-linejoin="round"/><path d="M12 9v5M12 17v.5" stroke="#FF9500" stroke-width="2" stroke-linecap="round"/></svg>`,
    info:    `<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#7B2FFF" stroke-width="2"/><path d="M12 8v.5M12 11v5" stroke="#7B2FFF" stroke-width="2" stroke-linecap="round"/></svg>`,
  };

  const toast = document.createElement('div');
  toast.className = `toast ${type} toast-enter`;
  toast.setAttribute('role', 'alert');
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || icons.info}</div>
    <div class="toast-content">
      ${title ? `<div class="toast-title">${escapeHtml(title)}</div>` : ''}
      ${message ? `<div class="toast-message">${escapeHtml(message)}</div>` : ''}
    </div>
    <button class="toast-close btn-icon" aria-label="Tutup">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    </button>
    <div class="toast-progress" style="animation-duration:${duration}ms"></div>
  `;

  container.appendChild(toast);

  const closeBtn = toast.querySelector('.toast-close');
  closeBtn.addEventListener('click', () => dismissToast(toast));

  const timer = setTimeout(() => dismissToast(toast), duration);
  toast._dismissTimer = timer;

  return toast;
}

function dismissToast(toast) {
  if (!toast || toast._dismissed) return;
  toast._dismissed = true;
  clearTimeout(toast._dismissTimer);
  toast.classList.remove('toast-enter');
  toast.classList.add('toast-exit');
  toast.addEventListener('animationend', () => toast.remove(), { once: true });
  setTimeout(() => toast.remove(), 400);
}

/* Expose globally */
window.showToast = showToast;


/* ============================================================
   SIDEBAR TOGGLE
   ============================================================ */
function initSidebarToggle() {
  const toggle   = document.getElementById('sidebarToggle');
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (!toggle || !sidebar) return;

  function openSidebar() {
    sidebar.classList.add('open');
    if (backdrop) { backdrop.classList.add('visible'); document.body.style.overflow = 'hidden'; }
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    if (backdrop) { backdrop.classList.remove('visible'); document.body.style.overflow = ''; }
  }

  toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
  });

  window.closeSidebar = closeSidebar;
}

/* ============================================================
   FAB BUTTON MENU
   ============================================================ */
function initFabMenu() {
  const fabBtn     = document.getElementById('fabBtn');
  const fabMenu    = document.getElementById('fabMenu');
  const fabOverlay = document.getElementById('fabOverlay');
  if (!fabBtn || !fabMenu) return;

  function toggleFab() {
    const isOpen = fabMenu.classList.contains('open');
    if (isOpen) closeFab(); else openFab();
  }
  function openFab() {
    fabMenu.classList.add('open');
    fabBtn.classList.add('open');
    if (fabOverlay) { fabOverlay.classList.add('visible'); }
  }
  function closeFab() {
    fabMenu.classList.remove('open');
    fabBtn.classList.remove('open');
    if (fabOverlay) { fabOverlay.classList.remove('visible'); }
  }

  fabBtn.addEventListener('click', toggleFab);
  if (fabOverlay) fabOverlay.addEventListener('click', closeFab);
}

/* ============================================================
   BOTTOM NAV ACTIVE STATE
   ============================================================ */
function initBottomNav() {
  const navItems = document.querySelectorAll('.bottom-nav-item[href]');
  const current  = window.location.pathname;
  navItems.forEach(item => {
    const href = item.getAttribute('href') || '';
    if (href && current.endsWith(href.replace(/^.*\//, '').replace(/\?.*$/, ''))) {
      item.classList.add('active');
    }
  });
}

/* ============================================================
   NOTIFICATION PANEL
   ============================================================ */
function initNotifPanel() {
  const toggle  = document.getElementById('notifToggle');
  const panel   = document.getElementById('notifPanel');
  const closeBtn= document.getElementById('notifClose');
  if (!toggle || !panel) return;

  toggle.addEventListener('click', () => {
    const isOpen = panel.classList.contains('open');
    if (isOpen) {
      panel.classList.remove('open');
    } else {
      panel.classList.add('open');
      loadNotifications();
    }
  });

  if (closeBtn) closeBtn.addEventListener('click', () => panel.classList.remove('open'));

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target) && !toggle.contains(e.target)) {
      panel.classList.remove('open');
    }
  });
}

function loadNotifications() {
  const list = document.getElementById('notifList');
  if (!list) return;

  ajaxGet('/api/notification.php?action=list').then(data => {
    if (!data.notifications) return;
    list.innerHTML = '';
    if (data.notifications.length === 0) {
      list.innerHTML = '<div class="empty-state" style="padding:32px 16px"><p>Tidak ada notifikasi</p></div>';
      return;
    }
    data.notifications.forEach(n => {
      const item = document.createElement('div');
      item.className = `notif-item ${n.is_read ? '' : 'unread'}`;
      item.dataset.id = n.id;
      item.innerHTML = `
        ${!n.is_read ? '<div class="unread-dot"></div>' : '<div style="width:8px"></div>'}
        <div class="notif-item-content">
          <div class="notif-item-title">${escapeHtml(n.title)}</div>
          <div class="notif-item-msg">${escapeHtml(n.message)}</div>
          <div class="notif-item-time">${escapeHtml(n.time_ago || '')}</div>
        </div>
      `;
      item.addEventListener('click', () => markNotifRead(n.id, item));
      list.appendChild(item);
    });
  }).catch(() => {});
}

function markNotifRead(id, el) {
  ajaxPost('/api/notification.php', { action: 'mark_read', id }).then(() => {
    el.classList.remove('unread');
    const dot = el.querySelector('.unread-dot');
    if (dot) dot.remove();
    updateNotifBadge();
  }).catch(() => {});
}

function startNotifPolling() {
  updateNotifBadge();
  NX.notifPollInterval = setInterval(updateNotifBadge, 30000);
}

function updateNotifBadge() {
  ajaxGet('/api/notification.php?action=unread_count').then(data => {
    const badges = document.querySelectorAll('.badge-count');
    const count  = data.count || 0;
    badges.forEach(b => {
      b.textContent = count > 99 ? '99+' : count;
      b.style.display = count > 0 ? '' : 'none';
    });
  }).catch(() => {});
}


/* ============================================================
   MODALS
   ============================================================ */
function initModals() {
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-modal]');
    if (trigger) { e.preventDefault(); openModal(trigger.dataset.modal); }

    const closeBtn = e.target.closest('[data-modal-close], .modal-close');
    if (closeBtn) closeModal();

    if (e.target.classList.contains('modal-overlay')) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && NX.activeModals.length > 0) closeModal();
  });
}

function openModal(id) {
  const overlay = document.getElementById('modal-overlay') || document.querySelector('.modal-overlay');
  const modal   = id ? document.getElementById(id) : document.querySelector('.modal');
  if (!overlay || !modal) return;

  overlay.classList.remove('hidden');
  modal.style.display = 'flex';
  modal.classList.add('animate-slide-up');
  document.body.style.overflow = 'hidden';
  NX.activeModals.push(id);
}

function closeModal() {
  const overlay = document.getElementById('modal-overlay') || document.querySelector('.modal-overlay');
  if (!overlay) return;

  overlay.classList.add('hidden');
  document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
  document.body.style.overflow = '';
  NX.activeModals.pop();
}

window.openModal  = openModal;
window.closeModal = closeModal;

/* ============================================================
   CONFIRM DIALOG (replaces window.confirm)
   ============================================================ */
function nxConfirm({ title = 'Konfirmasi', message = 'Apakah Anda yakin?', confirmText = 'Ya', cancelText = 'Batal', onConfirm, onCancel } = {}) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay center-modal';
  overlay.innerHTML = `
    <div class="modal" style="max-width:340px; border-radius:16px; animation: zoom-in 0.2s ease;">
      <div class="modal-header"><h3>${escapeHtml(title)}</h3></div>
      <div class="modal-body"><p style="color:var(--text-secondary);font-size:14px">${escapeHtml(message)}</p></div>
      <div class="modal-footer">
        <button class="btn btn-ghost" id="nx-cancel">${escapeHtml(cancelText)}</button>
        <button class="btn btn-primary" id="nx-confirm">${escapeHtml(confirmText)}</button>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);
  document.body.style.overflow = 'hidden';

  const cleanup = () => { overlay.remove(); document.body.style.overflow = ''; };

  overlay.querySelector('#nx-confirm').addEventListener('click', () => { cleanup(); if (onConfirm) onConfirm(); });
  overlay.querySelector('#nx-cancel').addEventListener('click',  () => { cleanup(); if (onCancel) onCancel(); });
  overlay.addEventListener('click', (e) => { if (e.target === overlay) { cleanup(); if (onCancel) onCancel(); } });

  return overlay;
}

window.nxConfirm = nxConfirm;

/* ============================================================
   FORM VALIDATION HELPERS
   ============================================================ */
function initFormValidation() {
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(input => {
        clearFieldError(input);
        if (!input.value.trim()) {
          showFieldError(input, 'Bidang ini wajib diisi');
          valid = false;
        } else if (input.type === 'email' && !isValidEmail(input.value)) {
          showFieldError(input, 'Format email tidak valid');
          valid = false;
        } else if (input.dataset.minLength && input.value.length < parseInt(input.dataset.minLength)) {
          showFieldError(input, `Minimal ${input.dataset.minLength} karakter`);
          valid = false;
        }
      });
      if (!valid) e.preventDefault();
    });
  });
}

function showFieldError(input, msg) {
  input.classList.add('error');
  const err = document.createElement('span');
  err.className = 'form-error'; err.textContent = msg;
  input.closest('.form-group, .input-wrap')?.appendChild(err);
}

function clearFieldError(input) {
  input.classList.remove('error');
  input.closest('.form-group, .input-wrap')?.querySelector('.form-error')?.remove();
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/* ============================================================
   NUMBER FORMATTING (Indonesian)
   ============================================================ */
function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num);
}

function formatRupiah(num, prefix = 'Rp') {
  return prefix + formatNumber(Math.round(num));
}

function parseRupiah(str) {
  return parseFloat(str.replace(/[^\d]/g, '')) || 0;
}

window.formatNumber = formatNumber;
window.formatRupiah = formatRupiah;

/* ============================================================
   COPY TO CLIPBOARD
   ============================================================ */
function initCopyButtons() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    const text = btn.dataset.copy || btn.closest('[data-copy-target]')?.querySelector(btn.dataset.copyTarget)?.textContent || '';
    copyToClipboard(text, btn);
  });
}

function copyToClipboard(text, btn) {
  navigator.clipboard?.writeText(text).then(() => {
    showToast({ type: 'success', title: 'Disalin!', message: 'Teks telah disalin ke clipboard', duration: 2500 });
    if (btn) {
      const orig = btn.innerHTML;
      btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#00D4FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      setTimeout(() => { btn.innerHTML = orig; }, 1800);
    }
  }).catch(() => {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); ta.remove();
    showToast({ type: 'success', title: 'Disalin!', duration: 2500 });
  });
}

window.copyToClipboard = copyToClipboard;


/* ============================================================
   AJAX HELPERS WITH CSRF
   ============================================================ */
function ajaxGet(url) {
  return fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': NX.csrfToken }
  }).then(r => r.ok ? r.json() : Promise.reject(r));
}

function ajaxPost(url, data) {
  const body = new FormData();
  for (const [k, v] of Object.entries(data)) body.append(k, v);
  body.append('csrf_token', NX.csrfToken);
  return fetch(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': NX.csrfToken },
    body,
  }).then(r => r.ok ? r.json() : Promise.reject(r));
}

function ajaxJson(url, payload) {
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': NX.csrfToken,
    },
    body: JSON.stringify({ ...payload, csrf_token: NX.csrfToken }),
  }).then(r => r.ok ? r.json() : Promise.reject(r));
}

window.ajaxGet  = ajaxGet;
window.ajaxPost = ajaxPost;
window.ajaxJson = ajaxJson;

/* ============================================================
   COUNTDOWN TIMER
   ============================================================ */
function startCountdown(el, targetTs, onExpire) {
  function tick() {
    const now   = Math.floor(Date.now() / 1000);
    const diff  = targetTs - now;
    if (diff <= 0) {
      el.textContent = '00:00:00';
      if (onExpire) onExpire();
      return;
    }
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    el.textContent = `${pad2(h)}:${pad2(m)}:${pad2(s)}`;
    setTimeout(tick, 1000);
  }
  tick();
}

function pad2(n) { return String(n).padStart(2, '0'); }

window.startCountdown = startCountdown;

function initCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach(el => {
    const ts = parseInt(el.dataset.countdown, 10);
    if (!isNaN(ts)) {
      startCountdown(el, ts, () => {
        el.closest('.claim-section')?.querySelector('.btn-claim')?.removeAttribute('disabled');
        el.textContent = 'SIAP!';
        el.style.color = 'var(--green)';
      });
    }
  });
}

/* ============================================================
   COUNT-UP ANIMATION
   ============================================================ */
function countUp(el, target, duration = 1500, prefix = '', suffix = '') {
  const startTime = performance.now();
  const startVal  = 0;

  function update(now) {
    const elapsed  = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const ease     = 1 - Math.pow(1 - progress, 3);
    const current  = Math.floor(startVal + (target - startVal) * ease);
    el.textContent = prefix + formatNumber(current) + suffix;
    if (progress < 1) requestAnimationFrame(update);
    else el.textContent = prefix + formatNumber(target) + suffix;
  }

  requestAnimationFrame(update);
}

window.countUp = countUp;

/* ============================================================
   IMAGE PREVIEW ON FILE INPUT
   ============================================================ */
function initImagePreviews() {
  document.addEventListener('change', (e) => {
    const input = e.target;
    if (input.type !== 'file' || !input.dataset.preview) return;
    const previewEl = document.getElementById(input.dataset.preview) || input.closest('.upload-area')?.querySelector('.upload-preview');
    if (!previewEl || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      previewEl.src = ev.target.result;
      previewEl.style.display = 'block';
      previewEl.closest('.upload-area')?.querySelector('.upload-placeholder')?.remove();
    };
    reader.readAsDataURL(input.files[0]);
  });

  // Drag and drop on upload areas
  document.querySelectorAll('.upload-area').forEach(area => {
    area.addEventListener('dragover', (e) => { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', () => area.classList.remove('dragover'));
    area.addEventListener('drop', (e) => {
      e.preventDefault(); area.classList.remove('dragover');
      const file = e.dataTransfer.files[0];
      const input = area.querySelector('input[type=file]');
      if (input && file) {
        const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
    area.addEventListener('click', () => area.querySelector('input[type=file]')?.click());
  });
}

/* ============================================================
   AUTO-RESIZE TEXTAREA
   ============================================================ */
function initAutoResize() {
  document.addEventListener('input', (e) => {
    if (e.target.tagName === 'TEXTAREA' && e.target.dataset.autoResize !== undefined) {
      e.target.style.height = 'auto';
      e.target.style.height = e.target.scrollHeight + 'px';
    }
  });
}

/* ============================================================
   FLASH MESSAGES (from data attributes)
   ============================================================ */
function initFlashMessages() {
  document.querySelectorAll('[data-flash-type]').forEach(el => {
    const type    = el.dataset.flashType;
    const title   = el.dataset.flashTitle || '';
    const message = el.dataset.flashMessage || el.textContent.trim();
    if (message) showToast({ type, title, message, duration: 5000 });
    el.remove();
  });

  // Also support PHP-injected inline script tag with window._flash
  if (window._flash && Array.isArray(window._flash)) {
    window._flash.forEach(f => showToast(f));
    window._flash = [];
  }
}


/* ============================================================
   ADMIN SIDEBAR TOGGLE
   ============================================================ */
function initAdminSidebar() {
  const toggleBtn = document.getElementById('adminSidebarToggle');
  const sidebar   = document.querySelector('.admin-sidebar');
  const main      = document.querySelector('.admin-main');
  if (!toggleBtn || !sidebar) return;

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main?.classList.toggle('expanded');

    // On mobile, add backdrop
    if (NX.isMobile()) {
      if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
      } else {
        sidebar.classList.add('open');
      }
      sidebar.classList.remove('collapsed');
    }
  });

  // Close admin sidebar on mobile when clicking outside
  document.addEventListener('click', (e) => {
    if (NX.isMobile() && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    }
  });
}

/* ============================================================
   TAB SYSTEM
   ============================================================ */
function initTabSystem() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;
    const tabGroup = btn.closest('.tabs')?.dataset.group || btn.dataset.group;
    const target   = btn.dataset.tab;
    if (!target) return;

    // Deactivate all tabs in group
    const container = btn.closest('[data-tabs-container]') || btn.closest('.tabs')?.parentElement;
    container?.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    container?.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    btn.classList.add('active');
    document.getElementById(target)?.classList.add('active');
  });
}

/* ============================================================
   FILTER CHIPS
   ============================================================ */
function initFilterChips() {
  document.addEventListener('click', (e) => {
    const chip = e.target.closest('.filter-chip');
    if (!chip) return;
    const group = chip.closest('.filter-scroll, .filter-group');
    if (!group) return;
    group.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
  });
}

/* ============================================================
   SKELETON LOADING HELPERS
   ============================================================ */
function showSkeleton(container, rows = 3) {
  container.innerHTML = '';
  for (let i = 0; i < rows; i++) {
    const el = document.createElement('div');
    el.className = 'skeleton-loading skeleton-text';
    el.style.cssText = `height:16px; margin-bottom:10px; width:${60 + Math.random()*35}%`;
    container.appendChild(el);
  }
}

function hideSkeleton(container) {
  container.querySelectorAll('.skeleton-loading').forEach(el => el.remove());
}

window.showSkeleton = showSkeleton;
window.hideSkeleton = hideSkeleton;

/* ============================================================
   LOADING OVERLAY
   ============================================================ */
function showLoading(msg = 'Memproses...') {
  let overlay = document.getElementById('loadingOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `<div class="loading-spinner"></div><p>${escapeHtml(msg)}</p>`;
    document.body.appendChild(overlay);
  }
  overlay.classList.remove('hidden');
}

function hideLoading() {
  document.getElementById('loadingOverlay')?.classList.add('hidden');
}

window.showLoading = showLoading;
window.hideLoading = hideLoading;

/* ============================================================
   UTILITY HELPERS
   ============================================================ */
function escapeHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function debounce(fn, ms = 300) {
  let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

function throttle(fn, ms = 200) {
  let last = 0;
  return (...args) => { const now = Date.now(); if (now - last >= ms) { last = now; fn(...args); } };
}

window.escapeHtml  = escapeHtml;
window.debounce    = debounce;
window.throttle    = throttle;

/* ============================================================
   PROGRESS BAR
   ============================================================ */
function animateProgressBar(el, percent, duration = 800) {
  el.style.width = '0%';
  requestAnimationFrame(() => {
    el.style.transition = `width ${duration}ms cubic-bezier(0.4,0,0.2,1)`;
    el.style.width = Math.min(100, percent) + '%';
  });
}

window.animateProgressBar = animateProgressBar;

/* ============================================================
   INPUT MASK — Rupiah
   ============================================================ */
document.addEventListener('input', (e) => {
  const input = e.target;
  if (!input.dataset.maskRupiah) return;
  let raw = input.value.replace(/\D/g, '');
  if (raw === '') { input.value = ''; return; }
  input.value = formatNumber(parseInt(raw, 10));
});

/* ============================================================
   PASSWORD VISIBILITY TOGGLE
   ============================================================ */
document.addEventListener('click', (e) => {
  const eye = e.target.closest('.input-eye');
  if (!eye) return;
  const wrap  = eye.closest('.input-wrap');
  const input = wrap?.querySelector('input[type=password], input[type=text]');
  if (!input) return;
  const isPass = input.type === 'password';
  input.type = isPass ? 'text' : 'password';
  eye.innerHTML = isPass
    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M1 1l22 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`
    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>`;
});
