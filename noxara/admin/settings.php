<?php
/**
 * NOXARA Admin - Pengaturan Umum
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Pengaturan Sistem';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    }

    $section = $_POST['section'] ?? '';

    $keysMap = [
        'umum'     => ['site_name','site_tagline','maintenance_mode','maintenance_message','copyright_text'],
        'deposit'  => ['deposit_enabled','deposit_min','deposit_max','deposit_expired_hours'],
        'withdraw' => ['withdraw_enabled','withdraw_open_hour','withdraw_close_hour','withdraw_max_daily'],
        'bonus'    => ['free_balance_enabled','free_balance_register','free_balance_register_enabled'],
        'keamanan' => ['admin_session_timeout','admin_max_failed_login','admin_lock_duration'],
        'chat'     => ['chat_enabled','cs_status'],
    ];

    if ($section === 'marquee') {
        $fields = ['is_enabled','speed','color','include_deposits','include_purchases','include_vip_upgrades','custom_messages'];
        $data = [];
        foreach ($fields as $f) $data[$f] = clean($_POST[$f] ?? '');
        $data['is_enabled']       = (string)(int)($_POST['is_enabled'] ?? 0);
        $data['include_deposits'] = (string)(int)($_POST['include_deposits'] ?? 0);
        $data['include_purchases'] = (string)(int)($_POST['include_purchases'] ?? 0);
        $data['include_vip_upgrades'] = (string)(int)($_POST['include_vip_upgrades'] ?? 0);
        db()->execute(
            'INSERT INTO marquee_settings (id,is_enabled,speed,color,include_deposits,include_purchases,include_vip_upgrades,custom_messages)
             VALUES (1,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE is_enabled=?,speed=?,color=?,include_deposits=?,include_purchases=?,include_vip_upgrades=?,custom_messages=?',
            'iiisiiisiiisiii',
            [(int)$data['is_enabled'],(int)$data['speed'],$data['color'],(int)$data['include_deposits'],(int)$data['include_purchases'],(int)$data['include_vip_upgrades'],$data['custom_messages'],
             (int)$data['is_enabled'],(int)$data['speed'],$data['color'],(int)$data['include_deposits'],(int)$data['include_purchases'],(int)$data['include_vip_upgrades'],$data['custom_messages']]
        );
        setFlashPopup('success', 'Pengaturan marquee disimpan.');
    } elseif (isset($keysMap[$section])) {
        foreach ($keysMap[$section] as $key) {
            $val = isset($_POST[$key]) ? clean($_POST[$key]) : '0';
            updateSetting($key, $val);
        }
        logActivity('update_settings', 'settings', 0, "Update section: {$section}");
        setFlashPopup('success', 'Pengaturan berhasil disimpan.');
    }

    header('Location: ' . BASE_URL . '/admin/settings.php?tab=' . e($section));
    exit;
}

$tab = $_GET['tab'] ?? 'umum';
$s   = getSettings(); // all settings
$marquee = db()->fetchOne('SELECT * FROM marquee_settings WHERE id=1 LIMIT 1') ?? ['is_enabled'=>1,'speed'=>40,'color'=>'#00D4FF','include_deposits'=>1,'include_purchases'=>1,'include_vip_upgrades'=>1,'custom_messages'=>''];

require_once INCLUDES_PATH . '/admin_header.php';

$tabs = [
    'umum'     => 'Umum',
    'deposit'  => 'Deposit',
    'withdraw' => 'Withdraw',
    'bonus'    => 'Bonus',
    'keamanan' => 'Keamanan',
    'chat'     => 'Chat',
    'marquee'  => 'Marquee',
];
?>

<!-- Maintenance Mode Quick Toggle -->
<div class="admin-card" style="margin-bottom:20px;padding:16px 20px">
  <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
    <div>
      <div style="font-weight:700;font-size:15px">Mode Maintenance</div>
      <div style="font-size:13px;color:#64748b;margin-top:2px">Aktifkan untuk menutup website sementara dari user</div>
    </div>
    <form method="POST" style="margin-left:auto">
      <?= CSRF::field() ?><input type="hidden" name="section" value="umum">
      <?php foreach (['site_name','site_tagline','maintenance_message','copyright_text'] as $k): ?>
        <input type="hidden" name="<?= $k ?>" value="<?= e($s[$k] ?? '') ?>">
      <?php endforeach; ?>
      <input type="hidden" name="maintenance_mode" value="<?= ($s['maintenance_mode'] ?? '0') === '1' ? '0' : '1' ?>">
      <button type="submit" class="btn <?= ($s['maintenance_mode'] ?? '0') === '1' ? 'btn-success' : 'btn-warning' ?>" style="min-width:160px">
        <?= ($s['maintenance_mode'] ?? '0') === '1' ? '✓ Matikan Maintenance' : 'Aktifkan Maintenance' ?>
      </button>
    </form>
  </div>
  <?php if (($s['maintenance_mode'] ?? '0') === '1'): ?>
  <div style="margin-top:10px;padding:8px 12px;background:rgba(251,191,36,.1);border-radius:8px;font-size:13px;color:#fbbf24">⚠ Website sedang dalam mode maintenance. User tidak dapat mengakses halaman utama.</div>
  <?php endif; ?>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom:20px">
  <?php foreach ($tabs as $key => $label): ?>
  <a href="?tab=<?= $key ?>" class="tab <?= $tab===$key?'active':'' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'umum'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Umum</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="umum">
    <div class="form-group"><label>Nama Website</label><input type="text" name="site_name" class="form-input" value="<?= e($s['site_name'] ?? '') ?>"></div>
    <div class="form-group"><label>Tagline</label><input type="text" name="site_tagline" class="form-input" value="<?= e($s['site_tagline'] ?? '') ?>"></div>
    <div class="form-group"><label>Copyright Text</label><input type="text" name="copyright_text" class="form-input" value="<?= e($s['copyright_text'] ?? '') ?>"></div>
    <div class="form-group"><label>Mode Maintenance</label><select name="maintenance_mode" class="form-select"><option value="0" <?= ($s['maintenance_mode']??'0')==='0'?'selected':'' ?>>Nonaktif</option><option value="1" <?= ($s['maintenance_mode']??'0')==='1'?'selected':'' ?>>Aktif</option></select></div>
    <div class="form-group"><label>Pesan Maintenance</label><textarea name="maintenance_message" class="form-input" rows="2"><?= e($s['maintenance_message'] ?? '') ?></textarea></div>
    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>

<?php elseif ($tab === 'deposit'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Deposit</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="deposit">
    <div class="form-group"><label>Deposit Diaktifkan</label><select name="deposit_enabled" class="form-select"><option value="1" <?= ($s['deposit_enabled']??'1')==='1'?'selected':'' ?>>Ya</option><option value="0" <?= ($s['deposit_enabled']??'1')==='0'?'selected':'' ?>>Tidak</option></select></div>
    <div class="form-row">
      <div class="form-group"><label>Min Deposit (Rp)</label><input type="number" name="deposit_min" class="form-input" value="<?= e($s['deposit_min'] ?? '50000') ?>"></div>
      <div class="form-group"><label>Maks Deposit (Rp)</label><input type="number" name="deposit_max" class="form-input" value="<?= e($s['deposit_max'] ?? '100000000') ?>"></div>
    </div>
    <div class="form-group"><label>Batas Waktu Konfirmasi (jam)</label><input type="number" name="deposit_expired_hours" class="form-input" value="<?= e($s['deposit_expired_hours'] ?? '3') ?>"></div>
    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>

<?php elseif ($tab === 'withdraw'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Withdraw</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="withdraw">
    <div class="form-group"><label>Withdraw Diaktifkan</label><select name="withdraw_enabled" class="form-select"><option value="1" <?= ($s['withdraw_enabled']??'1')==='1'?'selected':'' ?>>Ya</option><option value="0" <?= ($s['withdraw_enabled']??'1')==='0'?'selected':'' ?>>Tidak</option></select></div>
    <div class="form-row">
      <div class="form-group"><label>Jam Buka (WIB)</label><input type="number" name="withdraw_open_hour" class="form-input" value="<?= e($s['withdraw_open_hour'] ?? '8') ?>" min="0" max="23"></div>
      <div class="form-group"><label>Jam Tutup (WIB)</label><input type="number" name="withdraw_close_hour" class="form-input" value="<?= e($s['withdraw_close_hour'] ?? '20') ?>" min="0" max="23"></div>
    </div>
    <div class="form-group"><label>Maks Withdraw Per Hari</label><input type="number" name="withdraw_max_daily" class="form-input" value="<?= e($s['withdraw_max_daily'] ?? '1') ?>" min="1"></div>
    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>

<?php elseif ($tab === 'bonus'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Bonus</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="bonus">
    <div class="form-group"><label>Saldo Gratis Register Diaktifkan</label><select name="free_balance_register_enabled" class="form-select"><option value="1" <?= ($s['free_balance_register_enabled']??'1')==='1'?'selected':'' ?>>Ya</option><option value="0" <?= ($s['free_balance_register_enabled']??'1')==='0'?'selected':'' ?>>Tidak</option></select></div>
    <div class="form-group"><label>Jumlah Saldo Gratis Saat Daftar (Rp)</label><input type="number" name="free_balance_register" class="form-input" value="<?= e($s['free_balance_register'] ?? '10000') ?>"></div>
    <div class="form-group"><label>Sistem Saldo Gratis Diaktifkan</label><select name="free_balance_enabled" class="form-select"><option value="1" <?= ($s['free_balance_enabled']??'1')==='1'?'selected':'' ?>>Ya</option><option value="0" <?= ($s['free_balance_enabled']??'1')==='0'?'selected':'' ?>>Tidak</option></select></div>
    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>

<?php elseif ($tab === 'keamanan'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Keamanan Admin</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="keamanan">
    <div class="form-group"><label>Session Timeout Admin (detik)</label><input type="number" name="admin_session_timeout" class="form-input" value="<?= e($s['admin_session_timeout'] ?? '3600') ?>" min="300"></div>
    <div class="form-group"><label>Maks Percobaan Login Admin</label><input type="number" name="admin_max_failed_login" class="form-input" value="<?= e($s['admin_max_failed_login'] ?? '5') ?>" min="3"></div>
    <div class="form-group"><label>Durasi Kunci Akun Admin (detik)</label><input type="number" name="admin_lock_duration" class="form-input" value="<?= e($s['admin_lock_duration'] ?? '1800') ?>" min="60"></div>
    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>

<?php elseif ($tab === 'chat'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Chat</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="chat">
    <div class="form-group"><label>Fitur Chat Diaktifkan</label><select name="chat_enabled" class="form-select"><option value="1" <?= ($s['chat_enabled']??'1')==='1'?'selected':'' ?>>Ya</option><option value="0" <?= ($s['chat_enabled']??'1')==='0'?'selected':'' ?>>Tidak</option></select></div>
    <div class="form-group"><label>Status CS</label><select name="cs_status" class="form-select"><option value="online" <?= ($s['cs_status']??'online')==='online'?'selected':'' ?>>Online</option><option value="busy" <?= ($s['cs_status']??'online')==='busy'?'selected':'' ?>>Sibuk</option><option value="offline" <?= ($s['cs_status']??'online')==='offline'?'selected':'' ?>>Offline</option></select></div>
    <button type="submit" class="btn btn-primary">Simpan</button>
  </form>
</div>

<?php elseif ($tab === 'marquee'): ?>
<div class="admin-card">
  <div class="admin-card-header"><h3>Pengaturan Running Text (Marquee)</h3></div>
  <form method="POST">
    <?= CSRF::field() ?><input type="hidden" name="section" value="marquee">
    <div class="form-group"><label>Marquee Diaktifkan</label><select name="is_enabled" class="form-select"><option value="1" <?= $marquee['is_enabled']?'selected':'' ?>>Ya</option><option value="0" <?= !$marquee['is_enabled']?'selected':'' ?>>Tidak</option></select></div>
    <div class="form-row">
      <div class="form-group"><label>Kecepatan (px/s)</label><input type="number" name="speed" class="form-input" value="<?= (int)$marquee['speed'] ?>" min="10" max="200"></div>
      <div class="form-group"><label>Warna Teks</label><input type="text" name="color" class="form-input" value="<?= e($marquee['color']) ?>" placeholder="#00D4FF"></div>
    </div>
    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="include_deposits" value="1" <?= $marquee['include_deposits']?'checked':'' ?>> Tampilkan Deposit</label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="include_purchases" value="1" <?= $marquee['include_purchases']?'checked':'' ?>> Tampilkan Pembelian</label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="include_vip_upgrades" value="1" <?= $marquee['include_vip_upgrades']?'checked':'' ?>> Tampilkan Naik VIP</label>
    </div>
    <div class="form-group"><label>Pesan Kustom (satu per baris)</label><textarea name="custom_messages" class="form-input" rows="4" placeholder="Selamat kepada member setia NOXARA&#10;Promo spesial hari ini!"><?= e($marquee['custom_messages']) ?></textarea></div>
    <button type="submit" class="btn btn-primary">Simpan Pengaturan Marquee</button>
  </form>
</div>
<?php endif; ?>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
