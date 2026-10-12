<?php
require_once 'lang.php';
require_once 'notifications.php';
$flashMessages = consumeFlashMessages();
$isLoggedIn = isset($_SESSION['user_id']);
$secondsLeft = $isLoggedIn && isset($_SESSION['last_activity'])
    ? max(0, SESSION_TIMEOUT - (time() - $_SESSION['last_activity']))
    : null;
?>
<?php if ($isLoggedIn): ?>
    </main>
    <footer class="text-center py-3 app-footer app-footer-inner">
      <small>Krishi Sohayota &copy; 2026 &mdash; <?= t('footer.tagline') ?></small>
    </footer>
  </div>
</div>
<?php else: ?>
</div>
<footer class="text-center py-3 mt-5 app-footer">
  <small>Krishi Sohayota &copy; 2026 &mdash; <?= t('footer.tagline') ?></small>
</footer>
<?php endif; ?>

<?php if ($isLoggedIn): ?>

<div class="modal fade" id="sessionWarningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i><?= t('session.still_there') ?></h5>
      </div>
      <div class="modal-body">
        <p class="mb-0"><?= t('session.idle_warning') ?> <strong><span id="sessionCountdown">60</span> <?= t('session.seconds') ?></strong> <?= t('session.unless_stay') ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success w-100" id="stayLoggedInBtn"><?= t('session.stay_logged_in') ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>

document.querySelectorAll('.toggle-password').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const icon = btn.querySelector('i');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    icon.classList.toggle('bi-eye', showing);
    icon.classList.toggle('bi-eye-slash', !showing);
    btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  });
});

const flashMessages = <?= json_encode($flashMessages) ?>;
const toastContainer = document.getElementById('toastContainer');
function showToast(message, type) {
  const bg = { success: 'text-bg-success', danger: 'text-bg-danger', warning: 'text-bg-warning', info: 'text-bg-info' }[type] || 'text-bg-success';
  const el = document.createElement('div');
  el.className = `toast align-items-center ${bg} border-0 mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  toastContainer.appendChild(el);
  const toast = new bootstrap.Toast(el, { delay: 5000 });
  toast.show();
}
window.addEventListener('DOMContentLoaded', () => {
  flashMessages.forEach(f => showToast(f.message, f.type));
});

const sidebarToggle = document.getElementById('sidebarToggle');
const appSidebar = document.getElementById('appSidebar');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
function closeSidebar() {
  if (appSidebar) appSidebar.classList.remove('show');
  if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
}
if (sidebarToggle && appSidebar) {
  sidebarToggle.addEventListener('click', () => {
    if (window.innerWidth >= 992) {
      
      const collapsed = document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
    } else {
      
      appSidebar.classList.toggle('show');
      sidebarBackdrop.classList.toggle('show');
    }
  });
}
if (sidebarBackdrop) {
  sidebarBackdrop.addEventListener('click', closeSidebar);
}

if (window.innerWidth >= 992 && localStorage.getItem('sidebarCollapsed') === '1') {
  document.body.classList.add('sidebar-collapsed');
}

const notifBell = document.getElementById('notifBell');
if (notifBell) {
  notifBell.addEventListener('shown.bs.dropdown', () => {
    fetch('mark_notifications_read.php').then(() => {
      const badge = notifBell.querySelector('.notif-badge');
      if (badge) badge.remove();
      document.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
    });
  });
}
const markAllBtn = document.getElementById('markAllReadBtn');
if (markAllBtn) {
  markAllBtn.addEventListener('click', (e) => {
    e.preventDefault();
    fetch('mark_notifications_read.php').then(() => {
      const badge = notifBell.querySelector('.notif-badge');
      if (badge) badge.remove();
      document.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
    });
  });
}

<?php if ($isLoggedIn): ?>

(function () {
  const TIMEOUT = <?= (int)SESSION_TIMEOUT ?>;
  const WARNING_AT = 120; 
  let secondsLeft = <?= (int)$secondsLeft ?>;
  let warningShown = false;
  let warningModal = null;
  let countdownTimer = null;

  function getModal() {
    if (!warningModal) {
      warningModal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
    }
    return warningModal;
  }

  function logoutNow() {
    window.location.href = 'login.php?expired=1';
  }

  function tick() {
    secondsLeft--;
    if (secondsLeft <= 0) {
      logoutNow();
      return;
    }
    if (secondsLeft <= WARNING_AT) {
      if (!warningShown) {
        warningShown = true;
        getModal().show();
      }
      const span = document.getElementById('sessionCountdown');
      if (span) span.textContent = secondsLeft;
    }
  }
  setInterval(tick, 1000);

  const stayBtn = document.getElementById('stayLoggedInBtn');
  if (stayBtn) {
    stayBtn.addEventListener('click', () => {
      fetch('keepalive.php').then(r => r.json()).then(data => {
        if (data.ok) {
          secondsLeft = data.timeout;
          warningShown = false;
          getModal().hide();
        } else {
          logoutNow();
        }
      }).catch(() => logoutNow());
    });
  }


  let lastPing = Date.now();
  document.addEventListener('click', () => {
    if (Date.now() - lastPing > 60000 && !warningShown) {
      lastPing = Date.now();
      fetch('keepalive.php').then(r => r.json()).then(data => {
        if (data.ok) secondsLeft = data.timeout;
      });
    }
  });
})();
<?php endif; ?>
</script>

</body>
</html>
