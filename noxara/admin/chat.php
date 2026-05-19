<?php
/**
 * NOXARA Admin - Live Chat CS
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin();

$pageTitle = 'Live Chat';
$adminId   = SessionManager::adminId();

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Token tidak valid.']);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'send_reply') {
        $roomId  = (int)($_POST['room_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($roomId && $message !== '') {
            $room = db()->fetchOne('SELECT * FROM chat_rooms WHERE id=? LIMIT 1', 'i', [$roomId]);
            if ($room) {
                db()->execute(
                    'INSERT INTO chat_messages (room_id, sender_type, sender_id, message) VALUES (?,?,?,?)',
                    'isis', [$roomId, 'admin', $adminId, $message]
                );
                db()->execute(
                    'UPDATE chat_rooms SET user_unread=user_unread+1, admin_unread=0, last_message_at=NOW(), admin_id=? WHERE id=?',
                    'ii', [$adminId, $roomId]
                );
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    } elseif ($action === 'mark_read') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        if ($roomId) {
            db()->execute('UPDATE chat_rooms SET admin_unread=0 WHERE id=?', 'i', [$roomId]);
            db()->execute("UPDATE chat_messages SET is_read=1 WHERE room_id=? AND sender_type='user'", 'i', [$roomId]);
        }
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'update_cs_status') {
        $status = $_POST['cs_status'] ?? 'online';
        if (in_array($status, ['online','busy','offline'])) {
            updateSetting('cs_status', $status);
            logActivity('update_cs_status', 'settings', 0, "CS status → {$status}");
        }
        header('Location: ' . BASE_URL . '/admin/chat.php');
        exit;
    }
}

// ── DATA ──────────────────────────────────────────────────────
$activeRoomId = (int)($_GET['room'] ?? 0);

$rooms = db()->fetchAll(
    "SELECT cr.*, u.username, u.full_name, u.avatar,
            (SELECT message FROM chat_messages WHERE room_id=cr.id ORDER BY id DESC LIMIT 1) as last_msg
     FROM chat_rooms cr JOIN users u ON u.id=cr.user_id
     ORDER BY cr.last_message_at DESC, cr.created_at DESC
     LIMIT 50"
);

$messages = [];
$activeRoom = null;
if ($activeRoomId > 0) {
    $activeRoom = db()->fetchOne(
        'SELECT cr.*, u.username, u.full_name FROM chat_rooms cr JOIN users u ON u.id=cr.user_id WHERE cr.id=? LIMIT 1',
        'i', [$activeRoomId]
    );
    if ($activeRoom) {
        $messages = db()->fetchAll(
            'SELECT * FROM chat_messages WHERE room_id=? ORDER BY created_at ASC LIMIT 100',
            'i', [$activeRoomId]
        );
        // Mark read
        db()->execute("UPDATE chat_rooms SET admin_unread=0 WHERE id=?", 'i', [$activeRoomId]);
        db()->execute("UPDATE chat_messages SET is_read=1 WHERE room_id=? AND sender_type='user'", 'i', [$activeRoomId]);
    }
}

$templates  = db()->fetchAll('SELECT * FROM chat_templates WHERE is_active=1 ORDER BY sort_order ASC');
$csStatus   = getSetting('cs_status', 'online');

require_once INCLUDES_PATH . '/admin_header.php';
?>

<style>
.chat-layout{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 140px);gap:16px}
@media(max-width:768px){.chat-layout{grid-template-columns:1fr;grid-template-rows:auto 1fr}}
.chat-sidebar{background:#0F1629;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}
.chat-sidebar-header{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between}
.chat-rooms-list{flex:1;overflow-y:auto}
.chat-room-item{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer;transition:.15s}
.chat-room-item:hover{background:rgba(0,212,255,.06)}
.chat-room-item.active{background:rgba(0,212,255,.1);border-left:3px solid #00D4FF}
.room-user{display:flex;align-items:center;gap:10px}
.room-avatar{width:38px;height:38px;background:linear-gradient(135deg,#00D4FF,#7B2FFF);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.room-meta{flex:1;min-width:0}
.room-name{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.room-last{font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
.unread-badge{background:#ff4444;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.chat-main{background:#0F1629;border:1px solid rgba(255,255,255,.06);border-radius:12px;display:flex;flex-direction:column;overflow:hidden}
.chat-main-header{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between}
.chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px}
.msg-bubble{max-width:75%;padding:10px 14px;border-radius:12px;font-size:14px;line-height:1.5}
.msg-bubble.user{background:rgba(255,255,255,.07);align-self:flex-start;border-bottom-left-radius:4px}
.msg-bubble.admin{background:linear-gradient(135deg,rgba(0,212,255,.15),rgba(123,47,255,.15));border:1px solid rgba(0,212,255,.2);align-self:flex-end;border-bottom-right-radius:4px}
.msg-time{font-size:10px;color:#64748b;margin-top:4px}
.chat-input-area{padding:12px 16px;border-top:1px solid rgba(255,255,255,.06)}
.chat-input-row{display:flex;gap:8px}
.chat-input-row textarea{flex:1;background:#0A0E1A;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;color:#f1f5f9;font-size:14px;font-family:inherit;resize:none;outline:none}
.chat-input-row textarea:focus{border-color:#00D4FF}
.templates-bar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.template-btn{background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.2);color:#00D4FF;border-radius:8px;padding:4px 10px;font-size:12px;cursor:pointer;font-family:inherit}
.template-btn:hover{background:rgba(0,212,255,.15)}
.cs-status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px}
.cs-status-online{background:#22c55e}.cs-status-busy{background:#fbbf24}.cs-status-offline{background:#ef4444}
</style>

<!-- CS Status Bar -->
<div class="admin-card" style="margin-bottom:16px;padding:12px 16px">
  <form method="POST" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <?= CSRF::field() ?><input type="hidden" name="action" value="update_cs_status">
    <span style="font-size:14px;font-weight:600">Status CS:</span>
    <span class="cs-status-dot cs-status-<?= e($csStatus) ?>"></span>
    <span style="font-size:14px"><?= ucfirst(e($csStatus)) ?></span>
    <select name="cs_status" class="form-select" style="width:auto">
      <option value="online" <?= $csStatus==='online'?'selected':'' ?>>Online</option>
      <option value="busy" <?= $csStatus==='busy'?'selected':'' ?>>Sibuk</option>
      <option value="offline" <?= $csStatus==='offline'?'selected':'' ?>>Offline</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Update</button>
  </form>
</div>

<div class="chat-layout">
  <!-- Sidebar: room list -->
  <div class="chat-sidebar">
    <div class="chat-sidebar-header">
      <span style="font-weight:600;font-size:14px">Pesan (<?= count($rooms) ?>)</span>
    </div>
    <div class="chat-rooms-list">
      <?php if (empty($rooms)): ?>
        <div style="padding:20px;text-align:center;color:#64748b;font-size:13px">Belum ada percakapan</div>
      <?php else: ?>
        <?php foreach ($rooms as $room): ?>
        <a href="?room=<?= (int)$room['id'] ?>" style="text-decoration:none;color:inherit">
          <div class="chat-room-item <?= $activeRoomId===$room['id']?'active':'' ?>">
            <div class="room-user">
              <div class="room-avatar"><?= strtoupper(substr($room['username'],0,1)) ?></div>
              <div class="room-meta">
                <div class="room-name"><?= e($room['username']) ?></div>
                <div class="room-last"><?= e(mb_substr($room['last_msg'] ?? 'Belum ada pesan',0,35)) ?></div>
              </div>
              <?php if ((int)$room['admin_unread'] > 0): ?>
              <div class="unread-badge"><?= (int)$room['admin_unread'] ?></div>
              <?php endif; ?>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat main -->
  <div class="chat-main">
    <?php if ($activeRoom): ?>
    <div class="chat-main-header">
      <div>
        <div style="font-weight:700"><?= e($activeRoom['username']) ?></div>
        <div style="font-size:12px;color:#64748b"><?= e($activeRoom['full_name']) ?></div>
      </div>
      <a href="<?= BASE_URL ?>/admin/members.php?id=<?= (int)$activeRoom['user_id'] ?>" class="btn btn-xs btn-ghost" target="_blank">Lihat Profil</a>
    </div>
    <div class="chat-messages" id="chatMessages">
      <?php if (empty($messages)): ?>
        <div style="text-align:center;color:#64748b;font-size:13px;margin-top:40px">Belum ada pesan</div>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
        <div>
          <div class="msg-bubble <?= e($msg['sender_type']) ?>">
            <?= nl2br(e($msg['message'] ?? '')) ?>
            <?php if ($msg['image']): ?><br><img src="<?= BASE_URL ?>/uploads/<?= e($msg['image']) ?>" style="max-width:200px;border-radius:8px;margin-top:6px"><?php endif; ?>
          </div>
          <div class="msg-time" style="text-align:<?= $msg['sender_type']==='admin'?'right':'left' ?>"><?= formatDate($msg['created_at'], 'd M H:i') ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="chat-input-area">
      <?php if (!empty($templates)): ?>
      <div class="templates-bar">
        <?php foreach ($templates as $tpl): ?>
        <button type="button" class="template-btn" onclick="useTemplate(<?= htmlspecialchars(json_encode($tpl['message']), ENT_QUOTES) ?>)"><?= e($tpl['title']) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="chat-input-row">
        <textarea id="msgInput" rows="2" placeholder="Tulis balasan..." onkeydown="if(event.ctrlKey&&event.key==='Enter')sendReply()"></textarea>
        <button class="btn btn-primary" style="min-width:80px" onclick="sendReply()">Kirim</button>
      </div>
      <div style="font-size:11px;color:#64748b;margin-top:6px">Ctrl+Enter untuk kirim</div>
    </div>
    <?php else: ?>
    <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:#64748b">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="1.5"/></svg>
      <span>Pilih percakapan untuk mulai membalas</span>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($activeRoom): ?>
<script>
const ROOM_ID = <?= (int)$activeRoomId ?>;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function useTemplate(msg) {
  document.getElementById('msgInput').value = msg;
  document.getElementById('msgInput').focus();
}

function sendReply() {
  const msg = document.getElementById('msgInput').value.trim();
  if (!msg) return;
  const form = new FormData();
  form.append('action', 'send_reply');
  form.append('room_id', ROOM_ID);
  form.append('message', msg);
  form.append('csrf_token', CSRF_TOKEN);
  fetch('', {method:'POST',body:form})
    .then(r=>r.json())
    .then(d=>{ if(d.success){ document.getElementById('msgInput').value=''; location.reload(); }});
}

// Auto scroll
const cm = document.getElementById('chatMessages');
if (cm) cm.scrollTop = cm.scrollHeight;
</script>
<?php endif; ?>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
