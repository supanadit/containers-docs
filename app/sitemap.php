<?php
// /sitemap.xml — every docs page.
$S = $C['site'];
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (docs_pages($C) as $p): ?>
  <url>
    <loc><?= htmlspecialchars($S['baseUrl'] . '/' . $p['slug']) ?></loc>
  </url>
<?php endforeach; ?>
</urlset>
