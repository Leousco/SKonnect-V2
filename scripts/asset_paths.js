(() => {
  const viewsPathIndex = window.location.pathname.indexOf("/views/");
  const appBasePath = viewsPathIndex >= 0
    ? window.location.pathname.slice(0, viewsPathIndex)
    : "";

  window.SKONNECT_ASSETS = {
    url(path) {
      const value = String(path || "").trim().replace(/\\/g, "/");
      if (!value) return "";
      if (/^(?:[a-z]+:)?\/\//i.test(value)) return value;

      const relativePath = value.replace(/^\/+/, "");
      const normalizedBase = appBasePath.replace(/^\/+|\/+$/g, "");
      const appRelativePath = normalizedBase && (
        relativePath === normalizedBase || relativePath.startsWith(`${normalizedBase}/`)
      )
        ? relativePath.slice(normalizedBase.length).replace(/^\/+/, "")
        : relativePath;

      return new URL(`${normalizedBase ? `${normalizedBase}/` : ""}${appRelativePath}`, window.location.origin + "/").href;
    },
  };
})();
