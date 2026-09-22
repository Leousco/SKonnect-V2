
window.addEventListener("pageshow", (event) => {
  if (event.persisted) {
    window.location.reload();
  }
});


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


const form          = document.getElementById("loginForm");
const emailInput    = document.getElementById("email");
const passwordInput = document.getElementById("password");
const loginBtn = document.querySelector(".login-btn");


emailInput.addEventListener("input", clearLockoutNotice);

form.addEventListener("submit", (e) => {
  e.preventDefault();

  const email    = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (!email || !password) {
    showToast("Please enter email and password.");
    return;
  }

  
  loginBtn.disabled = true;
  loginBtn.textContent = "Logging in...";

  fetch("../../backend/routes/auth.php", {
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
        resetLoginBtn();
        showBanModal(data.reason);
        return;
      }

      if (data.status === "locked") {
        resetLoginBtn();
        showToast(data.message);
        showLockoutNotice(data.remaining);
        return;
      }

      clearLockoutNotice();
      showToast(data.message, data.status === "success" ? "success" : "error");

      if (data.status === "success" || data.status === "unverified") {
        
        setTimeout(() => {
          window.location.replace(data.redirect);
        }, 1200);
      } else {
        resetLoginBtn();
      }
    })
    .catch(() => {
      resetLoginBtn();
      showToast("Server error. Please try again.");
    });
});

function resetLoginBtn() {
  loginBtn.disabled = false;
  loginBtn.textContent = "Login";
}


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