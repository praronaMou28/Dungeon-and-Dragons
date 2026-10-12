<?php
require 'auth.php';
requireRole('admin');
require 'db_connect.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop = trim($_POST['crop_name']);
    $season = trim($_POST['season']);
    $plantStart = (int) $_POST['planting_start'];
    $plantEnd = (int) $_POST['planting_end'];
    $harvestStart = (int) $_POST['harvesting_start'];
    $harvestEnd = (int) $_POST['harvesting_end'];
    $notes = trim($_POST['notes']);

    $stmt = $pdo->prepare("INSERT INTO crop_calendar (crop_name, season, planting_start, planting_end, harvesting_start, harvesting_end, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$crop, $season, $plantStart, $plantEnd, $harvestStart, $harvestEnd, $notes]);
    header("Location: manage_crop_calendar.php");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM crop_calendar WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage_crop_calendar.php");
    exit;
}

$crops = $pdo->query("SELECT * FROM crop_calendar ORDER BY crop_name ASC")->fetchAll();

$monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];

include 'header.php';
?>

<h3 class="page-title"><?= t('manage_calendar.title') ?></h3>

<div class="card p-4 mb-4">
  <h5><?= t('manage_calendar.add_new') ?></h5>
  <form method="POST" class="row g-2">
    <div class="col-md-3"><input type="text" name="crop_name" class="form-control" placeholder="<?= t('manage_calendar.crop_name') ?>" required></div>
    <div class="col-md-2"><input type="text" name="season" class="form-control" placeholder="<?= t('manage_calendar.season') ?>" required></div>
    <div class="col-md-2">
      <select name="planting_start" class="form-select" required>
        <?php foreach ($monthNames as $num => $name): ?><option value="<?= $num ?>"><?= t('manage_calendar.plant_from') ?> <?= $name ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="planting_end" class="form-select" required>
        <?php foreach ($monthNames as $num => $name): ?><option value="<?= $num ?>"><?= t('manage_calendar.to') ?> <?= $name ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="harvesting_start" class="form-select" required>
        <?php foreach ($monthNames as $num => $name): ?><option value="<?= $num ?>"><?= t('manage_calendar.harvest_from') ?> <?= $name ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <select name="harvesting_end" class="form-select" required>
        <?php foreach ($monthNames as $num => $name): ?><option value="<?= $num ?>"><?= t('manage_calendar.to') ?> <?= $name ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-11"><input type="text" name="notes" class="form-control" placeholder="<?= t('manage_calendar.notes') ?>"></div>
    <div class="col-md-1"><button type="submit" class="btn btn-success w-100"><?= t('manage_calendar.add_btn') ?></button></div>
  </form>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
  <thead>
    <tr>
      <th><?= t('manage_calendar.crop_name') ?></th>
      <th><?= t('manage_calendar.season') ?></th>
      <th><?= t('calendar.planting_window') ?></th>
      <th><?= t('calendar.harvesting_window') ?></th>
      <th><?= t('manage_calendar.notes') ?></th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php if (count($crops) === 0): ?>
    <tr><td colspan="6" class="text-center text-muted py-4"><?= t('manage_calendar.empty') ?></td></tr>
  <?php else: ?>
    <?php foreach ($crops as $c): ?>
      <tr>
        <td class="fw-semibold"><?= htmlspecialchars($c['crop_name']) ?></td>
        <td><span class="pill pill-gold"><?= htmlspecialchars($c['season']) ?></span></td>
        <td><?= $monthNames[$c['planting_start']] ?> – <?= $monthNames[$c['planting_end']] ?></td>
        <td><?= $monthNames[$c['harvesting_start']] ?> – <?= $monthNames[$c['harvesting_end']] ?></td>
        <td class="text-muted"><?= htmlspecialchars($c['notes']) ?></td>
        <td class="text-end"><a href="manage_crop_calendar.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm(<?= json_encode(t('manage_calendar.confirm_delete')) ?>);"><?= t('common.delete') ?></a></td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include 'footer.php'; ?>
