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

// /docs and /docs/<slug>
if ($uri === '/docs' || str_starts_with($uri, '/docs/')) {
    $slug = $uri === '/docs' ? $C['root'] : substr($uri, 6);
    $slug = preg_replace('/\.md$/', '', (string) $slug) ?? $C['root'];
    $page = docs_find($C, $slug);
    if ($page === null || !is_file(docs_path($C, $slug))) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "404 Not Found\n";
        return;
    }
    $prev = $next = null;
    foreach (docs_pages($C) as $idx => $p) {
        if ($p['slug'] === $slug) {
            $prev = $idx > 0 ? docs_pages($C)[$idx - 1] : null;
            $next = $idx < count(docs_pages($C)) - 1 ? docs_pages($C)[$idx + 1] : null;
            break;
        }
    }
    require APP_ROOT . '/markdown.php';
    $rendered = md_render(file_get_contents(docs_path($C, $slug)));
    $html = $rendered['html'];
    $headings = $rendered['headings'];
    require APP_ROOT . '/pages/docs.php';
    return;
}

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

if (str_starts_with($uri, '/docs/') && str_ends_with($uri, '.md')) {
    header('Content-Type: text/markdown; charset=utf-8');
    header('Cache-Control: ' . SEO_CACHE);
    $_GET['slug'] = substr($uri, 6, -3);
    require APP_ROOT . '/md.php';
    return;
}

if ($uri === '/') {
    header('Location: /docs', true, 302);
    return;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "404 Not Found\n";
