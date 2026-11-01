<?php
// /search-index.json — client-side search over page titles, headings, and body text.
require_once APP_ROOT . '/markdown.php';

$S = $C['site'];
$docs = [];

foreach (docs_pages($C) as $p) {
    $path = docs_path($C, $p['slug']);
    if (!is_file($path)) {
        continue;
    }
    $src = file_get_contents($path);
    $rendered = md_render($src);
    $docs[] = [
        'slug'     => $p['slug'],
        'title'    => $p['title'],
        'group'    => $p['group'],
        'url'      => '/' . $p['slug'],
        'headings' => array_map(fn($h) => ['text' => $h['text'], 'id' => $h['id']], $rendered['headings']),
        'body'     => mb_substr(md_plain($src), 0, 8000),
    ];
}

echo json_encode(['baseUrl' => $S['baseUrl'], 'docs' => $docs], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
