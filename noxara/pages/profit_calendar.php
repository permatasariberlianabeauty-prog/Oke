<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/bootstrap.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mining.php';

requireLogin();

$userId = SessionManager::userId();

$year  = (int)getVal('year', (int)date('Y'));
$month = (int)getVal('month', (int)date('m'));

// Clamp
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$prevMonth = $month - 1;
$prevYear  = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear  = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Profit per hari bulan ini
$profitByDay = db()->fetchAll(
    'SELECT claim_date, SUM(amount) as total
     FROM mining_logs WHERE user_id = ? AND YEAR(claim_date) = ? AND MONTH(claim_date) = ?
     GROUP BY claim_date',
    'iii', [$userId, $year, $month]
);

$profitMap = [];
foreach ($profitByDay as $row) {
    $profitMap[$row['claim_date']] = (float)$row['total'];
}

$totalMonthProfit = array_sum($profitMap);
$daysInMonth      = (int)date('t', mktime(0,0,0,$month,1,$year));
$firstDayOfWeek   = (int)date('N', mktime(0,0,0,$month,1,$year)); // 1=Mon 7=Sun
$today            = date('Y-m-d');

// Max daily for color scale
$maxProfit = !empty($profitMap) ? max($profitMap) : 1;

$monthNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$pageTitle = 'Kalender Profit';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-container">
  <div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-back">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <h1 class="page-title">Kalender Profit</h1>
  </div>

  <!-- Month Summary -->
  <div class="card calendar-summary">
    <div class="cal-summary-row">
      <div>
        <span class="cal-month orbitron"><?= e($monthNames[$month]) ?> <?= $year ?></span>
        <span class="cal-total">Total: <strong class="cyan"><?= e(formatRupiah($totalMonthProfit)) ?></strong></span>
      </div>
      <div class="cal-nav">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-sm btn-ghost" aria-label="Bulan sebelumnya">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="?month=<?= date('m') ?>&year=<?= date('Y') ?>" class="btn btn-sm btn-outline-cyan">Hari Ini</a>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-sm btn-ghost" aria-label="Bulan berikutnya">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>
    </div>
  </div>

  <!-- Calendar Grid -->
  <div class="card calendar-card">
    <div class="calendar-grid">
      <!-- Day headers (Mon-Sun) -->
      <?php
      $dayHeaders = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];
      foreach ($dayHeaders as $dh): ?>
      <div class="cal-head"><?= $dh ?></div>
      <?php endforeach; ?>

      <!-- Empty cells before first day -->
      <?php for ($e = 1; $e < $firstDayOfWeek; $e++): ?>
      <div class="cal-cell cal-empty"></div>
      <?php endfor; ?>

      <!-- Day cells -->
      <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $profit   = $profitMap[$dateStr] ?? 0;
        $isToday  = ($dateStr === $today);
        $hasProfit= $profit > 0;
        $intensity= $hasProfit && $maxProfit > 0 ? round($profit / $maxProfit * 100) : 0;
        $cellClass = 'cal-cell';
        if ($isToday) $cellClass .= ' cal-today';
        if ($hasProfit) $cellClass .= ' cal-has-profit';
      ?>
      <div class="<?= $cellClass ?>"
        data-date="<?= $dateStr ?>"
        data-profit="<?= e(formatRupiah($profit)) ?>"
        <?= $hasProfit ? 'onclick="showDayDetail(this)"' : '' ?>
        style="<?= $hasProfit ? '--intensity:' . $intensity . '%' : '' ?>">
        <span class="cal-day-num"><?= $d ?></span>
        <?php if ($hasProfit): ?>
        <span class="cal-profit-dot"></span>
        <span class="cal-profit-amt"><?= e(formatRupiah($profit, true)) ?></span>
        <?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Legend -->
  <div class="cal-legend">
    <span class="cal-legend-item">
      <span class="cal-legend-dot dot-none"></span> Tidak ada profit
    </span>
    <span class="cal-legend-item">
      <span class="cal-legend-dot dot-low"></span> Profit kecil
    </span>
    <span class="cal-legend-item">
      <span class="cal-legend-dot dot-high"></span> Profit besar
    </span>
    <span class="cal-legend-item">
      <span class="cal-legend-dot dot-today"></span> Hari ini
    </span>
  </div>

  <!-- Profit Detail Modal -->
  <div class="modal hidden" id="dayModal" role="dialog">
    <div class="modal-card">
      <div class="modal-header">
        <h2 class="modal-title" id="dayModalTitle">Profit Detail</h2>
        <button class="modal-close" onclick="closeDayModal()" aria-label="Tutup">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>
      <div class="modal-body" id="dayModalBody"></div>
    </div>
  </div>

</div>

<script>
var profitData = <?= json_encode(array_map(function($v){ return ['amount'=>$v]; }, $profitMap), JSON_UNESCAPED_UNICODE) ?>;

function showDayDetail(el) {
  var date   = el.dataset.date;
  var profit = el.dataset.profit;
  var parts  = date.split('-');
  var months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  var label  = parseInt(parts[2]) + ' ' + months[parseInt(parts[1])] + ' ' + parts[0];
  document.getElementById('dayModalTitle').textContent = label;
  document.getElementById('dayModalBody').innerHTML =
    '<div class="day-profit-detail">' +
    '<svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#00D4FF"/></svg>' +
    '<span class="day-profit-label">Total Profit</span>' +
    '<span class="day-profit-value orbitron cyan">' + profit + '</span>' +
    '</div>';
  document.getElementById('dayModal').classList.remove('hidden');
  document.getElementById('modal-overlay').classList.remove('hidden');
}

function closeDayModal() {
  document.getElementById('dayModal').classList.add('hidden');
  document.getElementById('modal-overlay').classList.add('hidden');
}

document.getElementById('modal-overlay').addEventListener('click', closeDayModal);
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
