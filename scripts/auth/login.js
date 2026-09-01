// ── TOAST NOTIFICATION ───────────────────────────────────────────────────────
function showToast(message, type = "error") {
  const existing = document.querySelector(".sk-toast");
  if (existing) existing.remove();

  const toast = document.createElement("div");
  toast.className = `sk-toast sk-toast--${type}`;
  toast.innerHTML = `
    <span class="sk-toast__icon">${type === "success" ? "✔" : "✖"}</span>
    <span class="sk-toast__msg">${message}</span>
  `;
  document.body.appendChild(toast);

  requestAnimationFrame(() => toast.classList.add("sk-toast--show"));

  setTimeout(() => {
    toast.classList.remove("sk-toast--show");
    toast.addEventListener("transitionend", () => toast.remove(), { once: true });
  }, 3500);
}

// ── TOGGLE PASSWORD ───────────────────────────────────────────────────────────
function togglePassword(fieldId, icon) {
  const input = document.getElementById(fieldId);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

// ── LOCKOUT NOTICE ────────────────────────────────────────────────────────────
let lockoutTimer = null;

function formatTime(seconds) {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return m > 0
    ? `${m}m ${String(s).padStart(2, "0")}s`
    : `${s}s`;
}

function showLockoutNotice(seconds) {
  const notice  = document.getElementById("lockout-notice");
  const timerEl = document.getElementById("lockout-timer");

  if (lockoutTimer) clearInterval(lockoutTimer);

  let remaining = seconds;

  function tick() {
    if (remaining <= 0) {
      clearInterval(lockoutTimer);
      lockoutTimer = null;
      notice.hidden = true;
      return;
    }
    timerEl.textContent = `Try again in ${formatTime(remaining)}`;
    remaining--;
  }

  notice.hidden = false;
  tick();
  lockoutTimer = setInterval(tick, 1000);
}

function clearLockoutNotice() {
  if (lockoutTimer) {
    clearInterval(lockoutTimer);
    lockoutTimer = null;
  }
  document.getElementById("lockout-notice").hidden = true;
}

// ── LOGIN FORM SUBMIT ─────────────────────────────────────────────────────────
const form          = document.getElementById("loginForm");
const emailInput    = document.getElementById("email");
const passwordInput = document.getElementById("password");

// Clear lockout notice when the user switches to a different account
emailInput.addEventListener("input", clearLockoutNotice);

form.addEventListener("submit", (e) => {
  e.preventDefault();

  const email    = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (!email || !password) {
    showToast("Please enter email and password.");
    return;
  }

  fetch("/backend/routes/auth.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action:   "login",
      email:    email,
      password: password,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "banned") {
        showBanModal(data.reason);
        return;
      }

      if (data.status === "locked") {
        showToast(data.message);
        showLockoutNotice(data.remaining);
        return;
      }

      clearLockoutNotice();
      showToast(data.message, data.status === "success" ? "success" : "error");

      if (data.status === "success" || data.status === "unverified") {
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 1200);
      }
    })
    .catch(() => {
      showToast("Server error. Please try again.");
    });
});

// ── BAN MODAL ─────────────────────────────────────────────────────────────────
function showBanModal(reason) {
  const overlay = document.getElementById("ban-modal-overlay");
  document.getElementById("ban-modal-reason").textContent = reason || "No reason provided.";
  overlay.classList.add("is-open");
  overlay.setAttribute("aria-hidden", "false");
}

document.getElementById("ban-modal-close")?.addEventListener("click", () => {
  const overlay = document.getElementById("ban-modal-overlay");
  overlay.classList.remove("is-open");
  overlay.setAttribute("aria-hidden", "true");
});

document.getElementById("ban-modal-overlay")?.addEventListener("click", (e) => {
  if (e.target === e.currentTarget) {
    e.currentTarget.classList.remove("is-open");
    e.currentTarget.setAttribute("aria-hidden", "true");
  }
});