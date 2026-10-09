<?php

use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim(config('app.url') ?: 'https://www.foodfoto.pl', '/');

$urls = [
    '/',
    '/o-mnie',
    '/kontakt',
    '/strona/klienci',
    '/polityka-prywatnosci',
    '/portfolio/zywnosc',
    '/portfolio/stylizacja',
    '/portfolio/opakowania',
    '/portfolio/czasopisma',
    '/strona/fotografia-zywnosci',
    '/strona/fotografia-kulinarna',
    '/strona/food-styling',
    '/strona/fotografia-produktowa-zywnosci',
    '/strona/zdjecia-na-opakowania',
    '/strona/fotografia-reklamowa-zywnosci',
];

function textOf(?DOMNode $node): string {
    return trim(preg_replace('/\s+/u', ' ', $node?->textContent ?? ''));
}

function attr(?DOMElement $el, string $name): string {
    return $el?->hasAttribute($name) ? trim($el->getAttribute($name)) : '';
}

$rows = [];
$allImages = [];

foreach ($urls as $path) {
    $url = $base.$path;
    try {
        $response = Http::timeout(20)->withHeaders([
            'User-Agent' => 'FoodFoto-SEO-Audit/1.0',
            'Accept' => 'text/html,application/xhtml+xml',
        ])->get($url);
    } catch (Throwable $e) {
        $rows[] = ['url'=>$url,'status'=>'ERR','error'=>$e->getMessage()];
        continue;
    }

    $html = $response->body();
    $status = $response->status();

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    if (!$loaded) {
        $rows[] = ['url'=>$url,'status'=>$status,'error'=>'Nie udało się sparsować HTML'];
        continue;
    }

    $xpath = new DOMXPath($dom);

    $title = textOf($xpath->query('//title')->item(0));

    $description = '';
    foreach ($xpath->query('//meta[@name]') as $meta) {
        if (mb_strtolower(attr($meta, 'name')) === 'description') {
            $description = attr($meta, 'content');
            break;
        }
    }

    $canonical = '';
    foreach ($xpath->query('//link[@rel]') as $link) {
        if (str_contains(' '.mb_strtolower(attr($link, 'rel')).' ', ' canonical ')) {
            $canonical = attr($link, 'href');
            break;
        }
    }

    $robots = '';
    foreach ($xpath->query('//meta[@name]') as $meta) {
        if (mb_strtolower(attr($meta, 'name')) === 'robots') {
            $robots = attr($meta, 'content');
            break;
        }
    }

    $headings = ['h1'=>[], 'h2'=>[], 'h3'=>[]];
    foreach (array_keys($headings) as $tag) {
        foreach ($xpath->query('//'.$tag) as $node) {
            $headings[$tag][] = textOf($node);
        }
    }

    $images = [];
    foreach ($xpath->query('//img') as $img) {
        $src = attr($img, 'src');
        $altPresent = $img->hasAttribute('alt');
        $alt = $altPresent ? attr($img, 'alt') : null;
        $role = attr($img, 'role');
        $ariaHidden = attr($img, 'aria-hidden');
        $decorative = ($altPresent && $alt === '') || $role === 'presentation' || $ariaHidden === 'true';

        $item = [
            'page'=>$url,
            'src'=>$src,
            'alt_present'=>$altPresent,
            'alt'=>$alt,
            'decorative'=>$decorative,
        ];
        $images[] = $item;
        $allImages[] = $item;
    }

    $rows[] = [
        'url'=>$url,
        'status'=>$status,
        'title'=>$title,
        'description'=>$description,
        'canonical'=>$canonical,
        'robots'=>$robots,
        'h1'=>$headings['h1'],
        'h2'=>$headings['h2'],
        'h3'=>$headings['h3'],
        'images'=>$images,
    ];
}

echo PHP_EOL."=== PUBLICZNY AUDYT SEO: ".date('Y-m-d H:i:s')." ===".PHP_EOL.PHP_EOL;

foreach ($rows as $row) {
    echo $row['url'].PHP_EOL;
    echo "  HTTP: ".($row['status'] ?? '?').PHP_EOL;
    if (!empty($row['error'])) {
        echo "  BŁĄD: ".$row['error'].PHP_EOL.PHP_EOL;
        continue;
    }
    echo "  TITLE: ".($row['title'] ?: '[PUSTY]').PHP_EOL;
    echo "  META: ".($row['description'] ?: '[PUSTY]').PHP_EOL;
    echo "  CANONICAL: ".($row['canonical'] ?: '[PUSTY]').PHP_EOL;
    echo "  ROBOTS: ".($row['robots'] ?: '[BRAK]').PHP_EOL;
    echo "  H1: ".count($row['h1'])." — ".implode(' | ', $row['h1']).PHP_EOL;
    echo "  H2: ".count($row['h2'])." — ".implode(' | ', $row['h2']).PHP_EOL;
    echo "  H3: ".count($row['h3']).PHP_EOL;

    $missing = array_values(array_filter($row['images'], fn($i) => !$i['alt_present']));
    $empty = array_values(array_filter($row['images'], fn($i) => $i['alt_present'] && $i['alt'] === ''));
    echo "  IMG: ".count($row['images'])."; bez ALT: ".count($missing)."; pusty ALT: ".count($empty).PHP_EOL;
    foreach ($missing as $img) echo "    BEZ ALT: ".$img['src'].PHP_EOL;
    foreach ($empty as $img) echo "    PUSTY ALT: ".$img['src'].PHP_EOL;
    echo PHP_EOL;
}

$usable = array_values(array_filter($rows, fn($r) => empty($r['error'])));

foreach ([
    'title' => 'POWTARZAJĄCE SIĘ TITLE',
    'description' => 'POWTARZAJĄCE SIĘ META DESCRIPTION',
] as $field => $label) {
    echo "=== {$label} ===".PHP_EOL;
    $groups = [];
    foreach ($usable as $row) {
        $value = trim((string)($row[$field] ?? ''));
        if ($value === '') continue;
        $key = mb_strtolower($value);
        $groups[$key]['value'] = $value;
        $groups[$key]['urls'][] = $row['url'];
    }
    $dupes = array_filter($groups, fn($g) => count($g['urls']) > 1);
    if (!$dupes) {
        echo "BRAK DUPLIKATÓW".PHP_EOL;
    } else {
        foreach ($dupes as $group) {
            echo $group['value'].PHP_EOL;
            foreach ($group['urls'] as $url) echo "  - {$url}".PHP_EOL;
        }
    }
    echo PHP_EOL;
}

echo "=== OBRAZY WYMAGAJĄCE UWAGI ===".PHP_EOL;
$problems = array_values(array_filter($allImages, fn($i) => !$i['alt_present'] || ($i['alt_present'] && $i['alt'] === '')));
if (!$problems) {
    echo "BRAK OBRAZÓW BEZ ALT / Z PUSTYM ALT".PHP_EOL;
} else {
    foreach ($problems as $img) {
        $kind = !$img['alt_present'] ? 'BEZ ALT' : 'PUSTY ALT';
        echo "{$kind} | {$img['page']} | {$img['src']}".PHP_EOL;
    }
}

echo PHP_EOL."=== PODSUMOWANIE ===".PHP_EOL;
echo "Przebadane URL: ".count($rows).PHP_EOL;
echo "Obrazy łącznie: ".count($allImages).PHP_EOL;
echo "Obrazy bez ALT/pusty ALT: ".count($problems).PHP_EOL;
