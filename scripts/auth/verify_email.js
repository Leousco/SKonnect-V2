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
  
  // ── OTP INPUT BEHAVIOUR ───────────────────────────────────────────────────────
  const inputs    = document.querySelectorAll(".otp-input");
  const otpValue  = document.getElementById("otpValue");
  const form      = document.getElementById("otpForm");
  let messageEl   = document.getElementById("message");
  const resendBtn = document.getElementById("resendBtn");
  const countdownEl = document.getElementById("countdown");
  
  // Auto-advance / backspace / paste
  inputs.forEach((input, i) => {
    input.addEventListener("input", (e) => {
      const val = e.target.value.replace(/\D/g, "");
      e.target.value = val;
      if (val && i < inputs.length - 1) inputs[i + 1].focus();
      syncOTP();
    });
  
    input.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && !input.value && i > 0) {
        inputs[i - 1].focus();
        inputs[i - 1].value = "";
        syncOTP();
      }
    });
  
    input.addEventListener("paste", (e) => {
      e.preventDefault();
      const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);
      pasted.split("").forEach((char, idx) => {
        if (inputs[idx]) inputs[idx].value = char;
      });
      const next = Math.min(pasted.length, inputs.length - 1);
      inputs[next].focus();
      syncOTP();
    });
  });
  
  function syncOTP() {
    otpValue.value = Array.from(inputs).map((i) => i.value).join("");
  }
  
  // ── AJAX VERIFY ───────────────────────────────────────────────────────────────
  form.addEventListener("submit", function (e) {
    e.preventDefault();
  
    const otp = otpValue.value;
  
    if (otp.length !== 6) {
      showToast("Please enter the complete 6-digit OTP.");
      return;
    }
  
    fetch("../../backend/routes/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "verify_otp",
        otp: otp,
      }),
    })
      .then(async (res) => {
        const text = await res.text();
        try {
          return JSON.parse(text);
        } catch (e) {
          showToast("PHP Response: " + text);
          throw e;
        }
      })
      .then((data) => {
        if (data.status === "success") {
          showToast(data.message, "success");
          setTimeout(() => {
            window.location.href = "login.php?verified=1";
          }, 1500);
        } else {
          showToast(data.message);
        }
      })
      .catch(() => showToast("Server error. Please try again."));
  });
  
  // ── RESEND OTP — timer only runs AFTER first resend ──────────────────────────
  let countdownInterval = null;
  
  // Button starts fully enabled — no timer on first load
  resendBtn.disabled = false;
  countdownEl.textContent = "";
  
  resendBtn.addEventListener("click", () => {
    if (resendBtn.disabled) return;
  
    // Disable immediately and start 60 s countdown
    resendBtn.disabled = true;
    startCountdown(60);
  
    // AJAX resend request
    fetch("../../backend/routes/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ action: "resend_otp" }),
    })
      .then((res) => res.json())
      .then((data) => {
        showToast(data.message, data.status === "success" ? "success" : "error");
      })
      .catch(() => showToast("Failed to resend OTP. Try again."));
  });
  
  function startCountdown(duration) {
    if (countdownInterval) clearInterval(countdownInterval);
  
    let remaining = duration;
    countdownEl.textContent = `(${remaining}s)`;
  
    countdownInterval = setInterval(() => {
      remaining--;
      countdownEl.textContent = `(${remaining}s)`;
  
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        countdownInterval = null;
        resendBtn.disabled = false;
        countdownEl.textContent = "";
      }
    }, 1000);
  }