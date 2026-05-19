<?php
/**
 * NOXARA - Install Wizard
 * Standalone file — no external dependencies
 */
define('INSTALLER', true);

// ============================================================
// LOCK FILE CHECK
// ============================================================
$lockFile   = __DIR__ . '/.installed';
$configFile = dirname(__DIR__) . '/config/config.php';
$sqlFile    = dirname(__DIR__) . '/database/dashboard.sql';

if (file_exists($lockFile) && !isset($_GET['force'])) {
    http_response_code(403);
    die(renderPage('Sudah Terinstall', '<div class="box"><h2>✅ NOXARA Sudah Terinstall</h2>
    <p>Installer telah dikunci. Hapus file <code>install/.installed</code> untuk menjalankan ulang.</p>
    <a href="../" class="btn">Ke Halaman Utama</a></div>'));
}

// ============================================================
// STEP LOGIC
// ============================================================
$step     = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$errors   = [];
$success  = '';
$stepData = $_SESSION['nx_install'] ?? [];
session_start();

if ($step < 1 || $step > 5) $step = 1;

// ============================================================
// REQUIREMENTS CHECK (Step 1)
// ============================================================
function checkRequirements(): array {
    $checks = [];
    $checks['php']      = ['label' => 'PHP >= 8.2',          'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'value' => PHP_VERSION];
    $checks['mysqli']   = ['label' => 'MySQLi Extension',    'ok' => extension_loaded('mysqli'),                  'value' => extension_loaded('mysqli') ? 'Ada' : 'Tidak ada'];
    $checks['pdo']      = ['label' => 'PDO Extension',       'ok' => extension_loaded('pdo'),                     'value' => extension_loaded('pdo') ? 'Ada' : 'Tidak ada'];
    $checks['openssl']  = ['label' => 'OpenSSL Extension',   'ok' => extension_loaded('openssl'),                 'value' => extension_loaded('openssl') ? 'Ada' : 'Tidak ada'];
    $checks['uploads']  = ['label' => 'Folder uploads/ writable', 'ok' => is_writable(dirname(__DIR__).'/uploads'), 'value' => is_writable(dirname(__DIR__).'/uploads') ? 'Writable' : 'Tidak writable'];
    $checks['logs']     = ['label' => 'Folder logs/ writable',    'ok' => is_writable(dirname(__DIR__).'/logs'),    'value' => is_writable(dirname(__DIR__).'/logs') ? 'Writable' : 'Tidak writable'];
    $checks['backups']  = ['label' => 'Folder backups/ writable', 'ok' => is_writable(dirname(__DIR__).'/backups'), 'value' => is_writable(dirname(__DIR__).'/backups') ? 'Writable' : 'Tidak writable'];
    $checks['config_w'] = ['label' => 'Folder config/ writable',  'ok' => is_writable(dirname(__DIR__).'/config'),  'value' => is_writable(dirname(__DIR__).'/config') ? 'Writable' : 'Tidak writable'];
    return $checks;
}


// ============================================================
// AJAX: TEST DB CONNECTION
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'test_db') {
    header('Content-Type: application/json');
    $host = $_POST['db_host'] ?? 'localhost';
    $port = (int)($_POST['db_port'] ?? 3306);
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    try {
        $conn = new mysqli($host, $user, $pass, $name, $port);
        if ($conn->connect_error) throw new Exception($conn->connect_error);
        $conn->close();
        echo json_encode(['ok' => true, 'msg' => 'Koneksi berhasil!']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => 'Gagal: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// PROCESS FORM SUBMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    $step = (int)($_POST['step'] ?? 1);

    // Step 2: Save DB config
    if ($step === 2) {
        $dbHost    = trim($_POST['db_host']    ?? 'localhost');
        $dbPort    = (int)($_POST['db_port']   ?? 3306);
        $dbName    = trim($_POST['db_name']    ?? '');
        $dbUser    = trim($_POST['db_user']    ?? '');
        $dbPass    = $_POST['db_pass']         ?? '';
        $baseUrl   = rtrim(trim($_POST['base_url'] ?? ''), '/');

        if (!$dbName || !$dbUser) { $errors[] = 'DB Name dan DB User wajib diisi.'; }
        else {
            try {
                $testConn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
                if ($testConn->connect_error) throw new Exception($testConn->connect_error);
                $testConn->close();
                $_SESSION['nx_install'] = compact('dbHost','dbPort','dbName','dbUser','dbPass','baseUrl');
                $step = 3;
            } catch (Exception $e) {
                $errors[] = 'Koneksi database gagal: ' . $e->getMessage();
            }
        }
    }

    // Step 3: Import SQL
    if ($step === 3 && isset($_SESSION['nx_install'])) {
        $d = $_SESSION['nx_install'];
        try {
            $conn = new mysqli($d['dbHost'], $d['dbUser'], $d['dbPass'], $d['dbName'], $d['dbPort']);
            $conn->set_charset('utf8mb4');
            if (!file_exists($sqlFile)) throw new Exception('File SQL tidak ditemukan: ' . $sqlFile);
            $sql = file_get_contents($sqlFile);
            $conn->multi_query($sql);
            do { $conn->store_result(); } while ($conn->more_results() && $conn->next_result());
            if ($conn->error) throw new Exception($conn->error);
            $conn->close();
            $_SESSION['nx_install']['sql_done'] = true;
            $step = 4;
        } catch (Exception $e) {
            $errors[] = 'Import SQL gagal: ' . $e->getMessage();
            $step = 3;
        }
    }

    // Step 4: Create admin account
    if ($step === 4 && isset($_SESSION['nx_install']['sql_done'])) {
        $d         = $_SESSION['nx_install'];
        $adminUser = trim($_POST['admin_username'] ?? '');
        $adminMail = trim($_POST['admin_email']    ?? '');
        $adminName = trim($_POST['admin_fullname'] ?? '');
        $adminPass = $_POST['admin_password']      ?? '';
        $adminConf = $_POST['admin_confirm']       ?? '';

        if (!$adminUser || !$adminMail || !$adminPass) { $errors[] = 'Semua field wajib diisi.'; $step = 4; }
        elseif ($adminPass !== $adminConf) { $errors[] = 'Password dan konfirmasi tidak cocok.'; $step = 4; }
        elseif (strlen($adminPass) < 8) { $errors[] = 'Password minimal 8 karakter.'; $step = 4; }
        else {
            try {
                $conn = new mysqli($d['dbHost'], $d['dbUser'], $d['dbPass'], $d['dbName'], $d['dbPort']);
                $conn->set_charset('utf8mb4');
                $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $uid  = 'ADM' . strtoupper(substr(md5(uniqid()), 0, 7));
                $stmt = $conn->prepare("INSERT INTO admins (username, email, full_name, password, role, status, created_at) VALUES (?,?,?,?,'superadmin','active',NOW()) ON DUPLICATE KEY UPDATE password=VALUES(password)");
                $stmt->bind_param('ssss', $adminUser, $adminMail, $adminName, $hash);
                $stmt->execute();
                $stmt->close();
                $conn->close();
                $_SESSION['nx_install']['admin_done'] = true;
                $_SESSION['nx_install']['admin_user'] = $adminUser;
                $step = 5;
            } catch (Exception $e) {
                $errors[] = 'Gagal buat admin: ' . $e->getMessage();
                $step = 4;
            }
        }
    }

    // Step 5: Finalize
    if ($step === 5 && isset($_SESSION['nx_install']['admin_done'])) {
        $d = $_SESSION['nx_install'];
        try {
            // Write config file
            $configContent = "<?php\n// NOXARA — Auto-generated config by Installer\n// Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $configContent .= "define('DB_HOST', " . var_export($d['dbHost'], true) . ");\n";
            $configContent .= "define('DB_PORT', " . (int)$d['dbPort'] . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($d['dbName'], true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($d['dbUser'], true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($d['dbPass'], true) . ");\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
            $configContent .= "define('BASE_URL_MANUAL', " . var_export($d['baseUrl'], true) . ");\n\n";
            $configContent .= "\$scheme = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';\n";
            $configContent .= "\$host   = \$_SERVER['HTTP_HOST'] ?? 'localhost';\n";
            $configContent .= "define('BASE_URL', !empty(BASE_URL_MANUAL) ? rtrim(BASE_URL_MANUAL,'/') : \$scheme.'://'.\$host);\n\n";
            $configContent .= "define('APP_NAME',     'NOXARA');\n";
            $configContent .= "define('APP_TAGLINE',  'Invest Smarter, Grow Faster');\n";
            $configContent .= "define('APP_VERSION',  '1.0.0');\n";
            $configContent .= "define('APP_ENV',      'production');\n";
            $configContent .= "define('APP_TIMEZONE', 'Asia/Jakarta');\n\n";
            $configContent .= "define('ROOT_PATH',    dirname(__DIR__));\n";
            $configContent .= "define('CONFIG_PATH',  ROOT_PATH.'/config');\n";
            $configContent .= "define('INCLUDES_PATH',ROOT_PATH.'/includes');\n";
            $configContent .= "define('PAGES_PATH',   ROOT_PATH.'/pages');\n";
            $configContent .= "define('ADMIN_PATH',   ROOT_PATH.'/admin');\n";
            $configContent .= "define('ASSETS_PATH',  ROOT_PATH.'/assets');\n";
            $configContent .= "define('UPLOADS_PATH', ROOT_PATH.'/uploads');\n";
            $configContent .= "define('BACKUPS_PATH', ROOT_PATH.'/backups');\n";
            $configContent .= "define('LOGS_PATH',    ROOT_PATH.'/logs');\n";
            $configContent .= "define('CRON_PATH',    ROOT_PATH.'/cron');\n\n";
            $configContent .= "define('CSRF_TOKEN_LENGTH', 64);\n";
            $configContent .= "define('SESSION_LIFETIME', 7200);\n";
            $configContent .= "define('ADMIN_SESSION_LIFETIME', 3600);\n";
            $configContent .= "define('PIN_HASH_COST', 10);\n";
            $configContent .= "define('PASSWORD_HASH_COST', 12);\n";
            $configContent .= "define('MAX_LOGIN_ATTEMPTS', 5);\n";
            $configContent .= "define('LOGIN_LOCK_DURATION', 1800);\n";
            $configContent .= "define('RESET_TOKEN_LIFETIME', 3600);\n";
            $configContent .= "define('MAX_UPLOAD_SIZE', 5*1024*1024);\n";
            $configContent .= "define('ALLOWED_IMAGE_TYPES', ['jpg','jpeg','png','webp']);\n";
            $configContent .= "define('ALLOWED_DEPOSIT_TYPES', ['jpg','jpeg','png','webp','pdf']);\n\n";
            $configContent .= "error_reporting(0);\nini_set('display_errors','0');\nini_set('log_errors','1');\nini_set('error_log', LOGS_PATH.'/php_errors.log');\n";

            file_put_contents($configFile, $configContent);

            // Write lock file
            file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - Installed by wizard');

            // Write install log
            try {
                $conn = new mysqli($d['dbHost'], $d['dbUser'], $d['dbPass'], $d['dbName'], $d['dbPort']);
                $conn->set_charset('utf8mb4');
                $conn->query("INSERT INTO activity_logs (type, description, created_at) VALUES ('install','NOXARA installed via wizard at ".date('Y-m-d H:i:s')."',NOW()) ON DUPLICATE KEY UPDATE description=description");
                $conn->close();
            } catch (Exception $ignored) {}

            $success = 'Instalasi berhasil!';
            $_SESSION['nx_install'] = [];
            unset($_SESSION['nx_install']);
        } catch (Exception $e) {
            $errors[] = 'Finalisasi gagal: ' . $e->getMessage();
            $step = 5;
        }
    }
}

// ============================================================
// AUTO-DETECT BASE URL
// ============================================================
$autoBaseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
             . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

// ============================================================
// RENDER
// ============================================================
$reqs     = checkRequirements();
$allReqOk = !in_array(false, array_column($reqs, 'ok'), true);
$d        = $_SESSION['nx_install'] ?? [];

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NOXARA Installer</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0A0E1A;--card:#0F1629;--cyan:#00D4FF;--purple:#7B2FFF;--green:#00E676;--red:#FF3B3B;--border:rgba(255,255,255,.07);--text:#F1F5F9;--muted:#64748B}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh;padding:20px;font-size:15px}
.wrap{max-width:680px;margin:0 auto}
.brand{text-align:center;padding:30px 0 20px;font-size:28px;font-weight:900;letter-spacing:4px;background:linear-gradient(135deg,var(--cyan),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.subtitle{text-align:center;color:var(--muted);margin-top:-12px;margin-bottom:24px;font-size:13px}
.card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px;margin-bottom:20px}
.step-bar{display:flex;gap:4px;margin-bottom:24px}
.step-item{flex:1;height:4px;border-radius:2px;background:rgba(255,255,255,.08)}
.step-item.done{background:var(--cyan)}
.step-item.active{background:linear-gradient(90deg,var(--cyan),var(--purple))}
h2{font-size:18px;font-weight:700;margin-bottom:6px;color:var(--text)}
p.desc{color:var(--muted);font-size:13px;margin-bottom:20px}
.req-list{display:flex;flex-direction:column;gap:8px;margin:16px 0}
.req-item{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)}
.req-label{font-size:13px;font-weight:500}
.req-status{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700}
.ok{color:var(--green)}.fail{color:var(--red)}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px}
.form-input,.form-select{width:100%;background:rgba(255,255,255,.05);border:1.5px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;padding:10px 13px;outline:none;transition:border-color .2s;font-family:inherit}
.form-input:focus,.form-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,212,255,.15)}
.form-row{display:grid;grid-template-columns:3fr 1fr;gap:12px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:all .2s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,var(--cyan),var(--purple));color:#fff}
.btn-primary:hover{filter:brightness(1.1)}
.btn-outline{background:transparent;border:2px solid var(--border);color:var(--muted)}
.btn-outline:hover{border-color:var(--cyan);color:var(--cyan)}
.btn-test{background:rgba(0,212,255,.12);border:1px solid rgba(0,212,255,.3);color:var(--cyan);padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600}
.actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
.errors{background:rgba(255,59,59,.1);border:1px solid rgba(255,59,59,.3);color:var(--red);padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px}
.success-wrap{text-align:center;padding:20px 0}
.success-icon{font-size:56px;margin-bottom:16px}
.success-wrap h2{font-size:22px;color:var(--green);margin-bottom:8px}
.success-wrap p{color:var(--muted);font-size:14px;margin-bottom:20px}
.creds{background:rgba(0,212,255,.06);border:1px solid rgba(0,212,255,.2);border-radius:10px;padding:16px;text-align:left;margin:16px 0}
.creds p{font-size:13px;color:var(--muted);margin-bottom:6px}
.creds strong{color:var(--cyan)}
code{background:rgba(255,255,255,.08);padding:2px 7px;border-radius:4px;font-family:monospace;font-size:13px}
.test-result{margin-top:8px;font-size:12px;font-weight:600;min-height:18px}
.test-result.ok{color:var(--green)}.test-result.fail{color:var(--red)}
.warn-box{background:rgba(255,149,0,.1);border:1px solid rgba(255,149,0,.3);color:#ff9500;padding:12px 14px;border-radius:8px;font-size:13px;margin-top:12px}
@media(max-width:480px){.form-row{grid-template-columns:1fr}.actions{flex-direction:column}.btn{width:100%}}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">NOXARA</div>
  <p class="subtitle">Installation Wizard</p>

  <!-- Step progress bar -->
  <div class="step-bar">
    <?php for($i=1;$i<=5;$i++): ?>
    <div class="step-item <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"></div>
    <?php endfor; ?>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="errors"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>


  <!-- STEP 1: Welcome & Requirements -->
  <?php if ($step === 1): ?>
  <div class="card">
    <h2>👋 Selamat Datang di NOXARA Installer</h2>
    <p class="desc">Wizard ini akan memandu Anda menginstal NOXARA. Pastikan semua persyaratan berikut terpenuhi sebelum melanjutkan.</p>
    <div class="req-list">
      <?php foreach ($reqs as $key => $req): ?>
      <div class="req-item">
        <span class="req-label"><?= htmlspecialchars($req['label']) ?></span>
        <span class="req-status <?= $req['ok'] ? 'ok' : 'fail' ?>">
          <?= $req['ok'] ? '✓' : '✗' ?> <?= htmlspecialchars($req['value']) ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$allReqOk): ?>
    <div class="warn-box">⚠️ Beberapa persyaratan belum terpenuhi. Harap perbaiki terlebih dahulu sebelum melanjutkan.</div>
    <?php endif; ?>
    <div class="actions">
      <a href="?step=2" class="btn btn-primary <?= $allReqOk ? '' : '' ?>">Lanjut →</a>
    </div>
  </div>

  <!-- STEP 2: Database Config -->
  <?php elseif ($step === 2): ?>
  <div class="card">
    <h2>🗄️ Konfigurasi Database</h2>
    <p class="desc">Masukkan informasi koneksi database MySQL Anda.</p>
    <form method="POST">
      <input type="hidden" name="step" value="2">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">DB Host</label>
          <input class="form-input" name="db_host" id="db_host" value="<?= htmlspecialchars($d['dbHost'] ?? 'localhost') ?>" placeholder="localhost">
        </div>
        <div class="form-group">
          <label class="form-label">DB Port</label>
          <input class="form-input" name="db_port" id="db_port" value="<?= htmlspecialchars($d['dbPort'] ?? '3306') ?>" placeholder="3306">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Database</label>
        <input class="form-input" name="db_name" id="db_name" value="<?= htmlspecialchars($d['dbName'] ?? '') ?>" placeholder="noxara_db" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">DB Username</label>
          <input class="form-input" name="db_user" id="db_user" value="<?= htmlspecialchars($d['dbUser'] ?? '') ?>" placeholder="noxara_user" required>
        </div>
        <div class="form-group">
          <label class="form-label">DB Password</label>
          <input class="form-input" type="password" name="db_pass" id="db_pass" value="" placeholder="••••••••">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Base URL</label>
        <input class="form-input" name="base_url" id="base_url" value="<?= htmlspecialchars($d['baseUrl'] ?? $autoBaseUrl) ?>" placeholder="https://noxara.page" required>
        <small style="color:var(--muted);font-size:11px;margin-top:4px;display:block">Auto-detect: <?= htmlspecialchars($autoBaseUrl) ?></small>
      </div>
      <button type="button" class="btn-test" onclick="testConnection()">🔌 Test Koneksi</button>
      <div class="test-result" id="testResult"></div>
      <div class="actions">
        <a href="?step=1" class="btn btn-outline">← Kembali</a>
        <button type="submit" class="btn btn-primary">Lanjut →</button>
      </div>
    </form>
  </div>

  <!-- STEP 3: Import SQL -->
  <?php elseif ($step === 3): ?>
  <div class="card">
    <h2>📥 Import Database</h2>
    <p class="desc">Klik tombol di bawah untuk mengimpor struktur dan data awal database NOXARA.</p>
    <?php if (file_exists($sqlFile)): ?>
    <div style="background:rgba(0,230,118,.08);border:1px solid rgba(0,230,118,.2);padding:12px 14px;border-radius:8px;font-size:13px;color:#00E676;margin-bottom:16px">
      ✓ File SQL ditemukan: <code>database/dashboard.sql</code>
    </div>
    <?php else: ?>
    <div class="warn-box">⚠️ File <code>database/dashboard.sql</code> tidak ditemukan!</div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="step" value="3">
      <div class="actions">
        <a href="?step=2" class="btn btn-outline">← Kembali</a>
        <button type="submit" class="btn btn-primary" <?= file_exists($sqlFile) ? '' : 'disabled' ?>>Import Database →</button>
      </div>
    </form>
  </div>

  <!-- STEP 4: Admin Account -->
  <?php elseif ($step === 4): ?>
  <div class="card">
    <h2>👤 Buat Akun Admin</h2>
    <p class="desc">Buat akun superadmin untuk mengakses panel administrasi NOXARA.</p>
    <form method="POST">
      <input type="hidden" name="step" value="4">
      <div class="form-group">
        <label class="form-label">Username Admin</label>
        <input class="form-input" name="admin_username" value="superadmin" placeholder="superadmin" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email Admin</label>
        <input class="form-input" type="email" name="admin_email" placeholder="admin@noxara.page" required>
      </div>
      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <input class="form-input" name="admin_fullname" placeholder="Super Admin" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password (min 8 karakter)</label>
          <input class="form-input" type="password" name="admin_password" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Konfirmasi Password</label>
          <input class="form-input" type="password" name="admin_confirm" placeholder="••••••••" required>
        </div>
      </div>
      <div class="actions">
        <a href="?step=3" class="btn btn-outline">← Kembali</a>
        <button type="submit" class="btn btn-primary">Buat Admin →</button>
      </div>
    </form>
  </div>

  <!-- STEP 5: Finalize -->
  <?php elseif ($step === 5 && empty($success)): ?>
  <div class="card">
    <h2>🚀 Finalisasi Instalasi</h2>
    <p class="desc">Klik tombol di bawah untuk menulis file konfigurasi dan mengunci installer.</p>
    <form method="POST">
      <input type="hidden" name="step" value="5">
      <div class="actions">
        <button type="submit" class="btn btn-primary">Selesaikan Instalasi ✓</button>
      </div>
    </form>
  </div>

  <!-- SUCCESS -->
  <?php elseif (!empty($success)): ?>
  <div class="card">
    <div class="success-wrap">
      <div class="success-icon">🎉</div>
      <h2>Instalasi Berhasil!</h2>
      <p>NOXARA telah berhasil diinstal dan siap digunakan.</p>
      <div class="creds">
        <p>URL Admin Login:</p>
        <strong><?= htmlspecialchars($d['baseUrl'] ?? $autoBaseUrl) ?>/admin/login.php</strong>
        <p style="margin-top:10px">Username: <code><?= htmlspecialchars($d['admin_user'] ?? 'superadmin') ?></code></p>
      </div>
      <div class="warn-box" style="text-align:left;margin-bottom:16px">
        ⚠️ <strong>PENTING:</strong> Segera ubah password admin setelah login pertama! Hapus atau kunci folder <code>install/</code> untuk keamanan.
      </div>
      <a href="../admin/login.php" class="btn btn-primary">Login ke Admin Panel →</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function testConnection() {
  const el = document.getElementById('testResult');
  el.className = 'test-result';
  el.textContent = 'Menguji koneksi...';
  const fd = new FormData();
  fd.append('db_host', document.getElementById('db_host').value);
  fd.append('db_port', document.getElementById('db_port').value);
  fd.append('db_name', document.getElementById('db_name').value);
  fd.append('db_user', document.getElementById('db_user').value);
  fd.append('db_pass', document.getElementById('db_pass').value);
  fetch('?action=test_db', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      el.className = 'test-result ' + (data.ok ? 'ok' : 'fail');
      el.textContent = data.msg;
    })
    .catch(() => { el.className='test-result fail'; el.textContent='Request gagal.'; });
}
</script>
</body>
</html>
<?php
$html = ob_get_clean();
echo $html;

function renderPage($title, $body) {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>{$title}</title><style>body{background:#0A0E1A;color:#f1f5f9;font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}.box{background:#0F1629;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:32px;max-width:460px;text-align:center}h2{margin-bottom:12px;font-size:20px}p{color:#64748b;font-size:14px;margin-bottom:20px}code{background:rgba(255,255,255,.08);padding:2px 7px;border-radius:4px;font-family:monospace}.btn{display:inline-block;padding:12px 24px;background:linear-gradient(135deg,#00D4FF,#7B2FFF);color:#fff;border-radius:8px;text-decoration:none;font-weight:700;margin-top:12px}</style></head><body>{$body}</body></html>";
}
