<?php
// /docs/<slug>.md — raw markdown of a page, with llms-friendly frontmatter.
require_once APP_ROOT . '/markdown.php';

header('Content-Type: text/markdown; charset=utf-8');

$slug = $_GET['slug'] ?? '';
$page = docs_find($C, $slug);
$path = $page === null ? null : docs_path($C, $slug);
if ($page === null || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "404 Not Found\n";
    return;
}

$S = $C['site'];
echo "---\ntitle: {$page['title']}\ngroup: {$page['group']}\n";
echo "url: {$S['baseUrl']}/docs/{$slug}\n\n";
echo "---\n\n";
echo file_get_contents($path);
