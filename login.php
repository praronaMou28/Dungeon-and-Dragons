<?php
require 'auth.php';
require 'db_connect.php';
require_once 'lang.php';


if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "_dashboard.php");
    exit;
}

$loginError = "";
$registerError = "";
$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login') {
    $mode = 'login';
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        header("Location: " . $user['role'] . "_dashboard.php");
        exit;
    } else {
        $loginError = t('login.invalid');
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register') {
    $mode = 'register';
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $district = trim($_POST['district']);
    $upazila = trim($_POST['upazila']);
    $primary_crop = $_POST['primary_crop'] ?? null;
    $land_size = $_POST['land_size'] ?? null;
    $business_name = $_POST['business_name'] ?? null;
    $business_type = $_POST['business_type'] ?? null;

    $check = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $check->execute([$phone]);

    if ($check->fetch()) {
        $registerError = t('register.phone_taken');
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, phone, password, role, district, upazila, primary_crop, land_size, business_name, business_type)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $password, $role, $district, $upazila, $primary_crop, $land_size, $business_name, $business_type]);
        $newUserId = $pdo->lastInsertId();

        if ($role === 'buyer') {
            $trade_license = trim($_POST['trade_license']);
            $nid = trim($_POST['nid']);
            $vstmt = $pdo->prepare("INSERT INTO verified_buyers (user_id, trade_license, nid, status) VALUES (?, ?, ?, 'pending')");
            $vstmt->execute([$newUserId, $trade_license, $nid]);
        }

        header("Location: login.php?registered=1");
        exit;
    }
}

$prefillRole = in_array($_GET['role'] ?? '', ['farmer', 'buyer', 'consumer'], true) ? $_GET['role'] : '';
$startActive = ($mode === 'register') ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Krishi Sohayota — <?= $mode === 'register' ? t('register.title') : t('login.title') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=<?= @filemtime(__DIR__ . '/css/style.css') ?: time() ?>" rel="stylesheet">
  <link href="css/auth.css?v=<?= @filemtime(__DIR__ . '/css/auth.css') ?: time() ?>" rel="stylesheet">
</head>
<body class="auth-body">

  <a href="index.php" class="auth-home-link"><i class="bi bi-arrow-left"></i> <?= t('auth.back_home') ?></a>

  <div class="auth-container <?= $startActive ?>" id="authContainer">

    <div class="form-box login">
      <form method="POST" action="login.php">
        <input type="hidden" name="form_action" value="login">
        <div class="auth-brand"><span class="brand-icon-badge"><i class="bi bi-flower2"></i></span></div>
        <h1><?= t('login.title') ?></h1>

        <?php if (isset($_GET['expired'])): ?>
          <div class="auth-alert warning"><i class="bi bi-clock-history"></i> <?= t('session.expired', ['minutes' => (int)(SESSION_TIMEOUT / 60)]) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
          <div class="auth-alert success"><i class="bi bi-check-circle"></i> <?= t('login.registered_ok') ?></div>
        <?php endif; ?>
        <?php if ($loginError): ?>
          <div class="auth-alert danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>

        <div class="input-box">
          <input type="text" name="phone" placeholder="<?= t('login.phone') ?>" required>
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" id="loginPassword" placeholder="<?= t('login.password') ?>" required>
          <i class="bi bi-eye toggle-password" data-target="loginPassword"></i>
        </div>

        <button type="submit" class="btn"><?= t('login.submit') ?></button>
      </form>
    </div>


    <div class="form-box register">
      <form method="POST" action="login.php" class="register-form">
        <input type="hidden" name="form_action" value="register">
        <h1><?= t('register.title') ?></h1>

        <?php if ($registerError): ?>
          <div class="auth-alert danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>

        <div class="input-box">
          <select name="role" id="role" onchange="toggleRoleFields()" required>
            <option value="" <?= $prefillRole === '' ? 'selected' : '' ?>><?= t('register.select_role') ?></option>
            <option value="farmer" <?= $prefillRole === 'farmer' ? 'selected' : '' ?>><?= t('register.role_farmer') ?></option>
            <option value="buyer" <?= $prefillRole === 'buyer' ? 'selected' : '' ?>><?= t('register.role_buyer') ?></option>
            <option value="consumer" <?= $prefillRole === 'consumer' ? 'selected' : '' ?>><?= t('register.role_consumer') ?></option>
          </select>
          <i class="bi bi-person-badge-fill"></i>
        </div>

        <div class="input-box">
          <input type="text" name="name" placeholder="<?= t('register.full_name') ?>" required>
          <i class="bi bi-person-fill"></i>
        </div>

        <div class="input-box">
          <input type="text" name="phone" placeholder="<?= t('register.phone') ?>" required>
          <i class="bi bi-telephone-fill"></i>
        </div>

        <div class="input-box">
          <input type="password" name="password" id="registerPassword" placeholder="<?= t('register.password') ?>" required>
          <i class="bi bi-eye toggle-password" data-target="registerPassword"></i>
        </div>

        <div class="input-row">
          <div class="input-box half">
            <input type="text" name="district" placeholder="<?= t('register.district') ?>" required>
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <div class="input-box half">
            <input type="text" name="upazila" placeholder="<?= t('register.upazila') ?>" required>
            <i class="bi bi-signpost-2-fill"></i>
          </div>
        </div>

        <div id="farmerFields" class="role-fields">
          <div class="input-row">
            <div class="input-box half">
              <input type="text" name="primary_crop" placeholder="<?= t('register.primary_crop') ?>">
              <i class="bi bi-flower1"></i>
            </div>
            <div class="input-box half">
              <input type="text" name="land_size" placeholder="<?= t('register.land_size_ph') ?>">
              <i class="bi bi-rulers"></i>
            </div>
          </div>
        </div>

      
        <div id="buyerFields" class="role-fields">
          <div class="input-box">
            <input type="text" name="business_name" placeholder="<?= t('register.business_name') ?>">
            <i class="bi bi-shop"></i>
          </div>
          <div class="input-box">
            <select name="business_type">
              <option value="wholesaler"><?= t('register.wholesaler') ?></option>
              <option value="retailer"><?= t('register.retailer') ?></option>
            </select>
            <i class="bi bi-tags-fill"></i>
          </div>
          <div class="input-row">
            <div class="input-box half">
              <input type="text" name="trade_license" placeholder="<?= t('register.trade_license') ?>">
              <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div class="input-box half">
              <input type="text" name="nid" placeholder="<?= t('register.nid') ?>">
              <i class="bi bi-credit-card-2-front-fill"></i>
            </div>
          </div>
          <div class="auth-alert warning small"><i class="bi bi-info-circle"></i> <?= t('register.buyer_notice') ?></div>
        </div>

        <button type="submit" class="btn"><?= t('register.submit') ?></button>
      </form>
    </div>

    <div class="toggle-box">
      <div class="toggle-panel toggle-left">
        <h1><?= t('login.title') ?></h1>
        <p><?= t('login.no_account') ?></p>
        <button class="btn register-btn" type="button"><?= t('register.title') ?></button>
      </div>
      <div class="toggle-panel toggle-right">
        <h1><?= t('register.title') ?></h1>
        <p><?= t('register.have_account') ?></p>
        <button class="btn login-btn" type="button"><?= t('login.title') ?></button>
      </div>
    </div>

  </div>

<script>
const container = document.getElementById('authContainer');
document.querySelector('.register-btn').addEventListener('click', () => {
  container.classList.add('active');
  history.replaceState(null, '', 'login.php?mode=register');
});
document.querySelector('.login-btn').addEventListener('click', () => {
  container.classList.remove('active');
  history.replaceState(null, '', 'login.php');
});

document.querySelectorAll('.toggle-password').forEach(icon => {
  icon.addEventListener('click', () => {
    const input = document.getElementById(icon.dataset.target);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('bi-eye', !isHidden);
    icon.classList.toggle('bi-eye-slash', isHidden);
  });
});

function toggleRoleFields() {
  const role = document.getElementById('role').value;
  document.getElementById('farmerFields').classList.toggle('show', role === 'farmer');
  document.getElementById('buyerFields').classList.toggle('show', role === 'buyer');
}
toggleRoleFields();
</script>
</body>
</html>
