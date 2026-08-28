/* bookmarks_page.js — SKonnect My Bookmarks Page */

document.addEventListener("DOMContentLoaded", () => {
  /* ---- FILTER ---- */

  const searchInput = document.getElementById("bm-search");
  const categorySelect = document.getElementById("bm-category");
  const statusSelect = document.getElementById("bm-status");
  const cards = Array.from(document.querySelectorAll(".bm-card"));
  const noResults = document.getElementById("bm-no-results");

  function filterCards() {
    if (!cards.length) return;

    const query = searchInput?.value.toLowerCase().trim() || "";
    const category = categorySelect?.value || "all";
    const status = statusSelect?.value || "all";
    let visible = 0;

    cards.forEach((card) => {
      const title =
        card.querySelector(".ann-card-title")?.textContent.toLowerCase() || "";
      const excerpt =
        card.querySelector(".ann-card-excerpt")?.textContent.toLowerCase() ||
        "";
      const cardCat = card.dataset.category || "";
      const cardSts = card.dataset.status || "";

      const matchesSearch =
        !query || title.includes(query) || excerpt.includes(query);
      const matchesCategory = category === "all" || cardCat === category;
      const matchesStatus = status === "all" || cardSts === status;

      const show = matchesSearch && matchesCategory && matchesStatus;
      card.style.display = show ? "" : "none";
      if (show) visible++;
    });

    if (noResults) noResults.style.display = visible === 0 ? "block" : "none";
  }

  searchInput?.addEventListener("input", filterCards);
  categorySelect?.addEventListener("change", filterCards);
  statusSelect?.addEventListener("change", filterCards);

  /* ---- SUPPORT BUTTONS ---- */

  document.querySelectorAll(".support-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const threadId = btn.dataset.threadId;
      const fd = new FormData();
      fd.append("type", "thread");
      fd.append("id", threadId);

      try {
        const res = await fetch(
          "../../backend/controllers/ToggleSupportController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();
        if (data.status === "success") {
          btn.classList.toggle("active", data.supported);
          btn.title = data.supported ? "Remove support" : "I support this";
          btn.querySelector(".support-count").textContent = data.total;
        }
      } catch (e) {
        showToast("Could not update support. Try again.", "error");
      }
    });
  });

  /* ---- REMOVE BOOKMARK ---- */

  document.querySelectorAll(".bookmark-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const threadId = btn.dataset.threadId;
      const card = btn.closest(".bm-card");
      const fd = new FormData();
      fd.append("thread_id", threadId);

      try {
        const res = await fetch(
          "../../backend/controllers/ToggleBookmarkController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();

        if (data.status === "success" && !data.bookmarked) {
          // Animate out then remove card
          card.style.transition = "opacity 0.25s, transform 0.25s";
          card.style.opacity = "0";
          card.style.transform = "scale(0.97)";
          setTimeout(() => {
            card.remove();
            // Update the count badge
            const badge = document.querySelector(".bookmarks-total-badge");
            if (badge) {
              const curr = parseInt(badge.textContent) || 1;
              badge.textContent = Math.max(0, curr - 1);
            }
            // Show empty state if no cards left
            if (!document.querySelectorAll(".bm-card").length) {
              showEmptyState();
            }
          }, 280);
          showToast("Bookmark removed.", "success");
        }
      } catch (e) {
        showToast("Could not remove bookmark. Try again.", "error");
      }
    });
  });

  function showEmptyState() {
    const grid = document.getElementById("bm-grid");
    if (grid) {
      grid.innerHTML = `
          <div class="bookmarks-empty" style="grid-column: 1 / -1;">
            <div class="bookmarks-empty-icon"> 
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="#facc15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bmi-icon">
                <path d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
            </div>
            <h3>No bookmarks left</h3>
            <p>You've removed all your bookmarks.</p>
            <a href="feed_page.php" class="btn-primary-portal">Browse Community Feed</a>
          </div>
        `;
    }
  }

  /* ---- TOAST ---- */

  function showToast(msg, type = "success") {
    const toast = document.getElementById("feed-toast");
    if (!toast) return;
    toast.textContent = msg;
    toast.className = `feed-toast toast-${type} show`;
    setTimeout(() => {
      toast.className = "feed-toast";
    }, 3000);
  }
});
