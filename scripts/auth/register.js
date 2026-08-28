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

// ── AGE AUTO-CALCULATE ────────────────────────────────────────────────────────
const birthInput = document.getElementById("birth_date");
const ageValue   = document.getElementById("ageValue");
const ageUnit    = document.getElementById("ageUnit");
const ageHidden  = document.getElementById("age");

birthInput.addEventListener("change", function () {
  const today = new Date();
  const birth = new Date(this.value);

  if (isNaN(birth)) {
    ageValue.textContent = "—";
    ageUnit.textContent  = "";
    ageHidden.value      = "";
    checkFormReady();
    return;
  }

  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;

  if (age >= 0) {
    ageValue.textContent = age;
    ageUnit.textContent  = age === 1 ? "yr old" : "yrs old";
    ageHidden.value      = age;
  } else {
    ageValue.textContent = "—";
    ageUnit.textContent  = "";
    ageHidden.value      = "";
  }
  checkFormReady();
});

// ── PASSWORD TOGGLE ───────────────────────────────────────────────────────────
document.querySelectorAll(".toggle-pw").forEach((btn) => {
  btn.addEventListener("click", function () {
    const target = document.getElementById(this.dataset.target);
    const icon   = this.querySelector("i");
    target.type  = target.type === "password" ? "text" : "password";
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
  });
});

// ── PASSWORD MATCH INDICATOR ──────────────────────────────────────────────────
const pw  = document.getElementById("password");
const cpw = document.getElementById("confirm_password");
const msg = document.getElementById("pwMatchMsg");

function checkMatch() {
  if (!cpw.value) {
    msg.textContent = "";
    msg.className   = "pw-match-msg";
    checkFormReady();
    return;
  }
  if (pw.value === cpw.value) {
    msg.textContent = "✓ Passwords match";
    msg.className   = "pw-match-msg match";
  } else {
    msg.textContent = "✗ Passwords do not match";
    msg.className   = "pw-match-msg no-match";
  }
  checkFormReady();
}

pw.addEventListener("input", checkMatch);
cpw.addEventListener("input", checkMatch);

// ── SUBMIT BUTTON ENABLE / DISABLE ───────────────────────────────────────────
const submitBtn       = document.querySelector("#registerForm .login-btn");
const privacyCheckbox = document.getElementById("privacyCheckbox");

// Start disabled
submitBtn.disabled = true;

function checkFormReady() {
  const firstName  = document.getElementById("first_name").value.trim();
  const lastName   = document.getElementById("last_name").value.trim();
  const gender     = document.getElementById("gender").value;
  const birthDate  = document.getElementById("birth_date").value;
  const email      = document.getElementById("email").value.trim();
  const password   = pw.value;
  const confirmPw  = cpw.value;
  const privacy    = privacyCheckbox.checked;

  const allFilled =
    firstName !== "" &&
    lastName  !== "" &&
    gender    !== "" &&
    birthDate !== "" &&
    email     !== "" &&
    password  !== "" &&
    confirmPw !== "" &&
    password === confirmPw &&
    privacy;

  submitBtn.disabled = !allFilled;
}

// Wire all required fields to checkFormReady
const watchedFields = [
  "first_name", "last_name", "middle_name",
  "gender", "email", "password", "confirm_password",
];
watchedFields.forEach((id) => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener("input",  checkFormReady);
    el.addEventListener("change", checkFormReady);
  }
});
privacyCheckbox.addEventListener("change", checkFormReady);