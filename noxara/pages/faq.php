<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';

requireLogin();

// Ambil konten dari legal_pages
$legalPage = db()->fetchOne("SELECT * FROM legal_pages WHERE slug = 'faq' LIMIT 1");
$dynamicFaqs = [];
if ($legalPage && !empty($legalPage['content'])) {
    // Coba parse JSON dari content atau tampilkan sebagai HTML
    $jsonFaqs = json_decode($legalPage['content'], true);
    if (is_array($jsonFaqs)) $dynamicFaqs = $jsonFaqs;
}

// FAQ statis (hardcoded)
$staticFaqs = [
    [
        'q' => 'Apa itu NOXARA?',
        'a' => 'NOXARA adalah platform investasi mining digital yang memungkinkan Anda mendapatkan profit harian dari paket investasi yang Anda beli. Kami menawarkan berbagai paket dengan ROI kompetitif dan sistem referral 3 level.',
    ],
    [
        'q' => 'Bagaimana cara mendaftar?',
        'a' => 'Klik tombol "Daftar" di halaman utama, isi data diri (username, email, nomor HP, nama lengkap, dan password), lalu verifikasi akun Anda. Proses registrasi hanya butuh beberapa menit.',
    ],
    [
        'q' => 'Bagaimana cara melakukan deposit?',
        'a' => 'Masuk ke menu Deposit, pilih nominal dan rekening tujuan, lakukan transfer sesuai nominal + kode unik yang diberikan, lalu upload bukti transfer. Deposit akan dikonfirmasi oleh admin dalam 1-24 jam kerja.',
    ],
    [
        'q' => 'Apa itu saldo gratis?',
        'a' => 'Saldo gratis adalah bonus yang diberikan saat pendaftaran dan melalui berbagai reward (misi, iklan, hadiah harian). Saldo gratis HANYA bisa digunakan untuk membeli paket investasi dan TIDAK bisa ditarik ke rekening.',
    ],
    [
        'q' => 'Bagaimana cara klaim profit harian?',
        'a' => 'Setiap hari setelah pukul 00:00 WIB, Anda dapat mengklik tombol "Klaim Profit Harian" di dashboard atau di halaman Paket Saya. Profit akan langsung masuk ke saldo utama Anda.',
    ],
    [
        'q' => 'Berapa lama proses withdraw?',
        'a' => 'Proses withdraw dilakukan pada jam operasional (08:00 - 20:00 WIB). Dana biasanya dikirim dalam 1-6 jam kerja setelah diajukan. Biaya admin dibebaskan sesuai level VIP Anda.',
    ],
    [
        'q' => 'Apa itu Program Referral NOXARA?',
        'a' => 'Program referral NOXARA memberikan komisi hingga 3 level. Level 1 mendapat komisi terbesar dari deposit dan pembelian paket downline langsung, Level 2 dan 3 mendapat persentase yang lebih kecil. Komisi otomatis masuk ke saldo Anda.',
    ],
    [
        'q' => 'Bagaimana cara naik level VIP?',
        'a' => 'Level VIP ditentukan otomatis berdasarkan total deposit Anda. Semakin tinggi total deposit, semakin tinggi level VIP, dan semakin rendah biaya admin serta batas minimum withdraw.',
    ],
    [
        'q' => 'Apakah dana saya aman?',
        'a' => 'Keamanan dana Anda adalah prioritas kami. Semua transaksi tercatat di ledger, dilindungi dengan enkripsi, dan memerlukan PIN khusus untuk withdraw. Kami juga menerapkan sistem autentikasi berlapis.',
    ],
    [
        'q' => 'Bagaimana jika saya lupa PIN?',
        'a' => 'Jika lupa PIN transaksi, Anda dapat menghubungi CS kami melalui Live Chat atau WhatsApp untuk proses reset PIN dengan verifikasi identitas.',
    ],
    [
        'q' => 'Apakah ada biaya untuk deposit?',
        'a' => 'Tidak ada biaya deposit dari platform kami. Namun perhatikan biaya transfer antar bank yang mungkin dikenakan oleh bank Anda. Nominal deposit akan ditambah kode unik (1-999) untuk verifikasi.',
    ],
    [
        'q' => 'Bagaimana cara menghubungi support?',
        'a' => 'Anda dapat menghubungi tim support kami melalui Live Chat (menu Chat), WhatsApp, atau Telegram yang tersedia di halaman Kontak. Jam layanan CS: Senin-Minggu 08:00 - 22:00 WIB.',
    ],
];

// Gabungkan FAQ dinamis dan statis
$allFaqs = array_merge($dynamicFaqs, $staticFaqs);

$searchQuery = clean(getVal('q', ''));
if (!empty($searchQuery)) {
    $allFaqs = array_filter($allFaqs, function($faq) use ($searchQuery) {
        return stripos($faq['q'], $searchQuery) !== false || stripos($faq['a'], $searchQuery) !== false;
    });
}

$pageTitle = 'FAQ';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">FAQ</h1>
  </div>

  <!-- Search -->
  <div class="faq-search-wrap">
    <form method="get" action="" class="form">
      <div class="input-icon-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="input-icon"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <input type="text" name="q" class="form-input input-with-icon"
          placeholder="Cari pertanyaan..." value="<?= e($searchQuery) ?>" autocomplete="off">
      </div>
    </form>
    <?php if (!empty($searchQuery)): ?>
    <a href="?" class="btn btn-sm btn-ghost">Hapus Filter</a>
    <?php endif; ?>
  </div>

  <!-- FAQ Accordion -->
  <?php if (empty($allFaqs)): ?>
  <div class="empty-state">
    <p>Tidak ada FAQ yang ditemukan untuk "<?= e($searchQuery) ?>".</p>
    <a href="?" class="btn btn-sm btn-ghost">Lihat Semua FAQ</a>
  </div>
  <?php else: ?>
  <div class="faq-accordion" id="faqAccordion">
    <?php foreach (array_values($allFaqs) as $i => $faq): ?>
    <div class="faq-item" id="faq-<?= $i ?>">
      <button class="faq-question" type="button"
        onclick="toggleFaq(<?= $i ?>)" aria-expanded="false" aria-controls="faq-ans-<?= $i ?>">
        <span><?= e($faq['q']) ?></span>
        <span class="faq-chevron">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </button>
      <div class="faq-answer" id="faq-ans-<?= $i ?>" role="region" aria-hidden="true">
        <div class="faq-answer-inner">
          <p><?= nl2br(e($faq['a'])) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Still have questions? -->
  <div class="card faq-contact-cta" style="text-align:center;margin-top:24px">
    <p>Masih ada pertanyaan?</p>
    <div class="cta-row">
      <a href="<?= BASE_URL ?>/pages/chat.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Live Chat
      </a>
      <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-outline-cyan">Hubungi Kami</a>
    </div>
  </div>

</div>

<script>
function toggleFaq(index) {
  var item   = document.getElementById('faq-' + index);
  var answer = document.getElementById('faq-ans-' + index);
  var btn    = item.querySelector('.faq-question');
  var isOpen = item.classList.contains('open');

  // Close all
  document.querySelectorAll('.faq-item.open').forEach(function(el){
    el.classList.remove('open');
    el.querySelector('.faq-answer').style.maxHeight = null;
    el.querySelector('.faq-answer').setAttribute('aria-hidden','true');
    el.querySelector('.faq-question').setAttribute('aria-expanded','false');
  });

  if (!isOpen) {
    item.classList.add('open');
    answer.style.maxHeight = answer.scrollHeight + 'px';
    answer.setAttribute('aria-hidden','false');
    btn.setAttribute('aria-expanded','true');
  }
}

// Open first FAQ by default
document.addEventListener('DOMContentLoaded', function(){
  if (document.querySelector('.faq-item')) toggleFaq(0);
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
