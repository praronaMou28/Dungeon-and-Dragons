<?php
require 'auth.php';
requireLogin();
require 'db_connect.php';
require_once 'lang.php';

$season = trim($_GET['season'] ?? '');

if ($season !== '') {
    $stmt = $pdo->prepare("SELECT * FROM crop_calendar WHERE season = ? ORDER BY crop_name ASC");
    $stmt->execute([$season]);
} else {
    $stmt = $pdo->query("SELECT * FROM crop_calendar ORDER BY crop_name ASC");
}
$crops = $stmt->fetchAll();

$allSeasons = $pdo->query("SELECT DISTINCT season FROM crop_calendar ORDER BY season ASC")->fetchAll(PDO::FETCH_COLUMN);

$monthNames = [
    1 => t('calendar.month.1'), 2 => t('calendar.month.2'), 3 => t('calendar.month.3'),
    4 => t('calendar.month.4'), 5 => t('calendar.month.5'), 6 => t('calendar.month.6'),
    7 => t('calendar.month.7'), 8 => t('calendar.month.8'), 9 => t('calendar.month.9'),
    10 => t('calendar.month.10'), 11 => t('calendar.month.11'), 12 => t('calendar.month.12'),
];

function monthInRange($current, $start, $end) {
    if ($start <= $end) {
        return $current >= $start && $current <= $end;
    }
    return $current >= $start || $current <= $end;
}

function formatMonthRange($start, $end, $names) {
    return $names[$start] . ' - ' . $names[$end];
}

$currentMonth = (int) date('n');

include 'header.php';
?>

<div class="hero-band furrow text-center">
  <span class="hero-eyebrow mx-auto"><i class="bi bi-calendar3"></i> <?= t('calendar.eyebrow') ?></span>
  <h1 class="mx-auto"><?= t('calendar.title') ?></h1>
  <p class="mx-auto"><?= t('calendar.subtitle') ?></p>

  <?php if (count($allSeasons) > 1): ?>
    <div class="filter-chip-row justify-content-center mt-3">
      <a href="crop_calendar.php" class="filter-chip <?= $season === '' ? 'active' : '' ?>" style="text-decoration:none;"><?= t('calendar.all_seasons') ?></a>
      <?php foreach ($allSeasons as $s): ?>
        <a href="crop_calendar.php?season=<?= urlencode($s) ?>" class="filter-chip <?= $season === $s ? 'active' : '' ?>" style="text-decoration:none;"><?= htmlspecialchars($s) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php if (count($crops) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-calendar3"></i>
    <h5><?= t('calendar.empty_title') ?></h5>
  </div>
<?php else: ?>
<div class="row">
  <?php foreach ($crops as $c):
      $plantingNow = monthInRange($currentMonth, $c['planting_start'], $c['planting_end']);
      $harvestingNow = monthInRange($currentMonth, $c['harvesting_start'], $c['harvesting_end']);
  ?>
    <div class="col-md-6 col-lg-4 mb-4">
      <div class="loan-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <span class="pill pill-gold"><i class="bi bi-flower1"></i> <?= htmlspecialchars($c['season']) ?></span>
          <?php if ($plantingNow): ?>
            <span class="pill pill-green"><i class="bi bi-seedling"></i> <?= t('calendar.plant_now') ?></span>
          <?php elseif ($harvestingNow): ?>
            <span class="pill pill-blue"><i class="bi bi-basket3"></i> <?= t('calendar.harvest_now') ?></span>
          <?php endif; ?>
        </div>
        <div>
          <div class="loan-card-title"><?= htmlspecialchars($c['crop_name']) ?></div>
          <?php if ($c['notes']): ?>
            <p class="text-muted mb-0" style="font-size:0.86rem;"><?= htmlspecialchars($c['notes']) ?></p>
          <?php endif; ?>
        </div>
        <div class="loan-card-meta">
          <div>
            <span class="label"><?= t('calendar.planting_window') ?></span>
            <span class="value plain" style="font-size:0.92rem;"><?= formatMonthRange($c['planting_start'], $c['planting_end'], $monthNames) ?></span>
          </div>
          <div>
            <span class="label"><?= t('calendar.harvesting_window') ?></span>
            <span class="value plain" style="font-size:0.92rem;"><?= formatMonthRange($c['harvesting_start'], $c['harvesting_end'], $monthNames) ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
