<?php
/**
 * NOXARA Admin - Halaman Legal
 */
define('ROOT_PATH', dirname(__DIR__));
define('IS_ADMIN', true);
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
requireAdmin(ROLE_SUPERADMIN);

$pageTitle = 'Halaman Legal';
$adminId   = SessionManager::adminId();

$allowedSlugs = [
    'tentang-kami', 'syarat-ketentuan', 'kebijakan-privasi',
    'kebijakan-deposit', 'kebijakan-withdraw', 'risiko-investasi', 'kontak-resmi'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['csrf_token'] ?? '')) {
        setFlashPopup('error', 'Token tidak valid.');
        header('Location: ' . BASE_URL . '/admin/legal_pages.php');
        exit;
    }

    $slug     = clean($_POST['slug'] ?? '');
    $title    = clean($_POST['page_title'] ?? '');
    $content  = $_POST['content'] ?? ''; // allow HTML
    // Basic sanity: strip dangerous tags but allow HTML
    $content  = strip_tags($content, '<h1><h2><h3><h4><p><br><strong><em><ul><ol><li><a><table><thead><tbody><tr><th><td><blockquote><code><pre><hr><img><div><span>');
    $isActive = (int)($_POST['is_active'] ?? 1);

    if (in_array($slug, $allowedSlugs) && $title) {
        db()->execute(
            'INSERT INTO legal_pages (slug,title,content,is_active) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE title=?,content=?,is_active=?',
            'sssisissi', [$slug,$title,$content,$isActive,$title,$content,$isActive]
        );
        logActivity('update_legal_page', 'legal_pages', 0, "Update halaman: {$slug}");
        setFlashPopup('success', "Halaman '{$title}' berhasil disimpan.");
    } else {
        setFlashPopup('error', 'Slug atau judul tidak valid.');
    }

    header('Location: ' . BASE_URL . '/admin/legal_pages.php?slug=' . urlencode($slug));
    exit;
}

$pages = db()->fetchAll('SELECT * FROM legal_pages ORDER BY id ASC');
$pageMap = [];
foreach ($pages as $p) { $pageMap[$p['slug']] = $p; }

// Ensure all allowed slugs exist with defaults
foreach ($allowedSlugs as $slug) {
    if (!isset($pageMap[$slug])) {
        $pageMap[$slug] = [
            'slug' => $slug,
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'content' => '',
            'is_active' => 1,
        ];
    }
}

$activeSlug = $_GET['slug'] ?? $allowedSlugs[0];
if (!in_array($activeSlug, $allowedSlugs)) $activeSlug = $allowedSlugs[0];
$activePage = $pageMap[$activeSlug];

$slugLabels = [
    'tentang-kami'        => 'Tentang Kami',
    'syarat-ketentuan'    => 'Syarat & Ketentuan',
    'kebijakan-privasi'   => 'Kebijakan Privasi',
    'kebijakan-deposit'   => 'Kebijakan Deposit',
    'kebijakan-withdraw'  => 'Kebijakan Withdraw',
    'risiko-investasi'    => 'Risiko Investasi',
    'kontak-resmi'        => 'Kontak Resmi',
];

require_once INCLUDES_PATH . '/admin_header.php';
?>

<div class="admin-grid-2" style="grid-template-columns:240px 1fr">
  <!-- Page List Sidebar -->
  <div>
    <div class="admin-card" style="padding:0;overflow:hidden">
      <div style="padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06);font-weight:700;font-size:14px">Halaman Legal</div>
      <?php foreach ($allowedSlugs as $slug): ?>
      <a href="?slug=<?= urlencode($slug) ?>" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.04);text-decoration:none;transition:.15s;<?= $activeSlug===$slug?'background:rgba(0,212,255,.08);border-left:3px solid #00D4FF;color:#00D4FF':'color:#94a3b8' ?>">
        <span style="font-size:13px"><?= e($slugLabels[$slug] ?? $slug) ?></span>
        <span class="badge <?= ($pageMap[$slug]['is_active'] ?? 0)?'badge-success':'badge-error' ?>" style="font-size:10px"><?= ($pageMap[$slug]['is_active'] ?? 0)?'Aktif':'Off' ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Editor -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header">
        <h3>Edit: <?= e($slugLabels[$activeSlug] ?? $activeSlug) ?></h3>
        <span><code style="font-size:11px;color:#64748b">/pages/info.php?page=<?= e($activeSlug) ?></code></span>
      </div>
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="slug" value="<?= e($activePage['slug']) ?>">
        <div class="form-group">
          <label>Judul Halaman *</label>
          <input type="text" name="page_title" class="form-input" value="<?= e($activePage['title']) ?>" required>
        </div>
        <div class="form-group">
          <label>Konten (HTML diizinkan)</label>
          <div style="border:1px solid rgba(255,255,255,.1);border-radius:10px;overflow:hidden">
            <!-- Toolbar -->
            <div style="background:#0A0E1A;padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;gap:6px;flex-wrap:wrap">
              <button type="button" onclick="insertTag('h2')" class="editor-btn">H2</button>
              <button type="button" onclick="insertTag('h3')" class="editor-btn">H3</button>
              <button type="button" onclick="insertTag('p')" class="editor-btn">P</button>
              <button type="button" onclick="insertTag('strong')" class="editor-btn">Bold</button>
              <button type="button" onclick="insertTag('em')" class="editor-btn">Italic</button>
              <button type="button" onclick="insertTag('ul')" class="editor-btn">UL</button>
              <button type="button" onclick="insertTag('li')" class="editor-btn">LI</button>
            </div>
            <textarea name="content" id="contentEditor" class="form-input" rows="16"
              style="border:none;border-radius:0;font-family:monospace;font-size:13px;resize:vertical"><?= htmlspecialchars($activePage['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="text-xs text-muted" style="margin-top:4px">Editor sederhana. Untuk tampilan preview, gunakan tag HTML dasar.</div>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="is_active" class="form-select" style="max-width:200px">
            <option value="1" <?= ($activePage['is_active'] ?? 1)?'selected':'' ?>>Aktif / Tampil</option>
            <option value="0" <?= !($activePage['is_active'] ?? 1)?'selected':'' ?>>Nonaktif / Sembunyikan</option>
          </select>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
          <button type="submit" class="btn btn-primary" style="min-width:160px">Simpan Halaman</button>
          <a href="<?= BASE_URL ?>/pages/info.php?page=<?= urlencode($activeSlug) ?>" target="_blank" class="btn btn-ghost btn-sm">Preview ↗</a>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.editor-btn{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#94a3b8;border-radius:5px;padding:3px 8px;font-size:12px;cursor:pointer;font-family:inherit}
.editor-btn:hover{background:rgba(0,212,255,.1);color:#00D4FF}
</style>
<script>
function insertTag(tag) {
  const ta = document.getElementById('contentEditor');
  const sel = ta.value.substring(ta.selectionStart, ta.selectionEnd);
  const insert = '<' + tag + '>' + sel + '</' + tag + '>';
  const start = ta.selectionStart;
  ta.value = ta.value.substring(0, start) + insert + ta.value.substring(ta.selectionEnd);
  ta.focus();
  ta.selectionStart = ta.selectionEnd = start + insert.length;
}
</script>

<?php require_once INCLUDES_PATH . '/admin_footer.php'; ?>
