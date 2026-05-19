<?php
/**
 * NOXARA Admin - Kelola Misi
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Kelola Misi';
$adminId   = SessionManager::adminId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/missions.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_mission' || $action === 'edit_mission') {
        $missionId   = (int)($_POST['mission_id'] ?? 0);
        $title       = clean($_POST['title'] ?? '');
        $desc        = clean($_POST['description'] ?? '');
        $type        = in_array($_POST['type'] ?? '', ['daily','weekly','milestone']) ? $_POST['type'] : 'daily';
        $actionType  = clean($_POST['action_type'] ?? '');
        $targetVal   = (int)($_POST['target_value'] ?? 1);
        $rewardType  = $_POST['reward_type'] === 'voucher' ? 'voucher' : 'free_balance';
        $rewardVal   = (float)($_POST['reward_value'] ?? 0);
        $minVip      = (int)($_POST['min_vip_level'] ?? 0);
        $sort        = (int)($_POST['sort_order'] ?? 0);
        $isActive    = (int)($_POST['is_active'] ?? 1);

        if ($title && $actionType) {
            if ($action === 'add_mission') {
                db()->execute(
                    'INSERT INTO missions (title,description,type,action_type,target_value,reward_type,reward_value,min_vip_level,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)',
                    'ssssiidsii', [$title,$desc,$type,$actionType,$targetVal,$rewardType,$rewardVal,$minVip,$sort,$isActive]
                );
                logActivity('add_mission', 'mission', (int)db()->lastInsertId(), "Tambah misi: {$title}");
                setFlashPopup('success', 'Misi berhasil ditambahkan.');
            } else {
                db()->execute(
                    'UPDATE missions SET title=?,description=?,type=?,action_type=?,target_value=?,reward_type=?,reward_value=?,min_vip_level=?,sort_order=?,is_active=? WHERE id=?',
                    'ssssiidsiiii', [$title,$desc,$type,$actionType,$targetVal,$rewardType,$rewardVal,$minVip,$sort,$isActive,$missionId]
                );
                logActivity('edit_mission', 'mission', $missionId, "Edit misi: {$title}");
                setFlashPopup('success', 'Misi berhasil diperbarui.');
            }
        }
    } elseif ($action === 'delete_mission') {
        $missionId = (int)($_POST['mission_id'] ?? 0);
        if ($missionId) {
            db()->execute('UPDATE missions SET is_active=0 WHERE id=?', 'i', [$missionId]);
            logActivity('delete_mission', 'mission', $missionId, "Hapus misi #{$missionId}");
            setFlashPopup('success', 'Misi dihapus.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/missions.php');
    exit;
}

$missions = db()->fetchAll(
    'SELECT m.*, (SELECT COUNT(*) FROM user_missions um WHERE um.mission_id=m.id AND um.status IN ("completed","claimed")) as completion_count
     FROM missions m ORDER BY m.type ASC, m.sort_order ASC, m.id ASC'
);

$editMission = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) $editMission = db()->fetchOne('SELECT * FROM missions WHERE id=? LIMIT 1', 'i', [$editId]);

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h3>Daftar Misi (<?= count($missions) ?>)</h3>
    <button class="btn btn-sm btn-primary" onclick="document.getElementById('addMissionModal').classList.replace('hidden','visible')">+ Tambah Misi</button>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>#</th><th>Judul</th><th>Tipe</th><th>Aksi</th><th>Target</th><th>Reward</th><th>Min VIP</th><th>Diselesaikan</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (empty($missions)): ?>
        <tr><td colspan="10" class="text-center text-muted">Belum ada misi</td></tr>
      <?php else: ?>
        <?php foreach ($missions as $m): ?>
        <tr>
          <td><?= (int)$m['id'] ?></td>
          <td><?= e($m['title']) ?></td>
          <td>
            <?php $typeColors=['daily'=>'badge-info','weekly'=>'badge-warning','milestone'=>'badge-success']; ?>
            <span class="badge <?= $typeColors[$m['type']] ?? 'badge-secondary' ?>"><?= ucfirst(e($m['type'])) ?></span>
          </td>
          <td><code style="font-size:11px;background:rgba(0,212,255,.08);padding:2px 6px;border-radius:4px"><?= e($m['action_type']) ?></code></td>
          <td><?= (int)$m['target_value'] ?></td>
          <td>
            <?php if ($m['reward_type'] === 'free_balance'): ?>
              <?= formatRupiah((float)$m['reward_value']) ?>
            <?php else: ?>
              Voucher
            <?php endif; ?>
          </td>
          <td>VIP <?= (int)$m['min_vip_level'] ?>+</td>
          <td><span class="badge badge-secondary"><?= number_format((int)$m['completion_count']) ?> user</span></td>
          <td><span class="badge <?= $m['is_active']?'badge-success':'badge-error' ?>"><?= $m['is_active']?'Aktif':'Nonaktif' ?></span></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= (int)$m['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
              <form method="POST" style="display:inline"><?= CSRF::field() ?><input type="hidden" name="action" value="delete_mission"><input type="hidden" name="mission_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Hapus misi ini?')">Hapus</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Mission Modal -->
<div class="modal-overlay hidden" id="addMissionModal">
  <div class="modal-box" style="max-width:560px">
    <div class="modal-header"><h3>Tambah Misi</h3><button class="btn-close" onclick="closeModal('addMissionModal')">✕</button></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="add_mission">
      <div class="modal-body">
        <div class="form-group"><label>Judul Misi *</label><input type="text" name="title" class="form-input" required></div>
        <div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-input" rows="2"></textarea></div>
        <div class="form-row">
          <div class="form-group">
            <label>Tipe Misi</label>
            <select name="type" class="form-select">
              <option value="daily">Harian</option>
              <option value="weekly">Mingguan</option>
              <option value="milestone">Milestone</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tipe Aksi</label>
            <select name="action_type" class="form-select">
              <option value="login">Login</option>
              <option value="claim_profit">Klaim Profit</option>
              <option value="watch_ads">Tonton Iklan</option>
              <option value="login_streak">Streak Login</option>
              <option value="claim_streak">Streak Klaim</option>
              <option value="first_deposit">Deposit Pertama</option>
              <option value="first_purchase">Beli Pertama</option>
              <option value="referral">Referral</option>
              <option value="vip_level">Level VIP</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Target Nilai</label><input type="number" name="target_value" class="form-input" value="1" min="1"></div>
          <div class="form-group"><label>Min VIP Level</label><select name="min_vip_level" class="form-select"><?php for($i=0;$i<=5;$i++): ?><option value="<?= $i ?>">VIP <?= $i ?></option><?php endfor; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Tipe Reward</label>
            <select name="reward_type" class="form-select">
              <option value="free_balance">Saldo Gratis</option>
              <option value="voucher">Voucher</option>
            </select>
          </div>
          <div class="form-group"><label>Nilai Reward (Rp)</label><input type="number" name="reward_value" class="form-input" min="0" step="1"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="0"></div>
          <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addMissionModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Mission Modal -->
<?php if ($editMission): ?>
<div class="modal-overlay visible" id="editMissionModal" onclick="if(event.target===this)window.location='<?= BASE_URL ?>/admin/missions.php'">
  <div class="modal-box" style="max-width:560px" onclick="event.stopPropagation()">
    <div class="modal-header"><h3>Edit Misi</h3><a href="<?= BASE_URL ?>/admin/missions.php" class="btn-close">✕</a></div>
    <form method="POST">
      <?= CSRF::field() ?><input type="hidden" name="action" value="edit_mission"><input type="hidden" name="mission_id" value="<?= (int)$editMission['id'] ?>">
      <div class="modal-body">
        <div class="form-group"><label>Judul *</label><input type="text" name="title" class="form-input" value="<?= e($editMission['title']) ?>" required></div>
        <div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-input" rows="2"><?= e($editMission['description']) ?></textarea></div>
        <div class="form-row">
          <div class="form-group"><label>Tipe</label><select name="type" class="form-select"><option value="daily" <?= $editMission['type']==='daily'?'selected':'' ?>>Harian</option><option value="weekly" <?= $editMission['type']==='weekly'?'selected':'' ?>>Mingguan</option><option value="milestone" <?= $editMission['type']==='milestone'?'selected':'' ?>>Milestone</option></select></div>
          <div class="form-group"><label>Tipe Aksi</label><select name="action_type" class="form-select"><option value="login" <?= $editMission['action_type']==='login'?'selected':'' ?>>Login</option><option value="claim_profit" <?= $editMission['action_type']==='claim_profit'?'selected':'' ?>>Klaim Profit</option><option value="watch_ads" <?= $editMission['action_type']==='watch_ads'?'selected':'' ?>>Tonton Iklan</option><option value="login_streak" <?= $editMission['action_type']==='login_streak'?'selected':'' ?>>Streak Login</option><option value="claim_streak" <?= $editMission['action_type']==='claim_streak'?'selected':'' ?>>Streak Klaim</option><option value="first_deposit" <?= $editMission['action_type']==='first_deposit'?'selected':'' ?>>Deposit Pertama</option><option value="first_purchase" <?= $editMission['action_type']==='first_purchase'?'selected':'' ?>>Beli Pertama</option><option value="referral" <?= $editMission['action_type']==='referral'?'selected':'' ?>>Referral</option><option value="vip_level" <?= $editMission['action_type']==='vip_level'?'selected':'' ?>>Level VIP</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Target</label><input type="number" name="target_value" class="form-input" value="<?= (int)$editMission['target_value'] ?>"></div>
          <div class="form-group"><label>Min VIP</label><select name="min_vip_level" class="form-select"><?php for($i=0;$i<=5;$i++): ?><option value="<?= $i ?>" <?= (int)$editMission['min_vip_level']===$i?'selected':'' ?>>VIP <?= $i ?></option><?php endfor; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Tipe Reward</label><select name="reward_type" class="form-select"><option value="free_balance" <?= $editMission['reward_type']==='free_balance'?'selected':'' ?>>Saldo Gratis</option><option value="voucher" <?= $editMission['reward_type']==='voucher'?'selected':'' ?>>Voucher</option></select></div>
          <div class="form-group"><label>Nilai Reward</label><input type="number" name="reward_value" class="form-input" value="<?= (float)$editMission['reward_value'] ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="<?= (int)$editMission['sort_order'] ?>"></div>
          <div class="form-group"><label>Status</label><select name="is_active" class="form-select"><option value="1" <?= $editMission['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$editMission['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/admin/missions.php" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>function closeModal(id){document.getElementById(id).classList.replace('visible','hidden')}</script>
<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
