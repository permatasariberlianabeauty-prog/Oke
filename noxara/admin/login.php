<?php
/**
 * NOXARA Admin - Login
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
define('SKIP_MAINTENANCE_CHECK', true);
require_once ROOT_PATH . '/config/bootstrap.php';

// Sudah login → redirect
if (SessionManager::isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Refresh halaman dan coba lagi.';
    } else {
        $username    = trim($_POST['username'] ?? '');
        $password    = $_POST['password'] ?? '';
        $ip          = getClientIp();
        $ua          = getUserAgent();

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            // Cari admin
            $admin = db()->fetchOne(
                'SELECT * FROM admin_users WHERE username = ? OR email = ? LIMIT 1',
                'ss', [$username, $username]
            );

            $locked = false;
            if ($admin && $admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                $locked = true;
                $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
                $error = "Akun terkunci. Coba lagi dalam {$remaining} menit.";
                // Log attempt on locked
                db()->execute(
                    'INSERT INTO admin_security_logs (admin_id, event_type, ip_address, user_agent, details) VALUES (?,?,?,?,?)',
                    'issss', [$admin['id'], 'login_locked', $ip, $ua, "Percobaan login saat akun terkunci: {$username}"]
                );
            }

            if (!$locked) {
                if (!$admin || !$admin['is_active']) {
                    $error = 'Username atau password salah.';
                    // Log failed attempt
                    db()->execute(
                        'INSERT INTO admin_security_logs (admin_id, event_type, ip_address, user_agent, details) VALUES (?,?,?,?,?)',
                        'issss', [null, 'login_failed', $ip, $ua, "Username tidak ditemukan: {$username}"]
                    );
                } elseif (!password_verify($password, $admin['password'])) {
                    // Increment failed login
                    $newFailed = (int)$admin['failed_login_count'] + 1;
                    $maxFailed = (int)getSetting('admin_max_failed_login', '5');
                    $lockDuration = (int)getSetting('admin_lock_duration', '1800');
                    $lockedUntil = null;

                    if ($newFailed >= $maxFailed) {
                        $lockedUntil = date('Y-m-d H:i:s', time() + $lockDuration);
                        $error = "Terlalu banyak percobaan gagal. Akun dikunci selama " . ceil($lockDuration / 60) . " menit.";
                    } else {
                        $remaining = $maxFailed - $newFailed;
                        $error = "Password salah. Tersisa {$remaining} percobaan sebelum akun dikunci.";
                    }

                    db()->execute(
                        'UPDATE admin_users SET failed_login_count = ?, locked_until = ? WHERE id = ?',
                        'isi', [$newFailed, $lockedUntil, $admin['id']]
                    );
                    db()->execute(
                        'INSERT INTO admin_security_logs (admin_id, event_type, ip_address, user_agent, details) VALUES (?,?,?,?,?)',
                        'issss', [$admin['id'], 'login_failed', $ip, $ua, "Gagal login attempt {$newFailed}"]
                    );
                } else {
                    // Login berhasil
                    db()->execute(
                        'UPDATE admin_users SET failed_login_count = 0, locked_until = NULL, last_login = NOW(), last_ip = ? WHERE id = ?',
                        'si', [$ip, $admin['id']]
                    );
                    db()->execute(
                        'INSERT INTO admin_security_logs (admin_id, event_type, ip_address, user_agent, details) VALUES (?,?,?,?,?)',
                        'issss', [$admin['id'], 'login_success', $ip, $ua, 'Login berhasil']
                    );
                    SessionManager::loginAdmin($admin);
                    header('Location: ' . BASE_URL . '/admin/index.php');
                    exit;
                }
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<?= CSRF::meta() ?>
<title>Login Admin - NOXARA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:#0A0E1A;color:#e2e8f0;font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.login-wrap{width:100%;max-width:420px}
.login-logo{text-align:center;margin-bottom:32px}
.login-logo .brand{font-family:'Orbitron',sans-serif;font-size:28px;font-weight:700;background:linear-gradient(135deg,#00D4FF,#7B2FFF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;letter-spacing:4px}
.login-logo .sub{color:#64748b;font-size:13px;margin-top:4px}
.login-card{background:#0F1629;border:1px solid rgba(0,212,255,.15);border-radius:16px;padding:32px}
.login-card h2{font-size:20px;font-weight:700;margin-bottom:6px;color:#f1f5f9}
.login-card p{color:#64748b;font-size:14px;margin-bottom:28px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#94a3b8;margin-bottom:8px}
.form-group input{width:100%;background:#0A0E1A;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:13px 16px;color:#f1f5f9;font-size:15px;font-family:inherit;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:#00D4FF}
.btn-login{width:100%;background:linear-gradient(135deg,#00D4FF,#7B2FFF);border:none;border-radius:10px;padding:14px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;min-height:48px;transition:opacity .2s}
.btn-login:hover{opacity:.9}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:12px 16px;color:#f87171;font-size:14px;margin-bottom:20px}
.admin-badge{background:linear-gradient(135deg,rgba(0,212,255,.1),rgba(123,47,255,.1));border:1px solid rgba(0,212,255,.2);border-radius:8px;padding:6px 14px;color:#00D4FF;font-size:12px;font-weight:700;letter-spacing:2px;display:inline-block;margin-bottom:16px}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-logo">
    <div class="brand">NOXARA</div>
    <div class="sub">Investment Platform</div>
  </div>
  <div class="login-card">
    <div class="admin-badge">ADMIN PANEL</div>
    <h2>Masuk ke Dashboard</h2>
    <p>Silakan login dengan akun admin Anda</p>

    <?php if ($error): ?>
    <div class="alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= CSRF::field() ?>
      <div class="form-group">
        <label for="username">Username / Email</label>
        <input type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="Masukkan username atau email" autocomplete="username" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-login">Masuk</button>
    </form>
  </div>
</div>
</body>
</html>
