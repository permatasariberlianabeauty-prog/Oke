<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/upload.php';

requireLogin();

$userId   = SessionManager::userId();
$userName = SessionManager::get('user_name', 'Member');

// AJAX: Ambil pesan terbaru
if (isGet() && isset($_GET['fetch_messages'])) {
    $lastId   = (int)getVal('last_id', 0);
    $messages = db()->fetchAll(
        'SELECT cm.*, IF(cm.sender_id = ? AND cm.sender_type = "user", 1, 0) as is_mine
         FROM chat_messages cm
         WHERE cm.user_id = ? AND cm.id > ?
         ORDER BY cm.id ASC LIMIT 50',
        'iii', [$userId, $userId, $lastId]
    );
    header('Content-Type: application/json');
    echo json_encode(['success'=>true,'messages'=>$messages,'timestamp'=>time()]);
    exit;
}

// POST: Kirim pesan
if (isPost() && isset($_POST['send_message'])) {
    CSRF::verify();
    $text = clean(postVal('message_text', ''));
    $imagePath = null;

    if (!empty($_FILES['chat_image']['name'])) {
        $up = handleUpload($_FILES['chat_image'], 'chat');
        if ($up['success']) $imagePath = $up['path'];
    }

    if (!empty($text) || !empty($imagePath)) {
        db()->execute(
            'INSERT INTO chat_messages (user_id, sender_id, sender_type, message, image_path, ip_address)
             VALUES (?,?,?,?,?,?)',
            'iiisss', [$userId, $userId, 'user', $text ?: '', $imagePath, getClientIp()]
        );
    }

    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>true]);
        exit;
    }
    redirect(BASE_URL . '/pages/chat.php');
}

// CS Status
$csStatus = getSetting('cs_status', 'online'); // online | busy | offline

// Ambil pesan awal
$initMessages = db()->fetchAll(
    'SELECT cm.*, IF(cm.sender_id = ? AND cm.sender_type = "user", 1, 0) as is_mine
     FROM chat_messages cm WHERE cm.user_id = ? ORDER BY cm.id DESC LIMIT 50',
    'ii', [$userId, $userId]
);
$initMessages = array_reverse($initMessages);
$lastId = !empty($initMessages) ? (int)end($initMessages)['id'] : 0;

$pageTitle = 'Live Chat';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container chat-page">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <div class="chat-header-info">
      <div class="chat-cs-avatar">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="#00D4FF" stroke-width="2"/><circle cx="12" cy="7" r="4" stroke="#00D4FF" stroke-width="2"/></svg>
        <span class="cs-status-dot cs-<?= e($csStatus) ?>"></span>
      </div>
      <div>
        <h1 class="page-title">Customer Service</h1>
        <span class="cs-status-text <?= e($csStatus) ?>">
          <?= ['online'=>'Online','busy'=>'Sibuk','offline'=>'Offline'][$csStatus] ?? 'Offline' ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Chat Window -->
  <div class="chat-window" id="chatWindow">
    <div class="chat-messages" id="chatMessages">
      <?php if (empty($initMessages)): ?>
      <div class="chat-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="#7B2FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <p>Mulai percakapan dengan CS kami.</p>
        <p class="text-muted">Kami siap membantu Anda!</p>
      </div>
      <?php else: ?>
      <?php foreach ($initMessages as $msg): renderChatMessage($msg, $csStatus); endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat Input -->
  <div class="chat-input-area">
    <form method="post" action="" enctype="multipart/form-data" id="chatForm" class="chat-form">
      <?= CSRF::field() ?>
      <label class="chat-attach-btn" for="chat_image_input" title="Kirim gambar">
        <input type="file" id="chat_image_input" name="chat_image" accept=".jpg,.jpeg,.png,.webp" class="hidden">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </label>
      <input type="text" name="message_text" id="chatInput" class="chat-text-input"
        placeholder="Ketik pesan..." autocomplete="off" maxlength="1000">
      <button type="submit" name="send_message" value="1" class="chat-send-btn" aria-label="Kirim">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22l-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </form>
    <div id="imagePreviewBar" class="chat-image-preview hidden">
      <img id="chatImgPreview" alt="Preview" style="max-height:60px;border-radius:8px">
      <button type="button" class="btn-icon-xs" onclick="clearChatImage()">✕</button>
    </div>
  </div>

</div>

<script>
var lastId   = <?= $lastId ?>;
var userId   = <?= $userId ?>;
var pollInt  = null;
var baseUrl  = '<?= BASE_URL ?>';

function renderMessage(msg) {
  var isMe  = parseInt(msg.is_mine) === 1;
  var side  = isMe ? 'msg-out' : 'msg-in';
  var time  = msg.created_at ? msg.created_at.substr(11,5) : '';
  var html  = '<div class="chat-bubble ' + side + '">';
  if (msg.message) html += '<p class="bubble-text">' + escapeHtml(msg.message) + '</p>';
  if (msg.image_path) {
    html += '<a href="' + baseUrl + '/uploads/' + msg.image_path + '" target="_blank" rel="noopener">';
    html += '<img src="' + baseUrl + '/uploads/' + msg.image_path + '" class="bubble-img" loading="lazy">';
    html += '</a>';
  }
  html += '<span class="bubble-time">' + time + '</span>';
  html += '</div>';
  return html;
}

function escapeHtml(text) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(text));
  return d.innerHTML;
}

function pollMessages() {
  fetch('?fetch_messages=1&last_id=' + lastId, { headers: {'X-Requested-With':'XMLHttpRequest'} })
    .then(function(r){ return r.json(); })
    .then(function(data){
      if (data.success && data.messages && data.messages.length > 0) {
        var container = document.getElementById('chatMessages');
        // Remove empty state if present
        var empty = container.querySelector('.chat-empty');
        if (empty) empty.remove();
        data.messages.forEach(function(msg){
          container.insertAdjacentHTML('beforeend', renderMessage(msg));
          lastId = Math.max(lastId, parseInt(msg.id));
        });
        scrollToBottom();
      }
    })
    .catch(function(){});
}

function scrollToBottom() {
  var win = document.getElementById('chatWindow');
  if (win) win.scrollTop = win.scrollHeight;
}

// AJAX form submit
document.getElementById('chatForm').addEventListener('submit', function(e){
  var input = document.getElementById('chatInput');
  var fileInput = document.getElementById('chat_image_input');
  var hasText  = input.value.trim() !== '';
  var hasFile  = fileInput && fileInput.files.length > 0;
  if (!hasText && !hasFile) { e.preventDefault(); return; }

  // AJAX only if no file
  if (!hasFile) {
    e.preventDefault();
    var text = input.value.trim();
    var csrfToken = document.querySelector('[name=csrf_token]').value;
    fetch('', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:'send_message=1&message_text='+encodeURIComponent(text)+'&csrf_token='+encodeURIComponent(csrfToken)
    }).then(function(){ input.value=''; pollMessages(); });
  }
});

// Image preview
document.getElementById('chat_image_input').addEventListener('change', function(){
  if (this.files && this.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e){
      document.getElementById('chatImgPreview').src = e.target.result;
      document.getElementById('imagePreviewBar').classList.remove('hidden');
    };
    reader.readAsDataURL(this.files[0]);
  }
});

function clearChatImage() {
  document.getElementById('chat_image_input').value = '';
  document.getElementById('imagePreviewBar').classList.add('hidden');
}

// Start polling
document.addEventListener('DOMContentLoaded', function(){
  scrollToBottom();
  pollInt = setInterval(pollMessages, 3000);
});

window.addEventListener('beforeunload', function(){ clearInterval(pollInt); });
</script>

<?php
function renderChatMessage(array $msg, string $csStatus): void {
    $isMe = (int)$msg['is_mine'] === 1;
    $side = $isMe ? 'msg-out' : 'msg-in';
    $time = !empty($msg['created_at']) ? substr($msg['created_at'],11,5) : '';
    echo '<div class="chat-bubble ' . $side . '">';
    if (!empty($msg['message'])) {
        echo '<p class="bubble-text">' . e($msg['message']) . '</p>';
    }
    if (!empty($msg['image_path'])) {
        echo '<a href="' . uploadUrl($msg['image_path']) . '" target="_blank" rel="noopener">';
        echo '<img src="' . uploadUrl($msg['image_path']) . '" class="bubble-img" loading="lazy">';
        echo '</a>';
    }
    echo '<span class="bubble-time">' . $time . '</span>';
    echo '</div>';
}
?>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
