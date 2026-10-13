(function (root) {
    function getPageRange(currentPage, totalPages, radius = 2) {
        const total = Math.max(0, Math.floor(Number(totalPages) || 0));
        if (total === 0) return [];

        const current = Math.min(
            total,
            Math.max(1, Math.floor(Number(currentPage) || 1)),
        );
        if (total <= 7) {
            return Array.from({ length: total }, (_, index) => index + 1);
        }

        const pages = new Set([1, total]);
        for (
            let page = Math.max(2, current - radius);
            page <= Math.min(total - 1, current + radius);
            page++
        ) {
            pages.add(page);
        }

        const range = [];
        let previous = 0;
        for (const page of [...pages].sort((a, b) => a - b)) {
            if (page - previous === 2) {
                range.push(previous + 1);
            } else if (page - previous > 2) {
                range.push("…");
            }
            range.push(page);
            previous = page;
        }

        return range;
    }

    root.SKONNECT_PAGINATION = Object.freeze({ getPageRange });
})(globalThis);
