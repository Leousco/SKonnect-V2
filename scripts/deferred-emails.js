(() => {
  const nativeFetch = window.fetch.bind(window);
  const viewsPathIndex = window.location.pathname.indexOf("/views/");
  const appBasePath = viewsPathIndex >= 0
    ? window.location.pathname.slice(0, viewsPathIndex)
    : "";
  const dispatchUrl = new URL(
    `${appBasePath}/backend/routes/dispatch_email.php`,
    window.location.origin,
  ).href;

  window.fetch = (...args) => nativeFetch(...args).then((response) => {
    const tokens = (response.headers.get("X-SKonnect-Email-Dispatch") || "")
      .split(",")
      .map((token) => token.trim())
      .filter(Boolean);

    tokens.forEach((token) => {
      const body = new URLSearchParams({ token });
      nativeFetch(dispatchUrl, {
        method: "POST",
        body,
        credentials: "same-origin",
        keepalive: true,
      }).then(async (dispatchResponse) => {
        if (!dispatchResponse.ok || !(await dispatchResponse.json()).success) {
          console.error("A deferred email notification could not be delivered.");
        }
      }).catch((error) => {
        console.error("A deferred email notification request failed.", error);
      });
    });

    return response;
  });
})();
