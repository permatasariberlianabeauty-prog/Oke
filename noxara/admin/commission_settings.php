<?php
/**
 * NOXARA Admin - Pengaturan Komisi Referral
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Pengaturan Komisi Referral';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/commission_settings.php');
        exit;
    }

    $settings = $_POST['comm'] ?? [];
    foreach ($settings as $type => $levels) {
        if (!in_array($type, ['deposit','purchase'])) continue;
        foreach ($levels as $level => $data) {
            $lvl      = (int)$level;
            $percent  = (float)($data['percent'] ?? 0);
            $isActive = (int)($data['is_active'] ?? 0);
            db()->execute(
                'INSERT INTO commission_settings (type,level,percent,is_active) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE percent=?,is_active=?',
                'sididi', [$type,$lvl,$percent,$isActive,$percent,$isActive]
            );
        }
    }
    logActivity('update_commission_settings', 'commission_settings', 0, 'Update pengaturan komisi');
    setFlashPopup('success', 'Pengaturan komisi berhasil disimpan.');
    header('Location: ' . BASE_URL . '/admin/commission_settings.php');
    exit;
}

$allSettings = db()->fetchAll('SELECT * FROM commission_settings ORDER BY type ASC, level ASC');
// Index
$commMap = [];
foreach ($allSettings as $cs) {
    $commMap[$cs['type']][(int)$cs['level']] = $cs;
}

require_once INCLUDES_PATH . '/admin_header.php';
?>

<form method="POST">
  <?= CSRF::field() ?>
  <?php foreach (['deposit' => 'Komisi Deposit', 'purchase' => 'Komisi Pembelian Paket'] as $type => $label): ?>
  <div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header"><h3><?= $label ?></h3></div>
    <p style="padding:0 4px 12px;color:#64748b;font-size:13px">Komisi yang diberikan ke upline saat member melakukan <?= $type === 'deposit' ? 'deposit' : 'pembelian paket' ?>.</p>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>Level Referral</th><th>Persentase (%)</th><th>Status</th></tr></thead>
        <tbody>
          <?php for ($lvl = 1; $lvl <= 3; $lvl++): ?>
          <?php $cs = $commMap[$type][$lvl] ?? ['percent' => 0, 'is_active' => 1]; ?>
          <tr>
            <td><strong>Level <?= $lvl ?></strong><div class="text-xs text-muted">Komisi untuk upline level <?= $lvl ?></div></td>
            <td>
              <input type="number" name="comm[<?= $type ?>][<?= $lvl ?>][percent]"
                class="form-input" style="width:120px"
                value="<?= number_format((float)$cs['percent'],2,'.','') ?>"
                min="0" max="100" step="0.01">
            </td>
            <td>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="comm[<?= $type ?>][<?= $lvl ?>][is_active]" value="1"
                  <?= $cs['is_active'] ? 'checked' : '' ?> style="width:18px;height:18px">
                <span>Aktif</span>
              </label>
            </td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Summary Table -->
  <div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header"><h3>Ringkasan Komisi Saat Ini</h3></div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>Tipe</th><th>Level 1</th><th>Level 2</th><th>Level 3</th></tr></thead>
        <tbody>
          <tr>
            <td>Deposit</td>
            <?php for($l=1;$l<=3;$l++): ?>
            <td><?= number_format((float)($commMap['deposit'][$l]['percent'] ?? 0),2).'%' ?> <span class="badge <?= ($commMap['deposit'][$l]['is_active'] ?? 0)?'badge-success':'badge-error' ?>"><?= ($commMap['deposit'][$l]['is_active'] ?? 0)?'Aktif':'Off' ?></span></td>
            <?php endfor; ?>
          </tr>
          <tr>
            <td>Pembelian</td>
            <?php for($l=1;$l<=3;$l++): ?>
            <td><?= number_format((float)($commMap['purchase'][$l]['percent'] ?? 0),2).'%' ?> <span class="badge <?= ($commMap['purchase'][$l]['is_active'] ?? 0)?'badge-success':'badge-error' ?>"><?= ($commMap['purchase'][$l]['is_active'] ?? 0)?'Aktif':'Off' ?></span></td>
            <?php endfor; ?>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" style="min-width:180px">Simpan Pengaturan Komisi</button>
</form>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
