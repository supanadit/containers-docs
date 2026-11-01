<?php
// Shared SEO head partial. Expects $SEO_TITLE, $SEO_DESC, $SEO_CANON, $C.
if (!isset($C)) {
    $C = require APP_ROOT . '/content.php';
}
$S = $C['site'];
$SEO_TITLE = $SEO_TITLE ?? $S['name'];
$SEO_DESC = $SEO_DESC ?? $S['description'];
$SEO_CANON = $SEO_CANON ?? $S['baseUrl'];
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($SEO_TITLE) ?></title>
<meta name="description" content="<?= htmlspecialchars($SEO_DESC) ?>">
<link rel="canonical" href="<?= htmlspecialchars($SEO_CANON) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= htmlspecialchars($S['name']) ?>">
<meta property="og:title" content="<?= htmlspecialchars($SEO_TITLE) ?>">
<meta property="og:description" content="<?= htmlspecialchars($SEO_DESC) ?>">
<meta property="og:url" content="<?= htmlspecialchars($SEO_CANON) ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:creator" content="Supan Adit Pratama">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect x='1' y='1' width='14' height='14' rx='3' fill='%232f5df6'/%3E%3Crect x='4' y='4' width='8' height='8' rx='1.5' fill='white'/%3E%3C/svg%3E">
<link rel="stylesheet" href="/style.css">
<link rel="sitemap" type="application/xml" href="/sitemap.xml">
<link rel="alternate" type="text/plain" href="/llms.txt" title="llms.txt">
<link rel="alternate" type="text/plain" href="/llms-full.txt" title="llms-full.txt">
<script>
// Apply the stored theme before first paint to avoid a flash.
(function () {
    var t = localStorage.getItem('docs-theme');
    if (t === 'dark' || t === 'light') {
        document.documentElement.dataset.theme = t;
    }
})();
</script>
