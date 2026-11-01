<?php
// Hand-rolled markdown -> HTML renderer. Supports the subset the docs use:
// ATX headings, fenced code with language labels, tables, ordered/unordered
// lists (with nesting), blockquotes + [!NOTE]-style admonitions, hr, links,
// images, inline code, bold/italic. Everything is escaped first.

function md_slug(string $text): string
{
    $t = mb_strtolower($text);
    $t = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $t);
    $t = preg_replace('/\s+/', '-', trim($t));
    return $t ?: 'section';
}

/** Render a full markdown document. Returns ['html' => ..., 'headings' => [['id','text','level']]]. */
function md_render(string $source): array
{
    $lines = preg_split('/\r\n|\r|\n/', $source);
    $html = '';
    $headings = [];
    $i = 0;
    $n = count($lines);

    $inParagraph = false;

    $closeParagraph = function () use (&$html, &$inParagraph): void {
        if ($inParagraph) {
            $html .= "</p>\n";
            $inParagraph = false;
        }
    };

    while ($i < $n) {
        $line = $lines[$i];

        // Fenced code block.
        if (preg_match('/^```([\w+-]*)\s*$/', $line, $m)) {
            $closeParagraph();
            $lang = $m[1];
            $code = [];
            $i++;
            while ($i < $n && !preg_match('/^```\s*$/', $lines[$i])) {
                $code[] = $lines[$i];
                $i++;
            }
            $i++; // closing fence
            $label = $lang !== '' ? $lang : 'code';
            $body = md_highlight(implode("\n", $code), strtolower($lang));
            $html .= '<figure class="codeblock"><figcaption>' . htmlspecialchars($label)
                . '</figcaption><pre><code>' . $body
                . "</code></pre></figure>\n";
            continue;
        }

        // Heading.
        if (preg_match('/^(#{1,4})\s+(.*?)(?:\s+#*)?$/', $line, $m)) {
            $closeParagraph();
            $level = strlen($m[1]);
            $text = md_inline($m[2]);
            $id = md_slug(strip_tags($m[2]));
            if ($level > 1) {
                $headings[] = ['id' => $id, 'text' => strip_tags($m[2]), 'level' => $level];
            }
            $html .= "<h$level id=\"$id\">$text</h$level>\n";
            $i++;
            continue;
        }

        // Horizontal rule.
        if (preg_match('/^\s*(-{3,}|\*{3,}|_{3,})\s*$/', $line)) {
            $closeParagraph();
            $html .= "<hr>\n";
            $i++;
            continue;
        }

        // Blockquote or admonition.
        if (preg_match('/^>\s?/', $line)) {
            $closeParagraph();
            $quote = [];
            while ($i < $n && preg_match('/^>\s?/', $lines[$i])) {
                $quote[] = preg_replace('/^>\s?/', '', $lines[$i]);
                $i++;
            }
            $kind = null;
            if ($quote !== [] && preg_match('/^\[!(NOTE|TIP|WARNING|IMPORTANT|CAUTION)\]\s*(.*)$/', $quote[0], $m)) {
                $kind = strtolower($m[1]);
                $quote[0] = $m[2];
            }
            $inner = md_render_blocks($quote);
            if ($kind !== null) {
                $icon = ['note' => "\xE2\x84\xB9", 'tip' => "\xE2\x9C\xA6", 'warning' => "\xE2\x9A\xA0",
                         'important' => "\xE2\x9D\x97", 'caution' => "\xE2\x9B\x94"][$kind];
                $html .= '<aside class="admonition admonition-' . $kind . '">'
                    . '<p class="admonition-title"><span class="admonition-icon">' . $icon . '</span>'
                    . ucfirst($kind) . '</p>' . $inner . "</aside>\n";
            } else {
                $html .= "<blockquote>$inner</blockquote>\n";
            }
            continue;
        }

        // Table: header row, |---|---| separator, body rows.
        if (preg_match('/^\s*\|.*\|\s*$/', $line) && $i + 1 < $n
            && preg_match('/^\s*\|[\s:|-]+\|\s*$/', $lines[$i + 1])) {
            $closeParagraph();
            $cells = fn(string $row): array => array_map('trim', explode('|', trim($row, " \t|")));
            $head = $cells($line);
            $align = $cells($lines[$i + 1]);
            $i += 2;
            $html .= "<div class=\"tablewrap\"><table>\n<thead><tr>";
            foreach ($head as $c => $h) {
                $a = isset($align[$c]) && str_ends_with(trim($align[$c] ?? ''), ':') ? ' style="text-align:right"' : '';
                $html .= "<th$a>" . md_inline($h) . '</th>';
            }
            $html .= "</tr></thead>\n<tbody>";
            while ($i < $n && preg_match('/^\s*\|.*\|\s*$/', $lines[$i])) {
                $row = $cells($lines[$i]);
                $html .= '<tr>';
                foreach ($head as $c => $h) {
                    $a = isset($align[$c]) && str_ends_with(trim($align[$c] ?? ''), ':') ? ' style="text-align:right"' : '';
                    $html .= "<td$a>" . md_inline($row[$c] ?? '') . '</td>';
                }
                $html .= "</tr>\n";
                $i++;
            }
            $html .= "</tbody></table></div>\n";
            continue;
        }

        // Unordered list (supports one nesting level via indentation).
        if (preg_match('/^(\s*)[-*+]\s+/', $line)) {
            $closeParagraph();
            [$listHtml, $i] = md_list($lines, $i, false);
            $html .= $listHtml;
            continue;
        }

        // Ordered list.
        if (preg_match('/^(\s*)\d+[.)]\s+/', $line)) {
            $closeParagraph();
            [$listHtml, $i] = md_list($lines, $i, true);
            $html .= $listHtml;
            continue;
        }

        // Blank line.
        if (trim($line) === '') {
            $closeParagraph();
            $i++;
            continue;
        }

        // Paragraph text.
        if (!$inParagraph) {
            $html .= '<p>';
            $inParagraph = true;
        } else {
            $html .= ' ';
        }
        $html .= md_inline(rtrim($line));
        $i++;
    }
    $closeParagraph();

    return ['html' => $html, 'headings' => $headings];
}

/** Render an already-stripped block list (used inside quotes/admonitions). */
function md_render_blocks(array $lines): string
{
    return md_render(implode("\n", $lines))['html'];
}

/** Parse a list starting at $i; returns [html, nextIndex]. */
function md_list(array $lines, int $i, bool $ordered): array
{
    $n = count($lines);
    $tag = $ordered ? 'ol' : 'ul';
    $items = [];       // each: ['text' => ..., 'children' => [...]]
    $pattern = $ordered ? '/^\s*\d+[.)]\s+(.*)$/' : '/^\s*[-*+]\s+(.*)$/';

    while ($i < $n) {
        $line = $lines[$i];
        if (preg_match($pattern, $line, $m)) {
            $indent = strlen($line) - strlen(ltrim($line));
            if ($indent === 0) {
                $items[] = ['text' => $m[1], 'children' => []];
            } elseif ($items !== []) {
                $items[count($items) - 1]['children'][] = $m[1];
            } else {
                break;
            }
            $i++;
            continue;
        }
        // Continuation lines and blank lines between items.
        if (trim($line) === '' || preg_match('/^\s{2,}\S/', $line)) {
            $i++;
            continue;
        }
        break;
    }

    $html = "<$tag>\n";
    foreach ($items as $item) {
        $html .= '<li>' . md_inline($item['text']);
        if ($item['children'] !== []) {
            $html .= "\n<" . ($ordered ? 'ol' : 'ul') . ">\n";
            foreach ($item['children'] as $c) {
                $html .= '<li>' . md_inline($c) . "</li>\n";
            }
            $html .= '</' . ($ordered ? 'ol' : 'ul') . ">\n";
        }
        $html .= "</li>\n";
    }
    $html .= "</$tag>\n";
    return [$html, $i];
}

/** Inline spans: escape, then links, images, code, bold, italic. */
function md_inline(string $text): string
{
    $escaped = htmlspecialchars($text, ENT_QUOTES);

    // Images: ![alt](src)
    $escaped = preg_replace_callback(
        '/!\[([^\]]*)\]\(([^)\s]+)\)/',
        fn($m) => '<img src="' . $m[2] . '" alt="' . $m[1] . '" loading="lazy">',
        $escaped
    );

    // Links: [text](href) — internal /docs/... links stay relative, others open in a new tab.
    $escaped = preg_replace_callback(
        '/\[([^\]]+)\]\(([^)\s]+)\)/',
        function ($m) {
            [$all, $label, $href] = $m;
            $ext = !str_starts_with($href, '/') && !str_starts_with($href, '#');
            $attrs = $ext ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $href . '"' . $attrs . '>' . $label . '</a>';
        },
        $escaped
    );

    // Inline code.
    $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);

    // Bold then italic.
    $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped);
    $escaped = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $escaped);

    return $escaped;
}

/* --------------------------------------------------------------------------
 * Syntax highlighting — small server-side tokenizer, no JS dependencies.
 * Each language is one combined regex; every match is escaped exactly once
 * and wrapped in a tok-* span. Non-matching text is escaped as-is.
 * ------------------------------------------------------------------------ */

function md_spans(string $code, string $pattern, array $groups): string
{
    // $groups: group name => token class, in priority order. Every alternative
    // in $pattern is a named group; the first participating group decides the class.
    $out = '';
    $pos = 0;
    while (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE, $pos)) {
        $whole = $m[0][0];
        $start = $m[0][1];
        $out .= htmlspecialchars(substr($code, $pos, $start - $pos));
        $class = 'tok-text';
        foreach ($groups as $name => $cls) {
            if (isset($m[$name]) && $m[$name][0] !== '') {
                $class = $cls;
                break;
            }
        }
        $out .= '<span class="' . $class . '">' . htmlspecialchars($whole) . '</span>';
        $pos = $start + strlen($whole);
    }
    return $out . htmlspecialchars(substr($code, $pos));
}

function md_highlight(string $code, string $lang): string
{
    // Line comments | quoted strings | named token, composed per language.
    $str = "['\"](?:\\\\.|[^'\\\\\n])*['\"]";

    switch ($lang) {
        case 'bash':
        case 'sh':
        case 'shell':
        case 'console':
            $pattern = "/(?<com>#[^\n]*)|(?<str>{$str})|(?<var>\\$\\{?[\\w]+\\}?)|(?<flag>\\s--?[\\w-]+)|(?<kw>\\b(?:docker|run|exec|it|pull|up|down|build|compose|curl|wget|psql|mariadb|kubectl|systemctl|apt-get|apt|cd|export|source|echo|cat|sudo|rm|cp|mv|mkdir|git|brew|make)\\b)/";
            $groups = ['com' => 'tok-com', 'str' => 'tok-str', 'var' => 'tok-var', 'flag' => 'tok-flag', 'kw' => 'tok-kw'];
            break;

        case 'yaml':
        case 'yml':
            $pattern = "/(?<com>#[^\n]*)|(?<str>{$str})|(?<key>^\\s*[\\w.\\/-]+(?=:))|(?<lit>\\b(?:true|false|null|yes|no|on|off)\\b)|(?<num>\\b\\d+(?:\\.\\d+)*\\b)/m";
            $groups = ['com' => 'tok-com', 'str' => 'tok-str', 'key' => 'tok-key', 'lit' => 'tok-num', 'num' => 'tok-num'];
            break;

        case 'toml':
        case 'ini':
        case 'conf':
        case 'env':
            $pattern = "/(?<com>[#;][^\n]*)|(?<str>{$str})|(?<sec>^\\s*\\[[^\\]\\n]+\\])|(?<key>^\\s*[\\w.-]+(?=\\s*[=:]))|(?<lit>\\b(?:true|false|null)\\b)|(?<num>\\b\\d+(?:\\.\\d+)*\\b)/m";
            $groups = ['com' => 'tok-com', 'str' => 'tok-str', 'sec' => 'tok-sec', 'key' => 'tok-key', 'lit' => 'tok-num', 'num' => 'tok-num'];
            break;

        case 'json':
            $pattern = "/(?<key>\"(?:\\\\.|[^\"\\\\])*\"(?=\\s*:))|(?<str>\"(?:\\\\.|[^\"\\\\])*\")|(?<num>-?\\d+(?:\\.\\d+)?)|(?<lit>\\b(?:true|false|null)\\b)/";
            $groups = ['key' => 'tok-key', 'str' => 'tok-str', 'num' => 'tok-num', 'lit' => 'tok-num'];
            break;

        case 'sql':
            $pattern = "/(?<com>--[^\n]*)|(?<str>{$str})|(?<kw>\\b(?:SELECT|FROM|WHERE|AND|OR|NOT|NULL|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|TABLE|INDEX|ALTER|DROP|JOIN|LEFT|RIGHT|INNER|OUTER|ON|GROUP|ORDER|BY|LIMIT|OFFSET|AS|WITH|UNION|DISTINCT|PRIMARY|KEY|REFERENCES|GRANT|REVOKE|DATABASE|USER|ROLE|SCHEMA|VIEW|SHOW|EXPLAIN|BEGIN|COMMIT|ROLLBACK)\\b)|(?<num>\\b\\d+(?:\\.\\d+)*\\b)/i";
            $groups = ['com' => 'tok-com', 'str' => 'tok-str', 'kw' => 'tok-kw', 'num' => 'tok-num'];
            break;

        case 'dockerfile':
            $pattern = "/(?<com>#[^\n]*)|(?<str>{$str})|(?<kw>^\\s*(?:FROM|AS|RUN|CMD|LABEL|EXPOSE|ENV|ADD|COPY|ENTRYPOINT|VOLUME|USER|WORKDIR|ARG|ONBUILD|STOPSIGNAL|HEALTHCHECK|SHELL)\\b)/m";
            $groups = ['com' => 'tok-com', 'str' => 'tok-str', 'kw' => 'tok-kw'];
            break;

        default:
            // generic: line comments, strings, numbers
            $pattern = "/(?<com>#[^\n]*|\\/\\/[^\\n]*)|(?<str>{$str})|(?<num>-?\\b\\d+(?:\\.\\d+)*\\b)/";
            $groups = ['com' => 'tok-com', 'str' => 'tok-str', 'num' => 'tok-num'];
            break;
    }

    return md_spans($code, $pattern, $groups);
}

/** Plain-text version of a page (for search index / llms endpoints). */
function md_plain(string $source): string
{
    $t = preg_replace('/^```.*$/m', '', $source);
    $t = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', (string) $t); // links/images -> label
    $t = preg_replace('/[*_]{1,2}([^*_]+)[*_]{1,2}/', '$1', (string) $t); // bold/italic markers
    $t = preg_replace('/[#>*`|]/', '', (string) $t);
    return trim(preg_replace('/\n{3,}/', "\n\n", (string) $t));
}
