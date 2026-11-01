<?php
// Docs page layout. Expects $C, $page (nav entry), $html (rendered body),
// $headings (TOC entries), $prev, $next.

$S = $C['site'];
$slug = $page['slug'];
$canonical = $S['baseUrl'] . '/docs/' . $slug;
$editUrl = $S['repoUrl'] . '/blob/main/container-docs/content/' . $slug . '.md';

function docs_sidebar(array $C, string $activeSlug): void
{
    foreach ($C['nav'] as $group) {
        echo '<div class="nav-group"><p class="nav-group-title">' . htmlspecialchars($group['title']) . '</p><ul>';
        foreach ($group['pages'] as $p) {
            $active = $p['slug'] === $activeSlug ? ' class="active" aria-current="page"' : '';
            echo '<li><a href="/docs/' . $p['slug'] . '"' . $active . '>' . htmlspecialchars($p['title']) . '</a></li>';
        }
        echo '</ul></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
$SEO_TITLE = $page['title'] . ' — ' . $S['name'];
$SEO_DESC = $S['description'];
$SEO_CANON = $canonical;
require APP_ROOT . '/seo.php';
?>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <button id="sidebar-toggle" class="icon-btn" aria-label="Toggle sidebar" hidden>☰</button>
        <a class="brand" href="<?= htmlspecialchars($S['landingUrl']) ?>">
            <span class="brand-mark">▣</span>
            <span><?= htmlspecialchars($S['shortName']) ?></span>
            <span class="brand-docs">Docs</span>
        </a>
        <div class="search" role="search">
            <input id="search-input" type="search" placeholder="Search docs…" autocomplete="off"
                   aria-label="Search documentation">
            <div id="search-results" class="search-results" hidden></div>
        </div>
        <nav class="header-links">
            <a href="<?= htmlspecialchars($S['landingUrl']) ?>">Home</a>
            <a href="<?= htmlspecialchars($S['repoUrl']) ?>" target="_blank" rel="noopener noreferrer">GitHub</a>
            <button id="theme-toggle" class="icon-btn" aria-label="Toggle dark mode">◐</button>
        </nav>
    </div>
</header>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <?php docs_sidebar($C, $slug); ?>
    </aside>

    <main class="content">
        <article class="doc">
            <?= $html ?>
        </article>
        <footer class="doc-footer">
            <a class="edit-link" href="<?= htmlspecialchars($editUrl) ?>" target="_blank" rel="noopener noreferrer">Edit this page on GitHub →</a>
            <nav class="pager">
                <?php if ($prev !== null): ?>
                <a class="pager-prev" href="/docs/<?= $prev['slug'] ?>"><span>← Previous</span><strong><?= htmlspecialchars($prev['title']) ?></strong></a>
                <?php else: ?><span></span><?php endif; ?>
                <?php if ($next !== null): ?>
                <a class="pager-next" href="/docs/<?= $next['slug'] ?>"><span>Next →</span><strong><?= htmlspecialchars($next['title']) ?></strong></a>
                <?php else: ?><span></span><?php endif; ?>
            </nav>
            <p class="copyright"><?= htmlspecialchars($S['copyright']) ?> · MIT License</p>
        </footer>
    </main>

    <aside class="toc">
        <p class="toc-title">On this page</p>
        <ul id="toc-list">
            <?php foreach ($headings as $h): ?>
            <li class="toc-h<?= $h['level'] ?>"><a href="#<?= $h['id'] ?>"><?= htmlspecialchars($h['text']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>
</div>

<script src="/main.js"></script>
</body>
</html>
