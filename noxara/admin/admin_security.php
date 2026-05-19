<?php
/**
 * NOXARA Admin - Keamanan Admin
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle  = 'Keamanan Admin';
$myAdminId  = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/admin_security.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_admin') {
        $username  = sanitizeUsername(clean($_POST['adm_username'] ?? ''));
        $email     = trim($_POST['adm_email'] ?? '');
        $fullName  = clean($_POST['adm_full_name'] ?? '');
        $password  = $_POST['adm_password'] ?? '';
        $role      = in_array($_POST['adm_role'] ?? '', ['superadmin','cs','finance']) ? $_POST['adm_role'] : 'cs';

        if (strlen($username) < 4) {
            setFlashPopup('error', 'Username minimal 4 karakter.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashPopup('error', 'Email tidak valid.');
        } elseif (strlen($password) < 8) {
            setFlashPopup('error', 'Password minimal 8 karakter.');
        } elseif (db()->fetchOne('SELECT id FROM admin_users WHERE username=? LIMIT 1', 's', [$username])) {
            setFlashPopup('error', 'Username sudah digunakan.');
        } elseif (db()->fetchOne('SELECT id FROM admin_users WHERE email=? LIMIT 1', 's', [$email])) {
            setFlashPopup('error', 'Email sudah terdaftar.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
            db()->execute(
                'INSERT INTO admin_users (username,email,password,full_name,role,is_active) VALUES (?,?,?,?,?,1)',
                'sssss', [$username,$email,$hash,$fullName,$role]
            );
            logActivity('add_admin', 'admin_users', (int)db()->lastInsertId(), "Tambah admin: {$username} ({$role})");
            setFlashPopup('success', "Admin {$username} berhasil ditambahkan.");
        }
    } elseif ($action === 'toggle_admin') {
        $admId    = (int)($_POST['adm_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? 0);
        if ($admId && $admId !== $myAdminId) {
            db()->execute('UPDATE admin_users SET is_active=? WHERE id=?', 'ii', [$newStatus, $admId]);
            logActivity('toggle_admin', 'admin_users', $admId, "Set is_active={$newStatus} untuk admin #{$admId}");
            setFlashPopup('success', 'Status admin diperbarui.');
        } else {
            setFlashPopup('error', 'Tidak bisa menonaktifkan diri sendiri.');
        }
    } elseif ($action === 'reset_failed_login') {
        $admId = (int)($_POST['adm_id'] ?? 0);
        if ($admId) {
            db()->execute('UPDATE admin_users SET failed_login_count=0, locked_until=NULL WHERE id=?', 'i', [$admId]);
            logActivity('reset_admin_lock', 'admin_users', $admId, "Reset lock untuk admin #{$admId}");
            setFlashPopup('success', 'Hitungan gagal login direset.');
        }
    } elseif ($action === 'change_admin_password') {
        $admId    = (int)($_POST['adm_id'] ?? 0);
        $newPass  = $_POST['new_password'] ?? '';
        if ($admId && strlen($newPass) >= 8) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
            db()->execute('UPDATE admin_users SET password=? WHERE id=?', 'si', [$hash, $admId]);
            logActivity('change_admin_password', 'admin_users', $admId, "Ubah password admin #{$admId}");
            setFlashPopup('success', 'Password admin berhasil diubah.');
        } else {
            setFlashPopup('error', 'Password minimal 8 karakter.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/admin_security.php');
    exit;
}

// ── DATA ──────────────────────────────────────────────────────
$adminUsers  = db()->fetchAll('SELECT * FROM admin_users ORDER BY role ASC, id ASC');
$secLogs     = db()->fetchAll('SELECT asl.*, au.username FROM admin_security_logs asl LEFT JOIN admin_users au ON au.id=asl.admin_id ORDER BY asl.id DESC LIMIT 50');
$sessionTimeout = (int)getSetting('admin_session_timeout', '3600');

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-grid-2" style="margin-bottom:24px;align-items:start">
  <!-- Admin List -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header">
        <h3>Daftar Admin (<?= count($adminUsers) ?>)</h3>
        <button class="btn btn-sm btn-primary" onclick="document.getElementById('addAdminModal').classList.replace('hidden','visible')">+ Tambah Admin</button>
      </div>
      <?php foreach ($adminUsers as $au): ?>
      <div style="border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:14px;margin-bottom:12px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <div>
            <div style="font-weight:700;display:flex;align-items:center;gap:8px">
              <?= e($au['username']) ?>
              <?php if ((int)$au['id'] === $myAdminId): ?><span style="font-size:11px;color:#00D4FF">(Anda)</span><?php endif; ?>
            </div>
            <div class="text-xs text-muted"><?= e($au['full_name']) ?> · <?= e($au['email']) ?></div>
            <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
              <span class="badge badge-<?= $au['role']==='superadmin'?'warning':($au['role']==='finance'?'info':'secondary') ?>"><?= strtoupper(e($au['role'])) ?></span>
              <span class="badge <?= $au['is_active']?'badge-success':'badge-error' ?>"><?= $au['is_active']?'Aktif':'Nonaktif' ?></span>
              <?php if ($au['two_fa_enabled']): ?><span class="badge badge-success">2FA On</span><?php else: ?><span class="badge badge-error">2FA Off</span><?php endif; ?>
              <?php if ((int)$au['failed_login_count'] > 0): ?><span class="badge badge-warning">Fails: <?= (int)$au['failed_login_count'] ?></span><?php endif; ?>
              <?php if ($au['locked_until'] && strtotime($au['locked_until']) > time()): ?><span class="badge badge-error">TERKUNCI</span><?php endif; ?>
            </div>
            <div class="text-xs text-muted" style="margin-top:4px">
              Login terakhir: <?= $au['last_login'] ? formatDate($au['last_login'],'d M Y H:i') : 'Belum pernah' ?>
              <?= $au['last_ip'] ? '(' . e($au['last_ip']) . ')' : '' ?>
            </div>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if ((int)$au['id'] !== $myAdminId): ?>
            <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="toggle_admin"><input type="hidden" name="adm_id" value="<?= (int)$au['id'] ?>"><input type="hidden" name="new_status" value="<?= $au['is_active']?0:1 ?>"><button class="btn btn-xs <?= $au['is_active']?'btn-warning':'btn-success' ?>" onclick="return confirm('<?= $au['is_active']?'Nonaktifkan':'Aktifkan' ?> admin ini?')"><?= $au['is_active']?'Nonaktif':'Aktifkan' ?></button></form>
            <?php endif; ?>
            <?php if ((int)$au['failed_login_count'] > 0 || ($au['locked_until'] && strtotime($au['locked_until']) > time())): ?>
            <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="reset_failed_login"><input type="hidden" name="adm_id" value="<?= (int)$au['id'] ?>"><button class="btn btn-xs btn-primary">Reset Lock</button></form>
            <?php endif; ?>
            <button class="btn btn-xs btn-ghost" onclick="openChangePassModal(<?= (int)$au['id'] ?>, '<?= e($au['username']) ?>')">Ubah Pass</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Security Info -->
  <div>
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Pengaturan Keamanan</h3></div>
      <div class="detail-grid">
        <div class="detail-item"><span>Session Timeout</span><strong><?= number_format($sessionTimeout / 60) ?> menit</strong></div>
        <div class="detail-item"><span>Max Failed Login</span><strong><?= e(getSetting('admin_max_failed_login','5')) ?>x</strong></div>
        <div class="detail-item"><span>Durasi Kunci</span><strong><?= number_format((int)getSetting('admin_lock_duration','1800') / 60) ?> menit</strong></div>
        <div class="detail-item"><span>Total Admin</span><strong><?= count($adminUsers) ?></strong></div>
      </div>
      <a href="<?= BASE_URL ?>/admin/settings.php?tab=keamanan" class="btn btn-xs btn-ghost" style="margin-top:12px">Edit Pengaturan →</a>
    </div>
  </div>
</div>

<!-- Security Logs -->
<div class="admin-card">
  <div class="admin-card-header"><h3>Log Keamanan Terbaru (50 terakhir)</h3></div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Admin</th><th>Event</th><th>IP</th><th>Detail</th><th>Waktu</th></tr></thead>
      <tbody>
      <?php if (empty($secLogs)): ?>
        <tr><td colspan="5" class="text-center text-muted">Belum ada log</td></tr>
      <?php else: ?>
        <?php foreach ($secLogs as $log): ?>
        <tr>
          <td><?= $log['username'] ? e($log['username']) : '<span class="text-muted">-</span>' ?></td>
          <td>
            <?php
            $evColors = ['login_success'=>'badge-success','login_failed'=>'badge-error','login_locked'=>'badge-error','logout'=>'badge-secondary'];
            ?>
            <span class="badge <?= $evColors[$log['event_type']] ?? 'badge-secondary' ?>"><?= e($log['event_type']) ?></span>
          </td>
          <td class="text-xs"><?= e($log['ip_address'] ?? '-') ?></td>
          <td class="text-xs text-muted"><?= e(mb_substr($log['details'] ?? '-',0,60)) ?></td>
          <td><?= formatDate($log['created_at'],'d M Y H:i:s') ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Admin Modal -->
<div class="modal-overlay hidden" id="addAdminModal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header"><h3>Tambah Admin Baru</h3><button class="btn-close" onclick="closeModal('addAdminModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_admin">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label>Username *</label><input type="text" name="adm_username" class="form-input" minlength="4" required></div>
          <div class="form-group"><label>Role *</label><select name="adm_role" class="form-select"><option value="cs">CS (Customer Service)</option><option value="finance">Finance</option><option value="superadmin">Superadmin</option></select></div>
        </div>
        <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="adm_full_name" class="form-input" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="adm_email" class="form-input" required></div>
        <div class="form-group"><label>Password * (min 8 karakter)</label><input type="password" name="adm_password" class="form-input" minlength="8" required></div>
        <div class="alert-warning" style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);border-radius:8px;padding:10px 14px;font-size:13px;color:#fbbf24;margin-top:8px">
          ⚠ Berikan akses superadmin hanya kepada orang yang benar-benar dipercaya.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addAdminModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Tambah Admin</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay hidden" id="changePassModal">
  <div class="modal-box" style="max-width:400px">
    <div class="modal-header"><h3>Ubah Password Admin</h3><button class="btn-close" onclick="closeModal('changePassModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="change_admin_password"><input type="hidden" name="adm_id" id="changePassAdmId">
      <div class="modal-body">
        <p style="margin-bottom:12px">Ubah password untuk: <strong id="changePassAdmName"></strong></p>
        <div class="form-group"><label>Password Baru * (min 8 karakter)</label><input type="password" name="new_password" class="form-input" minlength="8" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('changePassModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function closeModal(id){document.getElementById(id).classList.replace('visible','hidden')}
function openChangePassModal(id, name) {
  document.getElementById('changePassAdmId').value = id;
  document.getElementById('changePassAdmName').textContent = name;
  document.getElementById('changePassModal').classList.replace('hidden','visible');
}
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
