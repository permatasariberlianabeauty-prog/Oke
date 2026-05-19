<?php
/**
 * NOXARA - Notification Slide Panel
 */
?>
<div class="notif-panel" id="notifPanel">
  <div class="notif-panel-header">
    <h3>Notifikasi</h3>
    <button class="btn-icon" id="notifClose">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>
  <div class="notif-panel-actions">
    <button class="btn btn-xs btn-ghost" id="markAllReadBtn">Tandai semua dibaca</button>
  </div>
  <div class="notif-panel-list" id="notifList">
    <div class="notif-loading">Memuat...</div>
  </div>
</div>
<div class="notif-backdrop hidden" id="notifBackdrop"></div>
