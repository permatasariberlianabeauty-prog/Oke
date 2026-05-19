<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/upload.php';

requireLogin();

$userId = SessionManager::userId();
$user   = db()->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
if (!$user) { redirect(BASE_URL . '/auth/login.php'); }

if (isPost() && isset($_POST['update_profile'])) {
    CSRF::verify();
    $fullName = clean(postVal('full_name', ''));
    $phone    = preg_replace('/[^0-9]/', '', postVal('phone', ''));
    $email    = trim(postVal('email', ''));

    $errors = [];
    if (empty($fullName)) $errors[] = 'Nama lengkap wajib diisi.';
    if (strlen($phone) < 9 || strlen($phone) > 15) $errors[] = 'Nomor HP tidak valid.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';

    // Cek email duplikat
    if (empty($errors)) {
        $dupEmail = db()->fetchOne('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1', 'si', [$email, $userId]);
        if ($dupEmail) $errors[] = 'Email sudah digunakan akun lain.';
    }

    // Upload avatar
    $avatarPath = $user['avatar'] ?? '';
    if (!empty($_FILES['avatar']['name'])) {
        $up = handleUpload($_FILES['avatar'], 'avatar');
        if (!$up['success']) {
            $errors[] = $up['message'];
        } else {
            // Hapus avatar lama
            if (!empty($avatarPath)) deleteUploadedFile($avatarPath);
            $avatarPath = $up['path'];
        }
    }

    if (empty($errors)) {
        db()->execute(
            'UPDATE users SET full_name = ?, phone = ?, email = ?, avatar = ? WHERE id = ?',
            'ssssi', [$fullName, $phone, $email, $avatarPath, $userId]
        );
        SessionManager::set('user_name', $fullName);
        SessionManager::set('user_avatar', $avatarPath);
        setFlashPopup('success', 'Profil berhasil diperbarui.', 'Profil Diperbarui');
        redirect(BASE_URL . '/pages/profile.php');
    } else {
        setFlashPopup('error', implode('<br>', $errors), 'Gagal');
    }
}

// Riwayat login
$loginHistory = db()->fetchAll(
    'SELECT * FROM user_login_logs WHERE user_id = ? AND status = "success" ORDER BY created_at DESC LIMIT 10',
    'i', [$userId]
);

$pageTitle = 'Profil Saya';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Profil Saya</h1>
  </div>

  <!-- Avatar Section -->
  <div class="profile-avatar-section">
    <div class="avatar-upload-wrap" id="avatarWrap">
      <?php if (!empty($user['avatar'])): ?>
      <img src="<?= uploadUrl($user['avatar']) ?>" alt="Avatar" class="profile-avatar" id="avatarPreview">
      <?php else: ?>
      <div class="profile-avatar-placeholder" id="avatarPreview">
        <?= strtoupper(substr(e($user['full_name']??'U'),0,1)) ?>
      </div>
      <?php endif; ?>
      <label class="avatar-edit-btn" for="avatar_input" title="Ganti foto">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </label>
    </div>
    <div class="profile-name-info">
      <span class="profile-username">@<?= e($user['username']) ?></span>
      <span class="vip-badge vip-<?= (int)$user['vip_level'] ?>">VIP <?= (int)$user['vip_level'] ?></span>
      <span class="profile-joined text-muted">Bergabung <?= e(formatDate($user['created_at'])) ?></span>
    </div>
  </div>

  <!-- Edit Form -->
  <div class="card">
    <h2 class="card-title">Edit Profil</h2>
    <form method="post" action="" enctype="multipart/form-data" class="form">
      <?= CSRF::field() ?>
      <input type="file" id="avatar_input" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="hidden">

      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" class="form-input" value="<?= e($user['username']) ?>" readonly disabled>
        <p class="form-hint">Username tidak dapat diubah.</p>
      </div>

      <div class="form-group">
        <label class="form-label" for="full_name">Nama Lengkap <span class="required">*</span></label>
        <input type="text" id="full_name" name="full_name" class="form-input"
          value="<?= e($user['full_name']) ?>" required maxlength="100">
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-input"
          value="<?= e($user['email']) ?>" required maxlength="150">
      </div>

      <div class="form-group">
        <label class="form-label" for="phone">Nomor HP <span class="required">*</span></label>
        <input type="tel" id="phone" name="phone" class="form-input"
          value="<?= e($user['phone']) ?>" required maxlength="15" inputmode="numeric">
      </div>

      <button type="submit" name="update_profile" value="1"
        class="btn btn-primary btn-full btn-lg">
        Simpan Perubahan
      </button>
    </form>
  </div>

  <!-- Quick Links -->
  <div class="quick-links-list">
    <a href="<?= BASE_URL ?>/pages/security.php" class="quick-link-item card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/></svg>
      <span>Keamanan & PIN</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="<?= BASE_URL ?>/pages/bank_account.php" class="quick-link-item card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="15" rx="3" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/></svg>
      <span>Rekening Bank</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="<?= BASE_URL ?>/pages/referral.php" class="quick-link-item card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="2"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="2"/><path d="M16 3.13a4 4 0 0 1 0 7.75M21 21v-2a4 4 0 0 0-3-3.85" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <span>Program Referral</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="quick-link-item card quick-link-danger">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <span>Keluar</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
  </div>

  <!-- Login History -->
  <?php if (!empty($loginHistory)): ?>
  <div class="section">
    <h2 class="section-title">Riwayat Login Terakhir</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Waktu</th><th>IP</th><th>Device</th></tr></thead>
        <tbody>
          <?php foreach ($loginHistory as $log): ?>
          <tr>
            <td><?= e(formatDate($log['created_at'], 'd M Y H:i')) ?></td>
            <td><?= e($log['ip_address']) ?></td>
            <td class="text-muted" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= e(substr($log['user_agent']??'',0,50)) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
document.getElementById('avatar_input').addEventListener('change', function(){
  if (this.files && this.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e){
      var preview = document.getElementById('avatarPreview');
      if (preview.tagName === 'IMG') {
        preview.src = e.target.result;
      } else {
        var img = document.createElement('img');
        img.src = e.target.result;
        img.id  = 'avatarPreview';
        img.alt = 'Avatar';
        img.className = 'profile-avatar';
        preview.replaceWith(img);
      }
    };
    reader.readAsDataURL(this.files[0]);
  }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
