/* community.js — SKonnect Public Community Feed & Thread View */

document.addEventListener("DOMContentLoaded", () => {
  // Navbar toggle
  const navbarToggle = document.getElementById("navbarToggle");
  const navbarMenu = document.getElementById("navbarMenu");
  if (navbarToggle && navbarMenu) {
    navbarToggle.addEventListener("click", () => {
      navbarMenu.classList.toggle("active");
    });
  }

  // Init feed only on community.php
  const feedGrid = document.getElementById("pub-feed-grid");
  if (feedGrid) initFeed();

  function initFeed() {
    const searchInput = document.getElementById("pub-search");
    const categorySelect = document.getElementById("pub-category");
    const statusSelect = document.getElementById("pub-status");
    const sortSelect = document.getElementById("pub-sort");
    const noResultsFilter = document.getElementById("pub-no-results-filter");

    let allCards = Array.from(feedGrid.querySelectorAll(".pub-feed-card"));

    function filterCards() {
      const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
      const category = categorySelect ? categorySelect.value : "all";
      const status = statusSelect ? statusSelect.value : "all";
      let visible = 0;

      allCards.forEach((card) => {
        const title =
          card.querySelector(".thread-title")?.textContent.toLowerCase() || "";
        const snippet =
          card.querySelector(".thread-snippet")?.textContent.toLowerCase() ||
          "";
        const cardCat = card.dataset.category || "";
        const cardSts = card.dataset.status || "";

        const show =
          (!query || title.includes(query) || snippet.includes(query)) &&
          (category === "all" || cardCat === category) &&
          (status === "all" || cardSts === status);

        card.dataset.filtered = show ? "true" : "false";
        if (show) visible++;
      });

      if (noResultsFilter)
        noResultsFilter.style.display = visible === 0 ? "block" : "none";
      currentPage = 1;
      applyPage();
    }

    function sortCards() {
      const order = sortSelect ? sortSelect.value : "newest";

      const sorted = [...allCards].sort((a, b) => {
        const pinnedA = a.dataset.pinned === "1" ? 1 : 0;
        const pinnedB = b.dataset.pinned === "1" ? 1 : 0;
        if (pinnedB !== pinnedA) return pinnedB - pinnedA;

        if (order === "comments")
          return (
            parseInt(b.dataset.comments || 0) -
            parseInt(a.dataset.comments || 0)
          );
        if (order === "supports")
          return (
            parseInt(b.dataset.supports || 0) -
            parseInt(a.dataset.supports || 0)
          );
        const da = new Date(a.dataset.date || "2000-01-01");
        const db = new Date(b.dataset.date || "2000-01-01");
        return order === "oldest" ? da - db : db - da;
      });

      sorted.forEach((card) => feedGrid.appendChild(card));
      allCards = sorted;
      filterCards();
    }

    searchInput?.addEventListener("input", filterCards);
    categorySelect?.addEventListener("change", filterCards);
    statusSelect?.addEventListener("change", filterCards);
    sortSelect?.addEventListener("change", sortCards);

    // Pagination
    const CARDS_PER_PAGE = 9;
    let currentPage = 1;

    function getFilteredCards() {
      return allCards.filter((c) => c.dataset.filtered !== "false");
    }

    function getTotalPages() {
      return Math.max(1, Math.ceil(getFilteredCards().length / CARDS_PER_PAGE));
    }

    function renderPagination() {
      const pageNumbersEl = document.getElementById("pub-page-numbers");
      const prevBtn = document.getElementById("pub-prev-btn");
      const nextBtn = document.getElementById("pub-next-btn");
      const totalPages = getTotalPages();

      if (pageNumbersEl) {
        pageNumbersEl.innerHTML = "";
        for (let i = 1; i <= totalPages; i++) {
          const btn = document.createElement("button");
          btn.className = "page-num" + (i === currentPage ? " active" : "");
          btn.textContent = i;
          btn.addEventListener("click", () => {
            currentPage = i;
            applyPage();
          });
          pageNumbersEl.appendChild(btn);
        }
      }

      if (prevBtn) prevBtn.disabled = currentPage === 1;
      if (nextBtn) nextBtn.disabled = currentPage === totalPages;
    }

    function applyPage() {
      const filtered = getFilteredCards();
      const start = (currentPage - 1) * CARDS_PER_PAGE;

      allCards.forEach((card) => {
        const idx = filtered.indexOf(card);
        card.style.display =
          idx !== -1 && idx >= start && idx < start + CARDS_PER_PAGE
            ? ""
            : "none";
      });

      renderPagination();
      window.scrollTo({ top: 0, behavior: "smooth" });
    }

    document.getElementById("pub-prev-btn")?.addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage--;
        applyPage();
      }
    });
    document.getElementById("pub-next-btn")?.addEventListener("click", () => {
      if (currentPage < getTotalPages()) {
        currentPage++;
        applyPage();
      }
    });

    sortCards(); // triggers filterCards → applyPage on load
  }

  // Carousel — runs only on public_thread_view.php
  (function initCarousel() {
    const grid = document.querySelector(".thread-images-grid");
    if (!grid) return;

    const items = Array.from(grid.querySelectorAll(".thread-image-item"));
    if (!items.length) return;

    const slides = items.map((item) => ({
      src: item.dataset.src || item.querySelector("img")?.src,
      alt: item.querySelector("img")?.alt || "",
    }));

    // Single image
    if (slides.length === 1) {
      const { src, alt } = slides[0];
      const wrap = document.createElement("div");
      wrap.className = "thread-carousel single-image-mode";
      wrap.innerHTML = `
        <div class="carousel-track-wrap">
          <div class="carousel-track">
            <div class="carousel-slide active">
              <img src="${src}" alt="${alt}">
            </div>
          </div>
        </div>`;
      grid.replaceWith(wrap);
      wrap
        .querySelector(".carousel-slide")
        .addEventListener("click", () => openLightbox(src));
      return;
    }

    // Multi-image carousel
    const carousel = document.createElement("div");
    carousel.className = "thread-carousel";
    carousel.innerHTML = `
      <div class="carousel-track-wrap">
        <div class="carousel-track">
          ${slides
            .map(
              (s, i) => `
            <div class="carousel-slide" data-index="${i}">
              <img src="${s.src}" alt="${s.alt}">
            </div>`
            )
            .join("")}
        </div>
        <button class="carousel-btn carousel-btn-prev" aria-label="Previous">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
          </svg>
        </button>
        <button class="carousel-btn carousel-btn-next" aria-label="Next">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
          </svg>
        </button>
      </div>
      <div class="carousel-dots">
        ${slides
          .map(
            (_, i) => `
          <button class="carousel-dot${i === 0 ? " active" : ""}"
                  data-index="${i}"
                  aria-label="Go to slide ${i + 1}"></button>`
          )
          .join("")}
      </div>`;

    grid.replaceWith(carousel);

    let current = 0;
    const track = carousel.querySelector(".carousel-track");
    const slideEls = carousel.querySelectorAll(".carousel-slide");
    const dotEls = carousel.querySelectorAll(".carousel-dot");
    const btnPrev = carousel.querySelector(".carousel-btn-prev");
    const btnNext = carousel.querySelector(".carousel-btn-next");

    function applyPosition(animated) {
      track.style.transition = animated
        ? "transform 0.32s cubic-bezier(0.25, 0.46, 0.45, 0.94)"
        : "none";
      track.style.transform = `translateX(${20 + current * -60}%)`;
    }

    function goTo(next) {
      next = ((next % slides.length) + slides.length) % slides.length;
      if (next === current) return;
      dotEls[current].classList.remove("active");
      slideEls[current].classList.remove("active");
      current = next;
      dotEls[current].classList.add("active");
      slideEls[current].classList.add("active");
      applyPosition(true);
    }

    slideEls[0].classList.add("active");
    applyPosition(false);

    btnPrev.addEventListener("click", () => goTo(current - 1));
    btnNext.addEventListener("click", () => goTo(current + 1));
    dotEls.forEach((dot) =>
      dot.addEventListener("click", () => goTo(parseInt(dot.dataset.index)))
    );

    carousel.setAttribute("tabindex", "0");
    carousel.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") goTo(current - 1);
      if (e.key === "ArrowRight") goTo(current + 1);
    });

    slideEls.forEach((slide, i) => {
      slide.addEventListener("click", () => {
        if (i === current) openLightbox(slides[current].src);
        else goTo(i);
      });
    });
  })();

  // Lightbox
  const lightbox = document.getElementById("lightbox-overlay");
  const lightboxImg = document.getElementById("lightbox-img");
  const lightboxClose = document.getElementById("lightbox-close");

  function openLightbox(src) {
    if (!src || !lightbox) return;
    lightboxImg.src = src;
    lightbox.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.style.display = "none";
    document.body.style.overflow = "";
    lightboxImg.src = "";
  }

  lightboxClose?.addEventListener("click", closeLightbox);
  lightbox?.addEventListener("click", (e) => {
    if (e.target === lightbox) closeLightbox();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeLightbox();
  });
});
