<?php
declare(strict_types=1);

$manualPath = __DIR__ . '/USER_MANUAL.md';
if (!is_readable($manualPath)) {
    http_response_code(500);
    echo 'User manual file not found: USER_MANUAL.md';
    exit;
}

$markdown = (string)file_get_contents($manualPath);

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'section';
}

function renderInline(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
    return $text;
}

function renderMarkdown(string $markdown): string
{
    $lines = preg_split("/\r\n|\n|\r/", $markdown) ?: [];
    $html = '';
    $inUl = false;
    $inOl = false;
    $inCode = false;
    $toc = [];

    $closeLists = static function () use (&$html, &$inUl, &$inOl): void {
        if ($inUl) {
            $html .= "</ul>\n";
            $inUl = false;
        }
        if ($inOl) {
            $html .= "</ol>\n";
            $inOl = false;
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '```') {
            $closeLists();
            if ($inCode) {
                $html .= "</code></pre>\n";
            } else {
                $html .= "<pre><code>";
            }
            $inCode = !$inCode;
            continue;
        }

        if ($inCode) {
            $html .= htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            continue;
        }

        if ($trimmed === '') {
            $closeLists();
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $matches) === 1) {
            $closeLists();
            $level = strlen($matches[1]);
            $headingText = trim($matches[2]);
            $id = slugify($headingText);
            if ($level >= 2) {
                $toc[] = ['id' => $id, 'title' => $headingText, 'level' => $level];
            }
            $html .= "<h{$level} id=\"{$id}\">" . renderInline($headingText) . "</h{$level}>\n";
            continue;
        }

        if (preg_match('/^\s*-\s+(.*)$/', $line, $matches) === 1) {
            if ($inOl) {
                $html .= "</ol>\n";
                $inOl = false;
            }
            if (!$inUl) {
                $html .= "<ul>\n";
                $inUl = true;
            }
            $html .= '<li>' . renderInline(trim($matches[1])) . "</li>\n";
            continue;
        }

        if (preg_match('/^\s*\d+[.)]\s+(.*)$/', $line, $matches) === 1) {
            if ($inUl) {
                $html .= "</ul>\n";
                $inUl = false;
            }
            if (!$inOl) {
                $html .= "<ol>\n";
                $inOl = true;
            }
            $html .= '<li>' . renderInline(trim($matches[1])) . "</li>\n";
            continue;
        }

        $closeLists();
        $html .= '<p>' . renderInline($trimmed) . "</p>\n";
    }

    if ($inCode) {
        $html .= "</code></pre>\n";
    }
    if ($inUl) {
        $html .= "</ul>\n";
    }
    if ($inOl) {
        $html .= "</ol>\n";
    }

    $tocHtml = '';
    if (!empty($toc)) {
        $tocHtml .= "<nav class=\"toc\"><h2>Contents</h2>\n<ul>\n";
        foreach ($toc as $item) {
            $class = $item['level'] > 2 ? ' class="sub"' : '';
            $tocHtml .= "<li{$class}><a href=\"#{$item['id']}\">" .
                htmlspecialchars($item['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                "</a></li>\n";
        }
        $tocHtml .= "</ul></nav>\n";
    }

    return $tocHtml . "<article class=\"manual-content\">\n" . $html . "</article>\n";
}

$manualHtml = renderMarkdown($markdown);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIMS User Manual</title>
  <style>
    body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; background: #f6f8fb; color: #1b2430; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .hero { background: #17395c; color: #fff; padding: 20px; border-radius: 10px; margin-bottom: 16px; }
    .grid { display: grid; grid-template-columns: 300px 1fr; gap: 16px; align-items: start; }
    .toc, .manual-content { background: #fff; border: 1px solid #dde3ea; border-radius: 10px; }
    .toc { padding: 14px; position: sticky; top: 12px; max-height: 85vh; overflow: auto; }
    .toc h2 { margin: 0 0 10px; font-size: 18px; }
    .toc ul { margin: 0; padding-left: 18px; }
    .toc li { margin: 4px 0; }
    .toc li.sub { margin-left: 10px; }
    .toc a { color: #1f5ea8; text-decoration: none; }
    .toc a:hover { text-decoration: underline; }
    .manual-content { padding: 16px 18px; }
    .manual-content h1, .manual-content h2, .manual-content h3 { color: #17395c; margin-top: 18px; margin-bottom: 10px; }
    .manual-content h1 { margin-top: 0; }
    .manual-content p { margin: 8px 0; line-height: 1.45; }
    .manual-content ul, .manual-content ol { margin: 6px 0 10px 22px; }
    .manual-content li { margin: 4px 0; }
    code { background: #eef3f8; padding: 1px 5px; border-radius: 4px; }
    pre { background: #0f1720; color: #f2f6ff; padding: 12px; border-radius: 8px; overflow: auto; }
    @media (max-width: 900px) {
      .grid { grid-template-columns: 1fr; }
      .toc { position: static; max-height: none; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="hero">
      <h1 style="margin:0 0 6px;">LIMS User Manual</h1>
      <div>Operational user workflows generated from <code>USER_MANUAL.md</code>.</div>
    </div>
    <div class="grid">
      <?= $manualHtml ?>
    </div>
  </div>
</body>
</html>
