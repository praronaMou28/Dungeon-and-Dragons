<?php

require_once 'lang.php';
require_once 'notifications.php';

$unreadCount = 0;
$recentNotifications = [];
$cartCount = 0;
$cropCartCount = 0;
$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['SCRIPT_NAME']);

if ($isLoggedIn) {
    $unreadCount = getUnreadCount($pdo, $_SESSION['user_id']);
    $recentNotifications = getRecentNotifications($pdo, $_SESSION['user_id']);

    if ($_SESSION['role'] === 'consumer') {
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM cart_items WHERE consumer_id = ?");
        $cStmt->execute([$_SESSION['user_id']]);
        $cartCount = (int) $cStmt->fetchColumn();
    }

    if ($_SESSION['role'] === 'buyer') {
        $ccStmt = $pdo->prepare("SELECT COUNT(*) FROM crop_cart_items WHERE buyer_id = ?");
        $ccStmt->execute([$_SESSION['user_id']]);
        $cropCartCount = (int) $ccStmt->fetchColumn();
    }
}

function navItem($href, $icon, $label, $current) {
    $active = ($href === $current) ? ' active' : '';
    return "<a class=\"sidebar-link{$active}\" href=\"{$href}\"><i class=\"bi {$icon}\"></i><span>{$label}</span></a>";
}


$sidebarItems = [];
if ($isLoggedIn) {
    $role = $_SESSION['role'];
    if ($role === 'farmer') {
        $sidebarItems = [
            ['farmer_dashboard.php', 'bi-speedometer2', t('nav.dashboard')],
            ['market.php', 'bi-shop', t('nav.public_market')],
            ['add_listing.php', 'bi-plus-square', t('nav.add_listing')],
            ['manage_crop_orders.php', 'bi-truck', t('nav.fulfillment')],
            ['crop_calendar.php', 'bi-calendar3', t('nav.crop_calendar')],
            ['equipment.php', 'bi-tools', t('nav.equipment')],
            ['payment_history.php', 'bi-receipt', t('nav.payments')],
            ['complaints.php', 'bi-flag', t('nav.complaints')],
        ];
    } elseif ($role === 'buyer') {
        $sidebarItems = [
            ['buyer_dashboard.php', 'bi-speedometer2', t('nav.dashboard')],
            ['browse_crops.php', 'bi-basket', t('nav.browse_crops')],
            ['crop_calendar.php', 'bi-calendar3', t('nav.crop_calendar')],
            ['crop_cart.php', 'bi-cart3', t('nav.my_crop_cart')],
            ['market.php', 'bi-shop', t('nav.public_market')],
            ['add_storefront_item.php', 'bi-shop-window', t('nav.my_storefront')],
            ['manage_storefront_orders.php', 'bi-truck', t('nav.storefront_orders')],
            ['apply_verification.php', 'bi-patch-check', t('nav.verification')],
            ['payment_history.php', 'bi-receipt', t('nav.payments')],
            ['complaints.php', 'bi-flag', t('nav.complaints')],
        ];
    } elseif ($role === 'consumer') {
        $sidebarItems = [
            ['consumer_dashboard.php', 'bi-speedometer2', t('nav.dashboard')],
            ['market.php', 'bi-shop', t('nav.marketplace')],
            ['cart.php', 'bi-cart3', t('nav.my_cart')],
            ['payment_history.php', 'bi-receipt', t('nav.payments')],
            ['complaints.php', 'bi-flag', t('nav.complaints')],
        ];
    } elseif ($role === 'admin') {
        $sidebarItems = [
            ['admin_dashboard.php', 'bi-speedometer2', t('nav.dashboard')],
            ['admin_view_farmers.php', 'bi-flower1', t('nav.farmers')],
            ['admin_view_buyers.php', 'bi-shop', t('nav.buyers')],
            ['admin_view_consumers.php', 'bi-people', t('nav.consumers')],
            ['admin_view_listings.php', 'bi-box-seam', t('nav.listings')],
            ['market.php', 'bi-basket3', t('nav.marketplace')],
            ['manage_crop_calendar.php', 'bi-calendar3', t('nav.crop_calendar')],
            ['verify_buyers.php', 'bi-patch-check', t('nav.verify_buyers')],
            ['payment_history.php', 'bi-receipt', t('nav.payments')],
            ['admin_complaints.php', 'bi-flag', t('nav.complaints')],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Krishi Sohayota</title>
  <link rel="icon" type="image/svg+xml" href="img/logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css?v=<?= @filemtime(__DIR__ . '/css/style.css') ?: time() ?>" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <?php if ($isLoggedIn): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
  <?php endif; ?>
</head>
<body class="role-<?= $isLoggedIn ? htmlspecialchars($_SESSION['role']) : 'guest' ?>">

<?php if (!$isLoggedIn): ?>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
      <span class="brand-icon-badge"><img src="img/logo.svg" alt="" width="34" height="34"></span> Krishi Sohayota
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="market.php"><i class="bi bi-shop me-1"></i><?= t('nav.public_market') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="login.php"><?= t('nav.login') ?></a></li>
        <li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2 fw-semibold" href="login.php?mode=register"><?= t('nav.register') ?></a></li>
      </ul>
    </div>
  </div>
</nav>

<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

<div class="container py-4">

<?php else: ?>

<div class="app-shell">

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <aside class="app-sidebar" id="appSidebar">
    <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'].'_dashboard.php') ?>" class="sidebar-brand">
      <span class="sidebar-brand-icon"><img src="img/logo.svg" alt="" width="40" height="40"></span>
      <span class="sidebar-brand-text">Krishi<br>Sohayota</span>
    </a>

    <nav class="sidebar-nav">
      <div class="sidebar-section-label"><?= t('nav.menu') ?></div>
      <?php foreach ($sidebarItems as $item): ?>
        <?php echo navItem($item[0], $item[1], $item[2], $currentPage); ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar-circle"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div class="sidebar-user-info">
          <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
          <div class="user-role"><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></div>
        </div>
      </div>
      <a href="logout.php" class="sidebar-logout" title="<?= t('nav.logout') ?>"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </aside>

  <div class="app-main">
    <header class="app-topbar">
      <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
      </button>

      <div class="topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="<?= t('nav.search_placeholder') ?>" disabled>
      </div>

      <div class="topbar-actions">
        <?php if ($isLoggedIn && $_SESSION['role'] === 'consumer'): ?>
          <a class="topbar-icon-btn position-relative" href="cart.php" title="My Cart">
            <i class="bi bi-cart3"></i>
            <span class="badge rounded-pill bg-danger notif-badge <?= $cartCount > 0 ? '' : 'd-none' ?>" id="cartBadge"><?= $cartCount > 9 ? '9+' : $cartCount ?></span>
          </a>
        <?php endif; ?>
        <?php if ($isLoggedIn && $_SESSION['role'] === 'buyer'): ?>
          <a class="topbar-icon-btn position-relative" href="crop_cart.php" title="My Crop Cart">
            <i class="bi bi-cart3"></i>
            <span class="badge rounded-pill bg-danger notif-badge <?= $cropCartCount > 0 ? '' : 'd-none' ?>" id="cropCartBadge"><?= $cropCartCount > 9 ? '9+' : $cropCartCount ?></span>
          </a>
        <?php endif; ?>
        <div class="dropdown">
          <a class="topbar-icon-btn position-relative" href="#" id="notifBell" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="badge rounded-pill bg-danger notif-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end notif-dropdown p-2" aria-labelledby="notifBell">
            <li class="d-flex justify-content-between align-items-center px-2 pb-2">
              <strong><?= t('notif.title') ?></strong>
              <a href="#" id="markAllReadBtn" class="small text-decoration-none"><?= t('notif.mark_all_read') ?></a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <?php if (count($recentNotifications) === 0): ?>
              <li><p class="text-muted small px-2 mb-1"><?= t('notif.empty') ?></p></li>
            <?php else: ?>
              <?php foreach ($recentNotifications as $n): ?>
                <li>
                  <a class="dropdown-item notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>" href="<?= $n['link'] ? htmlspecialchars($n['link']) : '#' ?>">
                    <div class="small"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($n['created_at']) ?></div>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

        <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'].'_dashboard.php') ?>" class="topbar-avatar" title="<?= htmlspecialchars($_SESSION['name']) ?>">
          <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
        </a>
      </div>
    </header>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <main class="app-content">
<?php endif; ?>
