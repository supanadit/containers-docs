// containers-docs client: theme toggle, mobile sidebar, TOC scroll-spy,
// code copy buttons, client-side search over /search-index.json.

(function () {
    "use strict";

    /* ---------- theme ---------- */

    var themeBtn = document.getElementById("theme-toggle");
    function applyTheme(t) {
        document.documentElement.dataset.theme = t;
        localStorage.setItem("docs-theme", t);
    }
    if (themeBtn) {
        themeBtn.addEventListener("click", function () {
            var current = document.documentElement.dataset.theme === "dark" ? "dark" : "light";
            applyTheme(current === "dark" ? "light" : "dark");
        });
    }

    /* ---------- mobile sidebar ---------- */

    var sidebar = document.getElementById("sidebar");
    var sidebarToggle = document.getElementById("sidebar-toggle");
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
        document.addEventListener("click", function (e) {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove("open");
            }
        });
    }

    /* ---------- code copy buttons ---------- */

    document.querySelectorAll(".codeblock").forEach(function (fig) {
        var btn = document.createElement("button");
        btn.className = "code-copy";
        btn.type = "button";
        btn.textContent = "Copy";
        var cap = fig.querySelector("figcaption");
        if (cap) cap.appendChild(btn);
        btn.addEventListener("click", function () {
            var code = fig.querySelector("code");
            navigator.clipboard.writeText(code.textContent).then(function () {
                btn.textContent = "Copied";
                setTimeout(function () { btn.textContent = "Copy"; }, 1500);
            });
        });
    });

    /* ---------- TOC scroll-spy ---------- */

    var tocLinks = Array.prototype.slice.call(document.querySelectorAll("#toc-list a"));
    var headings = tocLinks
        .map(function (a) { return document.getElementById(a.getAttribute("href").slice(1)); })
        .filter(Boolean);
    if (tocLinks.length && "IntersectionObserver" in window) {
        var visible = new Set();
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) visible.add(e.target.id);
                else visible.delete(e.target.id);
            });
            var first = headings.find(function (h) { return visible.has(h.id); });
            tocLinks.forEach(function (a) {
                a.classList.toggle("current", !!first && a.getAttribute("href") === "#" + first.id);
            });
        }, { rootMargin: "-56px 0px -70% 0px" });
        headings.forEach(function (h) { observer.observe(h); });
    }

    /* ---------- search ---------- */

    var input = document.getElementById("search-input");
    var results = document.getElementById("search-results");
    if (!input || !results) return;

    var index = null;
    var selected = -1;

    function loadIndex() {
        if (index) return Promise.resolve(index);
        return fetch("/search-index.json", { cache: "no-store" }).then(function (r) {
            if (!r.ok) throw new Error("HTTP " + r.status);
            return r.json();
        }).then(function (data) {
            index = data.docs;
            return index;
        });
    }

    function snippet(doc, query) {
        var pos = doc.body.toLowerCase().indexOf(query.toLowerCase());
        if (pos < 0) return doc.body.slice(0, 90);
        var start = Math.max(0, pos - 30);
        return (start > 0 ? "…" : "") + doc.body.slice(start, start + 100);
    }

    function render(list, query) {
        if (!list.length) {
            results.innerHTML = '<div class="search-empty">No results for “' + escapeHtml(query) + '”</div>';
            results.hidden = false;
            return;
        }
        results.innerHTML = list.map(function (doc, i) {
            return '<a class="search-result" href="' + doc.url + '" data-i="' + i + '">' +
                '<span class="sr-title"><span class="sr-group">' + escapeHtml(doc.group) + "</span>" +
                escapeHtml(doc.title) + "</span>" +
                '<div class="sr-snippet">' + escapeHtml(snippet(doc, query)) + "</div></a>";
        }).join("");
        results.hidden = false;
        selected = -1;
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"]/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c];
        });
    }

    function runSearch() {
        var q = input.value.trim();
        if (q.length < 2) { results.hidden = true; return; }
        loadIndex().then(function (docs) {
            var lower = q.toLowerCase();
            var scored = [];
            docs.forEach(function (doc) {
                var score = 0;
                if (doc.title.toLowerCase().indexOf(lower) >= 0) score += 10;
                if (doc.group.toLowerCase().indexOf(lower) >= 0) score += 3;
                doc.headings.forEach(function (h) {
                    if (h.text.toLowerCase().indexOf(lower) >= 0) score += 5;
                });
                var count = 0;
                var body = doc.body.toLowerCase();
                var pos = body.indexOf(lower);
                while (pos >= 0 && count < 5) { count++; pos = body.indexOf(lower, pos + 1); }
                score += count;
                if (score > 0) scored.push({ doc: doc, score: score });
            });
            scored.sort(function (a, b) { return b.score - a.score; });
            render(scored.slice(0, 8).map(function (s) { return s.doc; }), q);
        }).catch(function () {
            // Server unreachable or restarting — show a transient message and
            // retry on the next keystroke.
            results.innerHTML = '<div class="search-empty">Search is unavailable right now…</div>';
            results.hidden = false;
        });
    }

    input.addEventListener("focus", function () { loadIndex(); });
    input.addEventListener("input", runSearch);
    document.addEventListener("keydown", function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === "k") {
            e.preventDefault();
            input.focus();
            return;
        }
        if (results.hidden) return;
        var items = results.querySelectorAll(".search-result");
        if (e.key === "Escape") { results.hidden = true; input.blur(); }
        if (!items.length) return;
        if (e.key === "ArrowDown" || e.key === "ArrowUp") {
            e.preventDefault();
            selected = e.key === "ArrowDown"
                ? Math.min(selected + 1, items.length - 1)
                : Math.max(selected - 1, 0);
            items.forEach(function (el, i) { el.classList.toggle("selected", i === selected); });
        }
        if (e.key === "Enter" && selected >= 0) {
            window.location.href = items[selected].getAttribute("href");
        }
    });
    document.addEventListener("click", function (e) {
        if (!results.contains(e.target) && e.target !== input) results.hidden = true;
    });
})();
