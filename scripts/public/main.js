// ── FORCE RELOAD ON BACK/FORWARD (BFCACHE) ───────────────────────────────────
// If this page is restored from the browser's cache instead of being freshly
// requested, force a reload so the server-side session check in main.php
// runs and redirects to the dashboard if the user is already logged in.
window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});

document.addEventListener("DOMContentLoaded", function () {

    // Navbar toggle
    const navbarToggle = document.getElementById("navbarToggle");
    const navbarMenu = document.getElementById("navbarMenu");

    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener("click", function () {
            navbarMenu.classList.toggle("active");
        });
    }

    // Counter animation
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute("data-target");
            const count = +counter.innerText;
            const increment = target / 100;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 20);
            } else {
                counter.innerText = target + "+";
            }
        };
        updateCount();
    });

});