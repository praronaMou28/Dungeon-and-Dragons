<?php
require 'auth.php';
require 'db_connect.php';
require_once 'lang.php';
require 'review_helpers.php';
require 'illustrations.php';

touchActivity();

$totalFarmers   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
$totalBuyers    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn();
$totalConsumers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'consumer'")->fetchColumn();
$totalListings  = (int) $pdo->query("SELECT COUNT(*) FROM crop_listings WHERE status = 'available' AND quantity_kg > 0")->fetchColumn();

$featuredCrops = $pdo->query("SELECT c.*, u.name AS farmer_name, u.district, u.upazila FROM crop_listings c
                               JOIN users u ON c.farmer_id = u.id
                               WHERE c.status = 'available' AND c.quantity_kg > 0
                               ORDER BY c.created_at DESC LIMIT 3")->fetchAll();


$featuredProducts = $pdo->query("SELECT s.*, u.name AS seller_name, u.business_name FROM storefront_listings s
                                  JOIN users u ON s.buyer_id = u.id
                                  WHERE s.stock_qty > 0
                                  ORDER BY s.created_at DESC LIMIT 3")->fetchAll();

$testimonials = $pdo->query("SELECT r.*, u.name AS reviewer_name, u.role AS reviewer_role FROM reviews r
                              JOIN users u ON r.reviewer_id = u.id
                              WHERE r.comment IS NOT NULL AND r.comment != ''
                              ORDER BY r.rating DESC, r.created_at DESC LIMIT 3")->fetchAll();

include 'header.php';
?>

<div class="hero-band furrow landing-hero">
  <div class="hero-illustration-bg"><?= heroIllustration('landing') ?></div>
  <div class="landing-hero-grid">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-flower2"></i> <?= t('landing.hero.eyebrow') ?></span>
      <h1><?= t('landing.hero.title') ?></h1>
      <p class="lead"><?= t('landing.hero.sub') ?></p>
      <div class="d-flex flex-wrap gap-2 mb-4 position-relative">
        <a href="login.php?mode=register" class="btn btn-success btn-lg"><i class="bi bi-person-plus me-1"></i><?= t('landing.hero.cta_register') ?></a>
        <a href="market.php" class="btn btn-outline-success btn-lg"><i class="bi bi-basket me-1"></i><?= t('landing.hero.cta_browse') ?></a>
      </div>

      <div class="landing-stats-strip">
        <div>
          <div class="landing-stat-num"><?= $totalFarmers ?>+</div>
          <div class="landing-stat-label"><?= t('landing.stats.farmers') ?></div>
        </div>
        <div>
          <div class="landing-stat-num"><?= $totalBuyers ?>+</div>
          <div class="landing-stat-label"><?= t('landing.stats.buyers') ?></div>
        </div>
        <div>
          <div class="landing-stat-num"><?= $totalConsumers ?>+</div>
          <div class="landing-stat-label"><?= t('landing.stats.consumers') ?></div>
        </div>
        <div>
          <div class="landing-stat-num"><?= $totalListings ?>+</div>
          <div class="landing-stat-label"><?= t('landing.stats.listings') ?></div>
        </div>
      </div>
    </div>

    <div class="landing-login-card">
      <div class="card p-4 landing-auth-cta">
        <div class="text-center mb-3">
          <span class="brand-icon-badge landing-auth-icon"><i class="bi bi-flower2"></i></span>
          <h3 class="page-title mt-3 mb-1"><?= t('login.title') ?></h3>
          <p class="text-secondary mb-0" style="font-size:0.9rem;"><?= t('landing.hero.sub') ?></p>
        </div>
        <?php if (isset($_GET['expired'])): ?>
          <div class="alert alert-warning"><?= t('session.expired', ['minutes' => (int)(SESSION_TIMEOUT / 60)]) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
          <div class="alert alert-success"><?= t('login.registered_ok') ?></div>
        <?php endif; ?>
        <a href="login.php" class="btn btn-success w-100 mb-2"><i class="bi bi-box-arrow-in-right me-1"></i><?= t('login.submit') ?></a>
        <a href="login.php?mode=register" class="btn btn-outline-success w-100"><i class="bi bi-person-plus me-1"></i><?= t('login.register_here') ?></a>
      </div>
    </div>
  </div>
</div>


<div class="section-block">
  <div class="section-header">
    <span class="hero-eyebrow"><i class="bi bi-signpost-2"></i> <?= t('landing.how.eyebrow') ?></span>
    <h2><?= t('landing.how.title') ?></h2>
    <p><?= t('landing.how.sub') ?></p>
  </div>
  <div class="row g-3">
    <div class="col-md-6 col-lg-3">
      <div class="step-card">
        <span class="step-num">01</span>
        <div class="icon-tile"><i class="bi bi-person-plus"></i></div>
        <h5><?= t('landing.how.step1_title') ?></h5>
        <p><?= t('landing.how.step1_sub') ?></p>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="step-card">
        <span class="step-num">02</span>
        <div class="icon-tile gold"><i class="bi bi-flower1"></i></div>
        <h5><?= t('landing.how.step2_title') ?></h5>
        <p><?= t('landing.how.step2_sub') ?></p>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="step-card">
        <span class="step-num">03</span>
        <div class="icon-tile clay"><i class="bi bi-shield-check"></i></div>
        <h5><?= t('landing.how.step3_title') ?></h5>
        <p><?= t('landing.how.step3_sub') ?></p>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="step-card">
        <span class="step-num">04</span>
        <div class="icon-tile blue"><i class="bi bi-graph-up-arrow"></i></div>
        <h5><?= t('landing.how.step4_title') ?></h5>
        <p><?= t('landing.how.step4_sub') ?></p>
      </div>
    </div>
  </div>
</div>

<?php if (count($featuredCrops) > 0 || count($featuredProducts) > 0): ?>

<div class="section-block">
  <div class="section-header">
    <span class="hero-eyebrow"><i class="bi bi-basket2"></i> <?= t('landing.market.eyebrow') ?></span>
    <h2><?= t('landing.market.title') ?></h2>
    <p><?= t('landing.market.sub') ?></p>
  </div>
  <div class="row g-4">
    <?php foreach ($featuredCrops as $l): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card product-card">
          <div class="product-image">
            <?php if (!empty($l['image_path'])): ?>
              <img src="<?= htmlspecialchars($l['image_path']) ?>" alt="<?= htmlspecialchars($l['crop_name']) ?>">
            <?php else: ?>
              <i class="bi bi-flower1 placeholder-icon"></i>
            <?php endif; ?>
          </div>
          <div class="product-top">
            <span class="pill pill-clay mb-2"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($l['upazila']) ?>, <?= htmlspecialchars($l['district']) ?></span>
            <div class="product-name"><?= htmlspecialchars($l['crop_name']) ?></div>
            <div class="product-meta"><i class="bi bi-person me-1"></i><?= htmlspecialchars($l['farmer_name']) ?></div>
          </div>
          <div class="product-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="product-price">৳<?= number_format($l['price_per_kg'], 2) ?> <small>/ kg</small></div>
              <span class="pill pill-green"><i class="bi bi-box-seam"></i> <?= htmlspecialchars($l['quantity_kg']) ?> kg</span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php foreach ($featuredProducts as $p): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card product-card">
          <div class="product-image">
            <?php if (!empty($p['image_path'])): ?>
              <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
            <?php else: ?>
              <i class="bi bi-basket3 placeholder-icon"></i>
            <?php endif; ?>
          </div>
          <div class="product-top">
            <span class="pill pill-gold mb-2"><i class="bi bi-patch-check-fill"></i> <?= t('market.verified_seller') ?></span>
            <div class="product-name"><?= htmlspecialchars($p['product_name']) ?></div>
            <div class="product-meta"><i class="bi bi-shop me-1"></i><?= htmlspecialchars($p['business_name'] ?: $p['seller_name']) ?></div>
          </div>
          <div class="product-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="product-price">৳<?= number_format($p['price_per_unit'], 2) ?> <small>/ <?= htmlspecialchars($p['unit_label'] ?: t('common.unit')) ?></small></div>
              <span class="pill pill-green"><i class="bi bi-box-seam"></i> <?= htmlspecialchars($p['stock_qty']) ?></span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <a href="market.php" class="landing-preview-link btn btn-outline-success"><i class="bi bi-arrow-right me-1"></i><?= t('landing.market.view_all') ?></a>
</div>
<?php endif; ?>

<?php if (count($testimonials) > 0): ?>

<div class="section-block">
  <div class="section-header">
    <span class="hero-eyebrow"><i class="bi bi-chat-quote"></i> <?= t('landing.testimonials.eyebrow') ?></span>
    <h2><?= t('landing.testimonials.title') ?></h2>
    <p><?= t('landing.testimonials.sub') ?></p>
  </div>
  <div class="row g-4">
    <?php foreach ($testimonials as $rev): ?>
      <div class="col-md-6 col-lg-4">
        <div class="testimonial-card">
          <?= renderStars($rev['rating']) ?>
          <p class="testimonial-quote">&ldquo;<?= htmlspecialchars($rev['comment']) ?>&rdquo;</p>
          <div class="testimonial-author">
            <div class="avatar-circle"><?= strtoupper(substr($rev['reviewer_name'], 0, 1)) ?></div>
            <div>
              <div class="fw-semibold" style="font-size:0.9rem;color:var(--text-primary);"><?= htmlspecialchars($rev['reviewer_name']) ?></div>
              <div class="testimonial-role"><?= $rev['reviewer_role'] === 'buyer' ? t('register.role_buyer') : t('register.role_consumer') ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="section-block">
  <div class="section-header">
    <span class="hero-eyebrow"><i class="bi bi-people"></i> <?= t('landing.join.eyebrow') ?></span>
    <h2><?= t('landing.join.title') ?></h2>
    <p><?= t('landing.join.sub') ?></p>
  </div>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="cta-role-card">
        <div class="icon-tile gold"><i class="bi bi-flower1"></i></div>
        <h5><?= t('landing.join.farmer_title') ?></h5>
        <p><?= t('landing.join.farmer_sub') ?></p>
        <a href="login.php?mode=register&amp;role=farmer" class="btn btn-outline-success w-100"><?= t('landing.join.register_as', ['role' => t('register.role_farmer')]) ?></a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cta-role-card">
        <div class="icon-tile blue"><i class="bi bi-shop"></i></div>
        <h5><?= t('landing.join.buyer_title') ?></h5>
        <p><?= t('landing.join.buyer_sub') ?></p>
        <a href="login.php?mode=register&amp;role=buyer" class="btn btn-outline-success w-100"><?= t('landing.join.register_as', ['role' => t('register.role_buyer')]) ?></a>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cta-role-card">
        <div class="icon-tile clay"><i class="bi bi-basket2"></i></div>
        <h5><?= t('landing.join.consumer_title') ?></h5>
        <p><?= t('landing.join.consumer_sub') ?></p>
        <a href="login.php?mode=register&amp;role=consumer" class="btn btn-outline-success w-100"><?= t('landing.join.register_as', ['role' => t('register.role_consumer')]) ?></a>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
