<?php
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__) . '/app');
}

header_remove('X-Powered-By');

define('SEO_CACHE', 'public, max-age=3600');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . $uri;
    if ($uri !== '/' && is_file($file)) {
        return false;
    }
}

$C = require APP_ROOT . '/content.php';

// Legacy /docs/... URLs moved to /<slug> — permanent redirect for SEO.
if ($uri === '/docs' || $uri === '/docs/' . $C['root']) {
    header('Location: /' . $C['root'], true, 301);
    return;
}
if (str_starts_with($uri, '/docs/')) {
    $legacy = substr($uri, 6);
    if (docs_find($C, $legacy) !== null) {
        header('Location: /' . $legacy, true, 301);
        return;
    }
    if (str_ends_with($legacy, '.md') && docs_find($C, substr($legacy, 0, -3)) !== null) {
        header('Location: /' . $legacy, true, 301);
        return;
    }
}

// Site-wide machine-readable endpoints (before per-page resolution).
if ($uri === '/search-index.json') {
    // Rebuilt from content on every request — keep it fresh so edits show up
    // in search without waiting out a long client cache.
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    require APP_ROOT . '/search-index.php';
    return;
}
if ($uri === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: ' . SEO_CACHE);
    require APP_ROOT . '/sitemap.php';
    return;
}
if ($uri === '/llms.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: ' . SEO_CACHE);
    require APP_ROOT . '/llms.php';
    return;
}
if ($uri === '/llms-full.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: ' . SEO_CACHE);
    require APP_ROOT . '/llms-full.php';
    return;
}

// Resolve a docs page request: /<slug>, /<slug>.md, /<slug>/llms.txt, /<slug>/llms-full.txt
$slug = ltrim($uri, '/');
$variant = 'html';
if (str_ends_with($slug, '.md')) {
    $variant = 'md';
    $slug = substr($slug, 0, -3);
} elseif (str_ends_with($slug, '/llms-full.txt')) {
    $variant = 'llms-full';
    $slug = substr($slug, 0, -14);
} elseif (str_ends_with($slug, '/llms.txt')) {
    $variant = 'llms';
    $slug = substr($slug, 0, -9);
}

$page = docs_find($C, $slug);
$source = ($page !== null && is_file(docs_path($C, $slug))) ? file_get_contents(docs_path($C, $slug)) : null;

if ($variant !== 'html') {
    header('Cache-Control: ' . SEO_CACHE);
    if ($source === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "404 Not Found\n";
        return;
    }
    $S = $C['site'];
    $front = "---\ntitle: {$page['title']}\ngroup: {$page['group']}\n"
        . "url: {$S['baseUrl']}/{$slug}\n\n---\n\n";
    if ($variant === 'md') {
        header('Content-Type: text/markdown; charset=utf-8');
        echo $source;
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    require_once APP_ROOT . '/markdown.php';
    echo $front;
    if ($variant === 'llms') {
        echo md_plain($source);
    } else {
        echo $source;
    }
    return;
}

if ($uri === '/') {
    header('Location: /' . $C['root'], true, 302);
    return;
}

if ($page === null || $source === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "404 Not Found\n";
    return;
}

$prev = $next = null;
$pages = docs_pages($C);
foreach ($pages as $idx => $p) {
    if ($p['slug'] === $slug) {
        $prev = $idx > 0 ? $pages[$idx - 1] : null;
        $next = $idx < count($pages) - 1 ? $pages[$idx + 1] : null;
        break;
    }
}
require APP_ROOT . '/markdown.php';
$rendered = md_render($source);
$html = $rendered['html'];
$headings = $rendered['headings'];
require APP_ROOT . '/pages/docs.php';
