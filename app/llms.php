<?php
// /llms.txt — machine-readable docs index.
require_once APP_ROOT . '/markdown.php';

$S = $C['site'];
echo "# {$S['name']}\n\n";
echo "> {$S['description']}\n\n";
echo "- [Containers landing]({$S['landingUrl']}): the project overview and container catalog.\n";
echo "- [Full documentation]({$S['baseUrl']}/llms-full.txt): every page in one markdown document.\n";
foreach ($C['nav'] as $group) {
    echo "\n## " . $group['title'] . "\n\n";
    foreach ($group['pages'] as $p) {
        $path = docs_path($C, $p['slug']);
        $desc = '';
        if (is_file($path)) {
            $lines = explode("\n", file_get_contents($path));
            foreach ($lines as $l) {
                $l = trim($l);
                if ($l !== '' && $l[0] !== '#' && !str_starts_with($l, '```')) {
                    $desc = md_plain($l);
                    break;
                }
            }
        }
        echo "- [{$p['title']}]({$S['baseUrl']}/docs/{$p['slug']}." . "md): " . $desc . "\n";
    }
}
