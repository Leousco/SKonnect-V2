document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("navbarToggle");
  const menu = document.getElementById("navbarMenu");

  if (!toggle || !menu) return;

  const setMenuOpen = (isOpen) => {
    menu.classList.toggle("active", isOpen);
    toggle.setAttribute("aria-expanded", String(isOpen));
  };

  toggle.addEventListener("click", () => {
    setMenuOpen(!menu.classList.contains("active"));
  });

  menu.addEventListener("click", (event) => {
    if (event.target.closest("a")) setMenuOpen(false);
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest("#navbar")) setMenuOpen(false);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && menu.classList.contains("active")) {
      setMenuOpen(false);
      toggle.focus();
    }
  });
});
