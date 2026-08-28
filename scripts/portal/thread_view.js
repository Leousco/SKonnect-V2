/* thread_view.js — SKonnect Thread View Page */

document.addEventListener("DOMContentLoaded", () => {
  /* ---- BAN MODAL HELPERS (hoisted so any future handler can reach them) ---- */

  const banOverlay = document.getElementById('ban-modal-overlay');
  const banDismiss = document.getElementById('ban-modal-dismiss');

  function showBanModal() {
    if (banOverlay) {
      banOverlay.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  function hideBanModal() {
    if (banOverlay) banOverlay.style.display = 'none';
    document.body.style.overflow = '';
  }

  banDismiss?.addEventListener('click', hideBanModal);

  /* ---- BAN ENFORCEMENT ---- */

  if (typeof USER_IS_BANNED !== 'undefined' && USER_IS_BANNED) {

    // Only auto-show once per browser session for this page.
    // sessionStorage clears when the tab is closed, so it shows again on a fresh visit.
    const BAN_SHOWN_KEY = 'banShown_thread';
    if (!sessionStorage.getItem(BAN_SHOWN_KEY)) {
      showBanModal();
      sessionStorage.setItem(BAN_SHOWN_KEY, '1');
    }

    // Mark globally so all handlers below can guard themselves
    window.__bannedUser = true;

    // Disable Thread Support button
    const tSupBtn = document.getElementById('thread-support-btn');
    if (tSupBtn) {
      tSupBtn.disabled = true;
      tSupBtn.style.opacity = '0.45';
      tSupBtn.style.cursor  = 'not-allowed';
      tSupBtn.title = 'Unavailable while your account is banned.';
    }

    // Disable Thread Bookmark button
    const tBmBtn = document.getElementById('thread-bookmark-btn');
    if (tBmBtn) {
      tBmBtn.disabled = true;
      tBmBtn.style.opacity = '0.45';
      tBmBtn.style.cursor  = 'not-allowed';
      tBmBtn.title = 'Unavailable while your account is banned.';
    }

    // Disable Report button
    const tRepBtn = document.getElementById('thread-report-btn');
    if (tRepBtn) {
      tRepBtn.disabled = true;
      tRepBtn.style.opacity = '0.45';
      tRepBtn.style.cursor  = 'not-allowed';
    }

    // Disable all comment support buttons
    document.querySelectorAll('.comment-support-btn').forEach((btn) => {
      btn.disabled = true;
      btn.style.opacity = '0.45';
      btn.style.cursor  = 'not-allowed';
    });

    // Disable all reply toggle buttons
    document.querySelectorAll('.reply-toggle-btn').forEach((btn) => {
      btn.disabled = true;
      btn.style.opacity = '0.45';
      btn.style.cursor  = 'not-allowed';
    });

    // Disable all content report buttons
    document.querySelectorAll('.content-report-btn').forEach((btn) => {
      btn.disabled = true;
      btn.style.opacity = '0.45';
      btn.style.cursor  = 'not-allowed';
    });
  }
  /* ---- DELETE MODAL ---- */

  const deleteOverlay  = document.getElementById('delete-modal-overlay');
  const deleteCancel   = document.getElementById('delete-modal-cancel');
  const deleteConfirm  = document.getElementById('delete-modal-confirm');
  const deleteLabel    = document.getElementById('delete-confirm-label');
  const deleteTitle    = document.getElementById('delete-modal-title');
  const deleteDesc     = document.getElementById('delete-modal-desc');

  let _deleteType   = null;   // 'thread' | 'comment' | 'reply'
  let _deleteTarget = null;   // numeric ID
  let _deleteEl     = null;   // DOM element to replace with tombstone on success

  const DELETE_DESCRIPTIONS = {
    thread:  'Deleting your thread will hide it from the community feed. This cannot be undone.',
    comment: 'This comment will be replaced with a placeholder. This cannot be undone.',
    reply:   'This reply will be replaced with a placeholder. This cannot be undone.',
  };

  function openDeleteModal(type, targetId, el) {
    _deleteType   = type;
    _deleteTarget = targetId;
    _deleteEl     = el;

    if (deleteTitle) deleteTitle.textContent = `Delete this ${type}?`;
    if (deleteDesc)  deleteDesc.textContent  = DELETE_DESCRIPTIONS[type] ?? '';

    deleteOverlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeDeleteModal() {
    deleteOverlay.style.display  = 'none';
    document.body.style.overflow = '';
    _deleteType   = null;
    _deleteTarget = null;
    _deleteEl     = null;
  }

  deleteCancel?.addEventListener('click', closeDeleteModal);
  deleteOverlay?.addEventListener('click', (e) => {
    if (e.target === deleteOverlay) closeDeleteModal();
  });

  deleteConfirm?.addEventListener('click', async () => {
    if (!_deleteType || !_deleteTarget) return;

    if (deleteLabel) deleteLabel.textContent = 'Deleting…';
    deleteConfirm.disabled = true;

    const fd = new FormData();
    fd.append('type',      _deleteType);
    fd.append('target_id', _deleteTarget);

    try {
      const res  = await fetch('../../backend/controllers/DeleteContentController.php', {
        method: 'POST', body: fd,
      });
      const data = await res.json();

      if (data.status === 'success') {
        // Snapshot state BEFORE closeDeleteModal() nulls the shared variables
        const deletedType = _deleteType;
        const deletedEl   = _deleteEl;

        closeDeleteModal();

        if (deletedType === 'thread') {
          // Redirect back to feed — thread is gone
          showToast('Thread deleted. Redirecting…', 'success');
          setTimeout(() => { window.location.href = 'feed_page.php'; }, 1500);
          return;
        }

        // Replace comment / reply DOM element with the author-tombstone
        if (deletedEl) {
          const isReply  = deletedType === 'reply';
          const iconPath = 'M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25' +
                           'm-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83' +
                           'c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5' +
                           'a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z';

          const tombstoneClass = isReply
            ? 'comment-tombstone comment-tombstone--reply comment-tombstone--self'
            : 'comment-tombstone comment-tombstone--self';

          const label = isReply
            ? 'This reply has been removed by the author.'
            : 'This comment has been removed by the author.';

          // Clear the element's interior and inject tombstone
          deletedEl.innerHTML = `
            <div class="${tombstoneClass}">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="${iconPath}" />
              </svg>
              <span>${label}</span>
            </div>`;

          // Mark item as removed in CSS (mirrors PHP class)
          deletedEl.classList.add(isReply ? 'reply-item--removed' : 'comment-item--removed');

          // Update comment count if it was a top-level comment
          if (!isReply) {
            const countEl = document.getElementById('comments-count');
            if (countEl) {
              const curr = parseInt(countEl.textContent) || 0;
              if (curr > 0) countEl.textContent = curr - 1;
            }
          }
        }

        showToast(`${deletedType.charAt(0).toUpperCase() + deletedType.slice(1)} deleted.`, 'success');
      } else {
        showToast(data.message || 'Could not delete. Try again.', 'error');
        closeDeleteModal();
      }
    } catch (e) {
      showToast('Network error. Try again.', 'error');
      closeDeleteModal();
    } finally {
      if (deleteLabel) deleteLabel.textContent = 'Yes, Delete';
      deleteConfirm.disabled = false;
    }
  });

  /**
   * Bind delete buttons inside a container.
   * Safe to call multiple times — checks data-delete-bound.
   */
  function bindDeleteButtons(container) {
    // Thread-level delete (the single #thread-delete-btn)
    const threadDelBtn = container.querySelector?.('#thread-delete-btn') ??
                         document.getElementById('thread-delete-btn');
    if (threadDelBtn && !threadDelBtn.dataset.deleteBound) {
      threadDelBtn.dataset.deleteBound = '1';
      threadDelBtn.addEventListener('click', () => {
        openDeleteModal('thread', threadDelBtn.dataset.threadId, null);
      });
    }

    // Comment / reply delete buttons
    container.querySelectorAll?.('.content-delete-btn').forEach((btn) => {
      if (btn.dataset.deleteBound) return;
      btn.dataset.deleteBound = '1';

      btn.addEventListener('click', () => {
        const type     = btn.dataset.deleteType;   // 'comment' | 'reply'
        const targetId = btn.dataset.targetId;
        // Walk up to the .comment-item or .reply-item wrapper
        const itemEl   = btn.closest('.comment-item, .reply-item');
        openDeleteModal(type, targetId, itemEl);
      });
    });
  }

  // Bind on page load
  bindDeleteButtons(document);


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

  /* ---- THREAD SUPPORT ---- */

  const threadSupportBtn = document.getElementById("thread-support-btn");
  const threadSupportCount = document.getElementById("thread-support-count");

  threadSupportBtn?.addEventListener("click", async () => {
    const fd = new FormData();
    fd.append("type", "thread");
    fd.append("id", THREAD_ID);

    try {
      const res = await fetch(
        "../../backend/controllers/ToggleSupportController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();
      if (data.status === "success") {
        threadSupportBtn.classList.toggle("active", data.supported);
        threadSupportBtn.title = data.supported
          ? "Remove support"
          : "I support this";
        threadSupportCount.textContent = data.total;
        const labelSpan = threadSupportBtn.querySelectorAll("span")[2];
        if (labelSpan)
          labelSpan.textContent = data.supported ? "Supported" : "Support";
      }
    } catch (e) {
      showToast("Could not update support.", "error");
    }
  });

  /* ---- THREAD BOOKMARK ---- */

  const threadBookmarkBtn = document.getElementById("thread-bookmark-btn");

  threadBookmarkBtn?.addEventListener("click", async () => {
    const fd = new FormData();
    fd.append("thread_id", THREAD_ID);

    try {
      const res = await fetch(
        "../../backend/controllers/ToggleBookmarkController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();
      if (data.status === "success") {
        threadBookmarkBtn.classList.toggle("active", data.bookmarked);
        threadBookmarkBtn.title = data.bookmarked
          ? "Remove bookmark"
          : "Bookmark this thread";
        const labelSpan = threadBookmarkBtn.querySelector(".bm-label");
        if (labelSpan) {
          labelSpan.textContent = data.bookmarked ? "Bookmarked" : "Bookmark";
        }
      }
    } catch (e) {
      showToast("Could not update bookmark.", "error");
    }
  });

  /* ---- COMMENT SUPPORT ---- */

  function bindCommentSupportButtons(container) {
    container.querySelectorAll(".comment-support-btn").forEach((btn) => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = "1";

      btn.addEventListener("click", async () => {
        const commentId = btn.dataset.commentId;
        const fd = new FormData();
        fd.append("type", "comment");
        fd.append("id", commentId);

        try {
          const res = await fetch(
            "../../backend/controllers/ToggleSupportController.php",
            { method: "POST", body: fd }
          );
          const data = await res.json();
          if (data.status === "success") {
            btn.classList.toggle("active", data.supported);
            btn.querySelector(".comment-support-count").textContent =
              data.total;
          }
        } catch (e) {
          showToast("Could not update support.", "error");
        }
      });
    });
  }

  bindCommentSupportButtons(
    document.getElementById("comment-list") ?? document
  );

  /* ---- REPLY TOGGLE ---- */

  function bindReplyUI(commentEl) {
    if (commentEl.dataset.replyBound) return;
    commentEl.dataset.replyBound = "1";

    const commentId = commentEl.id.replace("comment-", "");
    const toggleBtn = commentEl.querySelector(".reply-toggle-btn");
    const replyBox = document.getElementById(`reply-box-${commentId}`);
    const cancelBtn = replyBox?.querySelector(".btn-cancel-reply");
    const submitBtn = replyBox?.querySelector(".btn-submit-reply");
    const textarea = replyBox?.querySelector(".inline-reply-textarea");
    const replyList = document.getElementById(`reply-list-${commentId}`);

    if (!toggleBtn || !replyBox) return;

    toggleBtn.addEventListener("click", () => {
      const isOpen = replyBox.style.display !== "none";
      replyBox.style.display = isOpen ? "none" : "block";
      if (!isOpen) textarea?.focus();
    });

    cancelBtn?.addEventListener("click", () => {
      replyBox.style.display = "none";
      if (textarea) textarea.value = "";
    });

    submitBtn?.addEventListener("click", async () => {
      if (window.__bannedUser) {
        showToast('You cannot reply while your account is banned.', 'error');
        return;
      }
      const message = textarea?.value.trim();
      if (!message || message.length < 2) {
        if (textarea) textarea.style.borderColor = "#e11d48";
        textarea?.focus();
        return;
      }
      if (textarea) textarea.style.borderColor = "";

      const labelEl = submitBtn.querySelector(".reply-submit-label");
      if (labelEl) labelEl.textContent = "Posting…";
      submitBtn.disabled = true;

      const fd = new FormData();
      fd.append("comment_id", commentId);
      fd.append("message", message);

      try {
        const res = await fetch(
          "../../backend/controllers/PostReplyController.php",
          { method: "POST", body: fd }
        );
        const data = await res.json();

        if (data.status === "success") {
          if (textarea) textarea.value = "";
          replyBox.style.display = "none";

          const r = data.reply;
          const dateStr = new Date(r.created_at).toLocaleString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
          });
          const initials = (r.first_name?.[0] ?? "U").toUpperCase();
          const fullName = `${r.first_name} ${r.last_name}`;

          const item = document.createElement("div");
          item.className = `reply-item${
            r.is_mod_comment ? " reply-item--mod" : ""
          }`;
          item.id = `reply-${r.id}`;
          item.innerHTML = `
            <div class="reply-avatar">${initials}</div>
            <div class="reply-body">
              <div class="comment-header">
                <span class="comment-author">${escapeHtml(fullName)}</span>
                ${
                  r.is_mod_comment
                    ? '<span class="mod-reply-badge">SK Official</span>'
                    : ""
                }
                <time class="comment-date" datetime="${
                  r.created_at
                }">${dateStr}</time>
              </div>
              <div class="comment-text">${escapeHtml(r.message).replace(
                /\n/g,
                "<br>"
              )}</div>
              <div class="comment-actions reply-actions">
                <button class="content-delete-btn" data-delete-type="reply" data-target-id="${
                  r.id
                }" title="Delete your reply">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          `;

          replyList?.appendChild(item);
          bindDeleteButtons(item);
          item.scrollIntoView({ behavior: "smooth", block: "nearest" });
          showToast("Reply posted!", "success");
        } else {
          showToast(data.message || "Could not post reply.", "error");
        }
      } catch (e) {
        showToast("Network error. Try again.", "error");
      } finally {
        if (labelEl) labelEl.textContent = "Post Reply";
        submitBtn.disabled = false;
      }
    });

    // Allow Ctrl+Enter inside reply textarea
    textarea?.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) submitBtn?.click();
    });
  }

  // Bind reply UI on all server-rendered comments
  document.querySelectorAll(".comment-item").forEach((el) => bindReplyUI(el));

  /* ---- POST COMMENT ---- */

  const replyTextarea = document.getElementById("reply-textarea");
  const replySubmit = document.getElementById("reply-submit-btn");
  const replyLabel = document.getElementById("reply-label");
  const commentList = document.getElementById("comment-list");
  const commentCount = document.getElementById("comments-count");
  const noComments = document.getElementById("no-comments");

  replySubmit?.addEventListener("click", async () => {
    if (window.__bannedUser) {
      showToast('You cannot comment while your account is banned.', 'error');
      return;
    }
    const message = replyTextarea?.value.trim();
    if (!message || message.length < 2) {
      replyTextarea.style.borderColor = "#e11d48";
      replyTextarea.focus();
      return;
    }
    replyTextarea.style.borderColor = "";

    replyLabel.textContent = "Posting…";
    replySubmit.disabled = true;

    const fd = new FormData();
    fd.append("thread_id", THREAD_ID);
    fd.append("message", message);

    try {
      const res = await fetch(
        "../../backend/controllers/PostCommentController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();

      if (data.status === "success") {
        replyTextarea.value = "";
        noComments?.remove();

        const c = data.comment;
        const dateStr = new Date(c.created_at).toLocaleString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
          hour: "numeric",
          minute: "2-digit",
          hour12: true,
        });
        const initials = (c.first_name?.[0] ?? "U").toUpperCase();
        const fullName = `${c.first_name} ${c.last_name}`;

        const item = document.createElement("div");
        item.className = `comment-item${
          c.is_mod_comment ? " comment-item--mod" : ""
        }`;
        item.id = `comment-${c.id}`;
        // Newly posted comments are always the current user's own, so no report btn
        item.innerHTML = `
          <div class="comment-avatar">${initials}</div>
          <div class="comment-body">
            <div class="comment-header">
              <span class="comment-author">${escapeHtml(fullName)}</span>
              ${
                c.is_mod_comment
                  ? '<span class="mod-reply-badge">SK Official</span>'
                  : ""
              }
              <time class="comment-date" datetime="${
                c.created_at
              }">${dateStr}</time>
            </div>
            <div class="comment-text">${escapeHtml(c.message).replace(
              /\n/g,
              "<br>"
            )}</div>
            <div class="comment-actions">
              <button class="comment-support-btn" data-comment-id="${
                c.id
              }" title="Support this comment">
                  <img src="../../assets/img/handshake-icon.png" alt="Support" class="comment-support-icon">
                <span class="comment-support-count">0</span>
              </button>
              <button class="reply-toggle-btn" data-comment-id="${
                c.id
              }" title="Reply to this comment">
                💬 Reply
              </button>
              <button class="content-delete-btn" data-delete-type="comment" data-target-id="${
                c.id
              }" title="Delete your comment">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Delete
              </button>
            </div>
            <div class="reply-list" id="reply-list-${c.id}"></div>
            <div class="inline-reply-box" id="reply-box-${
              c.id
            }" style="display:none;">
              <textarea
                class="concern-textarea reply-textarea inline-reply-textarea"
                rows="2"
                placeholder="Write a reply…"
                data-comment-id="${c.id}"
              ></textarea>
              <div class="inline-reply-footer">
                <button class="btn-cancel-reply" data-comment-id="${
                  c.id
                }">Cancel</button>
                <button class="btn-submit-reply btn-primary-portal" data-comment-id="${
                  c.id
                }">
                  <span class="reply-submit-label">Post Reply</span>
                </button>
              </div>
            </div>
          </div>
        `;

        // Insert in correct position:
        // Mod comments → top of the list.
        // Resident comments → directly after the last mod comment (newest-first among residents).
        if (commentList) {
          if (item.classList.contains("comment-item--mod")) {
            commentList.prepend(item);
          } else {
            // Find the last mod comment currently in the list
            const modItems = Array.from(
              commentList.querySelectorAll(".comment-item--mod")
            );
            if (modItems.length > 0) {
              // Insert immediately after the last mod comment
              const lastMod = modItems[modItems.length - 1];
              lastMod.insertAdjacentElement("afterend", item);
            } else {
              // No mod comments at all — prepend so it's first
              commentList.prepend(item);
            }
          }
        }
        bindCommentSupportButtons(item);
        bindReplyUI(item);
        bindDeleteButtons(item);

        if (commentCount) {
          const curr = parseInt(commentCount.textContent) || 0;
          commentCount.textContent = curr + 1;
        }

        item.scrollIntoView({ behavior: "smooth", block: "end" });
        showToast("Comment posted!", "success");
      } else {
        showToast(data.message || "Could not post comment.", "error");
      }
    } catch (e) {
      showToast("Network error. Try again.", "error");
    } finally {
      replyLabel.textContent = "Post Comment";
      replySubmit.disabled = false;
    }
  });

  // Ctrl+Enter on main comment textarea
  replyTextarea?.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) replySubmit.click();
  });

  /* ---- IMAGE CAROUSEL ---- */

  (function initCarousel() {
    const grid = document.querySelector(".thread-images-grid");
    if (!grid) return;

    const items = Array.from(grid.querySelectorAll(".thread-image-item"));
    if (!items.length) return;

    const slides = items.map((item) => ({
      src: item.dataset.src || item.querySelector("img")?.src,
      alt: item.querySelector("img")?.alt || "",
    }));

    // Single image — no carousel chrome needed
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

    // Build peek carousel — track slides continuously, peek 20% on each side
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
          <button class="carousel-dot${
            i === 0 ? " active" : ""
          }" data-index="${i}" aria-label="Go to slide ${i + 1}"></button>`
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
    dotEls.forEach((dot) => {
      dot.addEventListener("click", () => goTo(parseInt(dot.dataset.index)));
    });

    carousel.setAttribute("tabindex", "0");
    carousel.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") goTo(current - 1);
      if (e.key === "ArrowRight") goTo(current + 1);
    });

    slideEls.forEach((slide, i) => {
      slide.addEventListener("click", () => {
        if (i === current) {
          openLightbox(slides[current].src);
        } else {
          goTo(i);
        }
      });
    });
  })();

  /* ---- LIGHTBOX ---- */

  const lightbox = document.getElementById("lightbox-overlay");
  const lightboxImg = document.getElementById("lightbox-img");
  const lightboxClose = document.getElementById("lightbox-close");

  function openLightbox(src) {
    if (!src || !lightbox) return;
    lightboxImg.src = src;
    lightbox.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  lightboxClose?.addEventListener("click", closeLightbox);
  lightbox?.addEventListener("click", (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.style.display = "none";
    document.body.style.overflow = "";
    lightboxImg.src = "";
  }

  /* ---- REPORT MODAL ---- */

  const reportOverlay = document.getElementById("report-modal-overlay");
  const reportClose = document.getElementById("report-modal-close");
  const reportCancel = document.getElementById("report-modal-cancel");
  const reportSubmit = document.getElementById("report-modal-submit");
  const reportLabel = document.getElementById("report-submit-label");
  const reportNote = document.getElementById("report-note");
  const reportCatError = document.getElementById("report-category-error");

  // State: what we're currently reporting
  let _reportType = null;
  let _reportTarget = null;

  function openReportModal(reportType, targetId) {
    if (window.__bannedUser) {
      showToast('You cannot report content while your account is banned.', 'error');
      return;
    }
    _reportType = reportType;
    _reportTarget = targetId;

    // Reset state
    document
      .querySelectorAll("input[name='report-category']")
      .forEach((r) => (r.checked = false));
    if (reportNote) reportNote.value = "";
    if (reportCatError) reportCatError.textContent = "";

    const title = document.getElementById("report-modal-title");
    if (title) {
      title.textContent =
        reportType === "thread"
          ? "Report Thread"
          : reportType === "comment"
          ? "Report Comment"
          : "Report Reply";
    }

    reportOverlay.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeReportModal() {
    reportOverlay.style.display = "none";
    document.body.style.overflow = "";
    _reportType = null;
    _reportTarget = null;
  }

  reportClose?.addEventListener("click", closeReportModal);
  reportCancel?.addEventListener("click", closeReportModal);
  reportOverlay?.addEventListener("click", (e) => {
    if (e.target === reportOverlay) closeReportModal();
  });

  reportSubmit?.addEventListener("click", async () => {
    const selected = document.querySelector(
      "input[name='report-category']:checked"
    );

    if (!selected) {
      if (reportCatError)
        reportCatError.textContent = "Please select a reason for your report.";
      return;
    }
    if (reportCatError) reportCatError.textContent = "";

    reportLabel.textContent = "Submitting…";
    reportSubmit.disabled = true;

    const fd = new FormData();
    fd.append("report_type", _reportType);
    fd.append("target_id", _reportTarget);
    fd.append("category", selected.value);
    fd.append("note", reportNote?.value.trim() ?? "");

    try {
      const res = await fetch(
        "../../backend/controllers/SubmitReportController.php",
        { method: "POST", body: fd }
      );
      const data = await res.json();

      if (data.status === "success") {
        closeReportModal();
        showToast(data.message, "success");
      } else {
        showToast(data.message || "Could not submit report.", "error");
      }
    } catch (e) {
      showToast("Network error. Try again.", "error");
    } finally {
      reportLabel.textContent = "Submit Report";
      reportSubmit.disabled = false;
    }
  });

  // Escape key closes report modal
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeLightbox();
      closeReportModal();
    }
  });

  /* ---- BIND REPORT BUTTONS ---- */

  /**
   * Attaches click handlers to all .content-report-btn and
   * #thread-report-btn elements inside a given container.
   * Safe to call multiple times — checks data-report-bound.
   */
  function bindReportButtons(container) {
    container
      .querySelectorAll(
        ".content-report-btn, #thread-report-btn, .thread-report-btn"
      )
      .forEach((btn) => {
        if (btn.dataset.reportBound) return;
        btn.dataset.reportBound = "1";

        btn.addEventListener("click", () => {
          const type = btn.dataset.reportType;
          const targetId = btn.dataset.targetId;
          if (!type || !targetId) return;
          openReportModal(type, targetId);
        });
      });
  }

  // Bind on page load
  bindReportButtons(document);

  /* ---- UTIL ---- */

  function escapeHtml(str) {
    const d = document.createElement("div");
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }
});