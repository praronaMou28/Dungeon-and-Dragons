<?php
require 'auth.php';
requireLogin();
require 'db_connect.php';
require_once 'lang.php';

$isFarmer = $_SESSION['role'] === 'farmer';
$myId = $_SESSION['user_id'];

if ($isFarmer) {
    $stmt = $pdo->prepare("SELECT e.*, u.name AS owner_name, u.district, u.upazila FROM equipment_listings e
                            JOIN users u ON e.owner_id = u.id
                            WHERE e.availability = 'available' OR e.owner_id = ?
                            ORDER BY (e.owner_id = ?) DESC, e.created_at DESC");
    $stmt->execute([$myId, $myId]);
} else {
    $stmt = $pdo->prepare("SELECT e.*, u.name AS owner_name, u.district, u.upazila FROM equipment_listings e
                            JOIN users u ON e.owner_id = u.id
                            WHERE e.availability = 'available'
                            ORDER BY e.created_at DESC");
    $stmt->execute();
}
$equipmentList = $stmt->fetchAll();

$types = array_values(array_unique(array_map(fn($e) => $e['equipment_type'], $equipmentList)));

function equipIcon($type) {
    $t = strtolower($type);
    if (strpos($t, 'tractor') !== false) return 'bi-truck-front';
    if (strpos($t, 'tiller') !== false) return 'bi-gear-wide-connected';
    if (strpos($t, 'pump') !== false || strpos($t, 'irrigation') !== false) return 'bi-droplet';
    if (strpos($t, 'harvest') !== false) return 'bi-basket3';
    if (strpos($t, 'sprayer') !== false) return 'bi-cloud-drizzle';
    return 'bi-tools';
}

include 'header.php';
?>

<div class="hero-band furrow">
  <span class="hero-eyebrow"><i class="bi bi-tools"></i> <?= t('equipment.eyebrow') ?></span>
  <h1><?= t('equipment.title') ?></h1>
  <p><?= t('equipment.subtitle') ?></p>

  <div class="d-flex flex-column flex-md-row gap-2 mt-3">
    <div class="search-bar flex-grow-1" style="max-width:420px;">
      <i class="bi bi-search"></i>
      <input type="text" id="equipSearch" placeholder="<?= t('equipment.search_ph') ?>">
    </div>
    <?php if ($_SESSION['role'] === 'farmer'): ?>
      <a href="add_equipment.php" class="btn btn-success flex-shrink-0"><i class="bi bi-plus-lg me-1"></i><?= t('equipment.list_yours') ?></a>
    <?php endif; ?>
  </div>
</div>

<?php if (count($types) > 1): ?>
<div class="filter-chip-row mb-4" id="typeFilters">
  <button type="button" class="filter-chip active" data-type="all"><?= t('equipment.all_types') ?></button>
  <?php foreach ($types as $t): ?>
    <button type="button" class="filter-chip" data-type="<?= htmlspecialchars(strtolower($t)) ?>"><?= htmlspecialchars($t) ?></button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (count($equipmentList) === 0): ?>
  <div class="empty-state">
    <i class="bi bi-tools"></i>
    <h5><?= t('equipment.empty_title') ?></h5>
    <p class="mb-0"><?= t('equipment.empty_sub') ?></p>
  </div>
<?php else: ?>
<div class="row" id="equipGrid">
  <?php foreach ($equipmentList as $e): ?>
    <div class="col-md-6 col-lg-4 mb-4 equip-item" data-type="<?= htmlspecialchars(strtolower($e['equipment_type'])) ?>" data-search="<?= htmlspecialchars(strtolower($e['equipment_type'] . ' ' . $e['owner_name'] . ' ' . $e['upazila'] . ' ' . $e['district'])) ?>">
      <div class="equip-card" <?= ($isFarmer && $e['owner_id'] == $myId && $e['availability'] !== 'available') ? 'style="opacity:0.7;"' : '' ?>>
        <div class="equip-card-top">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-tile"><i class="bi <?= equipIcon($e['equipment_type']) ?>"></i></div>
            <div>
              <div class="equip-card-title"><?= htmlspecialchars($e['equipment_type']) ?></div>
              <div class="text-muted" style="font-size:0.78rem;"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($e['upazila']) ?>, <?= htmlspecialchars($e['district']) ?></div>
            </div>
          </div>
          <?php if ($isFarmer && $e['owner_id'] == $myId): ?>
            <a href="toggle_equipment.php?id=<?= $e['id'] ?>" class="pill <?= $e['availability'] === 'available' ? 'pill-green' : 'pill-gray' ?> flex-shrink-0 text-decoration-none" title="<?= t('equipment.toggle_hint') ?>">
              <i class="bi <?= $e['availability'] === 'available' ? 'bi-check-circle-fill' : 'bi-slash-circle' ?>"></i>
              <?= $e['availability'] === 'available' ? t('equipment.available') : t('equipment.rented') ?>
              <i class="bi bi-arrow-repeat ms-1" style="font-size:0.75em;"></i>
            </a>
          <?php elseif ($e['availability'] === 'available'): ?>
            <span class="pill pill-green flex-shrink-0"><i class="bi bi-check-circle-fill"></i> <?= t('equipment.available') ?></span>
          <?php else: ?>
            <span class="pill pill-gray flex-shrink-0"><i class="bi bi-slash-circle"></i> <?= t('equipment.rented') ?></span>
          <?php endif; ?>
        </div>

        <p class="text-muted mb-0" style="font-size:0.86rem;"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($e['contact_note'] ?: t('equipment.default_contact_note')) ?></p>

        <div class="equip-card-owner">
          <div class="avatar-circle" style="width:32px;height:32px;font-size:0.8rem;"><?= strtoupper(substr($e['owner_name'], 0, 1)) ?></div>
          <div class="flex-grow-1 min-w-0">
            <div class="owner-name"><?= t('equipment.owner') ?>: <?= htmlspecialchars($e['owner_name']) ?></div>
            <div class="owner-loc"><?= t('equipment.list_posted') ?> <?= htmlspecialchars(date('d M Y', strtotime($e['created_at']))) ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<p class="text-muted text-center d-none" id="equipEmptyMsg"><i class="bi bi-search me-1"></i><?= t('equipment.no_match') ?></p>
<?php endif; ?>

<script>
const equipSearch = document.getElementById('equipSearch');
const equipItems = document.querySelectorAll('.equip-item');
const equipEmptyMsg = document.getElementById('equipEmptyMsg');
const typeFilters = document.querySelectorAll('#typeFilters .filter-chip');
let activeType = 'all';

function applyEquipFilters() {
  const term = (equipSearch?.value || '').toLowerCase().trim();
  let visibleCount = 0;
  equipItems.forEach(item => {
    const matchesType = activeType === 'all' || item.dataset.type === activeType;
    const matchesSearch = !term || item.dataset.search.includes(term);
    const show = matchesType && matchesSearch;
    item.classList.toggle('d-none', !show);
    if (show) visibleCount++;
  });
  if (equipEmptyMsg) equipEmptyMsg.classList.toggle('d-none', visibleCount !== 0);
}

if (equipSearch) equipSearch.addEventListener('input', applyEquipFilters);
typeFilters.forEach(chip => {
  chip.addEventListener('click', () => {
    typeFilters.forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    activeType = chip.dataset.type;
    applyEquipFilters();
  });
});
</script>

<?php include 'footer.php'; ?>
