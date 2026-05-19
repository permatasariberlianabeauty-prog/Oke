<?php
/**
 * NOXARA Admin - VIP Settings
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Pengaturan VIP';
$adminId   = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/vip_settings.php');
        exit;
    }

    $levels = $_POST['vip'] ?? [];
    foreach ($levels as $lvl => $data) {
        $level          = (int)$lvl;
        $name           = clean($data['name'] ?? '');
        $minDeposit     = (float)($data['min_deposit'] ?? 0);
        $minWithdraw    = (float)($data['min_withdraw'] ?? 0);
        $withdrawFee    = (float)($data['withdraw_fee_percent'] ?? 0);
        $maxWdDaily     = (float)($data['max_withdraw_daily'] ?? 0);
        $description    = clean($data['description'] ?? '');
        $badgeColor     = preg_match('/^#[0-9a-fA-F]{3,6}$/', $data['badge_color'] ?? '') ? $data['badge_color'] : '#00D4FF';

        db()->execute(
            'INSERT INTO vip_levels (level,name,min_deposit,min_withdraw,withdraw_fee_percent,max_withdraw_daily,description,badge_color)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE name=?,min_deposit=?,min_withdraw=?,withdraw_fee_percent=?,max_withdraw_daily=?,description=?,badge_color=?',
            'isddddsssddddss',
            [$level,$name,$minDeposit,$minWithdraw,$withdrawFee,$maxWdDaily,$description,$badgeColor,
             $name,$minDeposit,$minWithdraw,$withdrawFee,$maxWdDaily,$description,$badgeColor]
        );
    }
    logActivity('update_vip_settings', 'vip_levels', 0, 'Update semua VIP level');
    setFlashPopup('success', 'Pengaturan VIP berhasil disimpan.');
    header('Location: ' . BASE_URL . '/admin/vip_settings.php');
    exit;
}

$vipLevels = db()->fetchAll('SELECT * FROM vip_levels ORDER BY level ASC');
// Index by level
$vipByLevel = [];
foreach ($vipLevels as $v) { $vipByLevel[(int)$v['level']] = $v; }

require_once INCLUDES_PATH . '/admin_header.php';
?>

<!-- Current VIP Table -->
<div class="admin-card" style="margin-bottom:24px">
  <div class="admin-card-header"><h3>Struktur VIP Saat Ini</h3></div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Level</th><th>Nama</th><th>Min Deposit</th><th>Min Withdraw</th><th>Fee WD (%)</th><th>Maks WD/Hari</th><th>Warna Badge</th></tr></thead>
      <tbody>
        <?php for($i=0;$i<=VIP_MAX_LEVEL;$i++): ?>
        <?php $v = $vipByLevel[$i] ?? null; ?>
        <tr>
          <td><span class="badge" style="background:<?= e($v['badge_color'] ?? '#64748b') ?>20;color:<?= e($v['badge_color'] ?? '#64748b') ?>">VIP <?= $i ?></span></td>
          <td><?= e($v['name'] ?? '-') ?></td>
          <td><?= $v ? formatRupiah((float)$v['min_deposit']) : '-' ?></td>
          <td><?= $v ? formatRupiah((float)$v['min_withdraw']) : '-' ?></td>
          <td><?= $v ? number_format((float)$v['withdraw_fee_percent'],2).'%' : '-' ?></td>
          <td><?= $v ? (((float)$v['max_withdraw_daily'])==0 ? 'Tidak Terbatas' : formatRupiah((float)$v['max_withdraw_daily'])) : '-' ?></td>
          <td><span style="display:inline-flex;align-items:center;gap:6px"><span style="width:14px;height:14px;border-radius:3px;background:<?= e($v['badge_color'] ?? '#64748b') ?>"></span><?= e($v['badge_color'] ?? '-') ?></span></td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Edit Form -->
<form method="POST" action="">
  <?= CSRF::field() ?>
  <?php for($i=0;$i<=VIP_MAX_LEVEL;$i++): ?>
  <?php $v = $vipByLevel[$i] ?? ['name'=>'VIP '.$i,'min_deposit'=>0,'min_withdraw'=>0,'withdraw_fee_percent'=>0,'max_withdraw_daily'=>0,'description'=>'','badge_color'=>'#00D4FF']; ?>
  <div class="admin-card" style="margin-bottom:16px">
    <div class="admin-card-header">
      <h3>VIP Level <?= $i ?></h3>
      <span class="badge" style="background:<?= e($v['badge_color'] ?? '#64748b') ?>20;color:<?= e($v['badge_color'] ?? '#64748b') ?>">Preview Badge</span>
    </div>
    <div style="padding:0 4px">
      <div class="form-row">
        <div class="form-group">
          <label>Nama VIP</label>
          <input type="text" name="vip[<?= $i ?>][name]" class="form-input" value="<?= e($v['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Warna Badge (hex)</label>
          <input type="text" name="vip[<?= $i ?>][badge_color]" class="form-input" value="<?= e($v['badge_color']) ?>" placeholder="#00D4FF">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Min Deposit Kumulatif (Rp)</label>
          <input type="number" name="vip[<?= $i ?>][min_deposit]" class="form-input" value="<?= (float)$v['min_deposit'] ?>" min="0">
        </div>
        <div class="form-group">
          <label>Min Withdraw (Rp)</label>
          <input type="number" name="vip[<?= $i ?>][min_withdraw]" class="form-input" value="<?= (float)$v['min_withdraw'] ?>" min="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Fee Withdraw (%)</label>
          <input type="number" name="vip[<?= $i ?>][withdraw_fee_percent]" class="form-input" value="<?= (float)$v['withdraw_fee_percent'] ?>" min="0" max="100" step="0.01">
        </div>
        <div class="form-group">
          <label>Maks Withdraw/Hari (Rp, 0=unlimited)</label>
          <input type="number" name="vip[<?= $i ?>][max_withdraw_daily]" class="form-input" value="<?= (float)$v['max_withdraw_daily'] ?>" min="0">
        </div>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <input type="text" name="vip[<?= $i ?>][description]" class="form-input" value="<?= e($v['description']) ?>">
      </div>
    </div>
  </div>
  <?php endfor; ?>
  <button type="submit" class="btn btn-primary" style="min-width:180px">Simpan Semua VIP Level</button>
</form>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
