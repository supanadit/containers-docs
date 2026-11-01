<?php
// /llms-full.txt — the entire documentation as one markdown document.
// Every page appears with llms-friendly frontmatter, in nav order.
require_once APP_ROOT . '/markdown.php';

header('Content-Type: text/plain; charset=utf-8');

$S = $C['site'];

echo "# {$S['name']}\n\n";
echo "> {$S['description']}\n\n";
echo "Per-page markdown is also available at /docs/<slug>.md and an index at /llms.txt.\n\n";

foreach (docs_pages($C) as $p) {
    $path = docs_path($C, $p['slug']);
    if (!is_file($path)) {
        continue;
    }
    echo "---\n";
    echo "title: {$p['title']}\n";
    echo "group: {$p['group']}\n";
    echo "url: {$S['baseUrl']}/docs/{$p['slug']}.md\n";
    echo "---\n\n";
    echo file_get_contents($path);
    echo "\n\n";
}
