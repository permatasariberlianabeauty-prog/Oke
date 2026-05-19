<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

$userId   = SessionManager::userId();
$userName = SessionManager::get('user_name', '');

if (isPost() && isset($_POST['send_contact'])) {
    CSRF::verify();
    $subject = clean(postVal('subject', ''));
    $message = clean(postVal('message', ''));

    if (empty($subject) || empty($message)) {
        setFlashPopup('error', 'Subjek dan pesan wajib diisi.', 'Gagal');
    } else {
        // Log ke DB atau file
        $logLine = date('Y-m-d H:i:s') . ' | User #' . $userId . ' | Subjek: ' . $subject . ' | Pesan: ' . substr($message,0,200);
        writeLog('contact_messages.log', $logLine);

        // Simpan ke DB jika ada tabel contact_messages
        try {
            db()->execute(
                'INSERT INTO contact_messages (user_id, subject, message, ip_address) VALUES (?,?,?,?)',
                'isss', [$userId, $subject, $message, getClientIp()]
            );
        } catch (Throwable $e) {
            // Tabel mungkin belum ada, log saja
        }

        setFlashPopup('success', 'Pesan Anda telah dikirim. Tim kami akan segera menghubungi Anda.', 'Pesan Terkirim');
        redirect(BASE_URL . '/pages/contact.php');
    }
}

$contact = db()->fetchOne('SELECT * FROM contact_settings LIMIT 1');
$wa     = $contact['whatsapp'] ?? '';
$tg     = $contact['telegram'] ?? '';
$email  = $contact['email'] ?? '';
$ig     = $contact['instagram'] ?? '';
$ytube  = $contact['youtube'] ?? '';
$addr   = $contact['address'] ?? '';
$hours  = $contact['working_hours'] ?? 'Senin - Minggu, 08:00 - 22:00 WIB';

$pageTitle = 'Hubungi Kami';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Hubungi Kami</h1>
  </div>

  <!-- Contact Cards -->
  <div class="contact-cards">

    <?php if ($wa): ?>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$wa) ?>"
      target="_blank" rel="noopener" class="contact-card card">
      <div class="contact-icon" style="background:#25D366">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      </div>
      <div class="contact-info">
        <span class="contact-label">WhatsApp</span>
        <span class="contact-value"><?= e($wa) ?></span>
      </div>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="contact-arrow"><path d="M7 17L17 7M7 7h10v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endif; ?>

    <?php if ($tg): ?>
    <a href="https://t.me/<?= ltrim(e($tg),'@') ?>"
      target="_blank" rel="noopener" class="contact-card card">
      <div class="contact-icon" style="background:#2CA5E0">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
      </div>
      <div class="contact-info">
        <span class="contact-label">Telegram</span>
        <span class="contact-value">@<?= ltrim(e($tg),'@') ?></span>
      </div>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="contact-arrow"><path d="M7 17L17 7M7 7h10v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endif; ?>

    <?php if ($email): ?>
    <a href="mailto:<?= e($email) ?>" class="contact-card card">
      <div class="contact-icon" style="background:#7B2FFF">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="white" stroke-width="2"/><polyline points="22,6 12,13 2,6" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div class="contact-info">
        <span class="contact-label">Email</span>
        <span class="contact-value"><?= e($email) ?></span>
      </div>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="contact-arrow"><path d="M7 17L17 7M7 7h10v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endif; ?>

    <?php if ($ig): ?>
    <a href="https://instagram.com/<?= ltrim(e($ig),'@') ?>"
      target="_blank" rel="noopener" class="contact-card card">
      <div class="contact-icon" style="background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" stroke="white" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="white" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="white"/></svg>
      </div>
      <div class="contact-info">
        <span class="contact-label">Instagram</span>
        <span class="contact-value">@<?= ltrim(e($ig),'@') ?></span>
      </div>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="contact-arrow"><path d="M7 17L17 7M7 7h10v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/pages/chat.php" class="contact-card card">
      <div class="contact-icon" style="background:#00D4FF">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="#0A0E1A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="contact-info">
        <span class="contact-label">Live Chat</span>
        <span class="contact-value">Chat dengan CS kami</span>
      </div>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="contact-arrow"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>

  </div>

  <!-- Working Hours -->
  <?php if ($hours): ?>
  <div class="card contact-hours">
    <div class="hours-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#00D4FF" stroke-width="2"/><path d="M12 7v5l3 3" stroke="#00D4FF" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div>
      <span class="hours-label">Jam Layanan</span>
      <span class="hours-value"><?= e($hours) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($addr): ?>
  <div class="card contact-address">
    <div class="contact-icon-sm">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="#7B2FFF" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="#7B2FFF" stroke-width="2"/></svg>
    </div>
    <div>
      <span class="contact-label">Alamat</span>
      <span class="contact-value"><?= nl2br(e($addr)) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <!-- Contact Form -->
  <div class="card">
    <h2 class="card-title">Kirim Pesan</h2>
    <form method="post" action="" class="form">
      <?= CSRF::field() ?>
      <div class="form-group">
        <label class="form-label" for="contact_subject">Subjek <span class="required">*</span></label>
        <input type="text" id="contact_subject" name="subject" class="form-input"
          placeholder="Contoh: Pertanyaan tentang deposit" required maxlength="150">
      </div>
      <div class="form-group">
        <label class="form-label" for="contact_message">Pesan <span class="required">*</span></label>
        <textarea id="contact_message" name="message" class="form-input form-textarea" rows="5"
          placeholder="Tuliskan pertanyaan atau keluhan Anda di sini..." required maxlength="2000"></textarea>
      </div>
      <button type="submit" name="send_contact" value="1" class="btn btn-primary btn-full btn-lg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22l-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Kirim Pesan
      </button>
    </form>
  </div>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
