<?php

use App\Http\Controllers\SeoController;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Models\Photo;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ok = true;

$pass = function (string $label, bool $condition, string $detail = '') use (&$ok): void {
    echo ($condition ? "OK   " : "BŁĄD ") . $label;
    if ($detail !== '') echo " — ".$detail;
    echo PHP_EOL;
    if (!$condition) $ok = false;
};

$countTags = static function (array $sections): array {
    $counts = ['h1' => 0, 'h2' => 0, 'h3' => 0];
    foreach ($sections as $section) {
        if (($section['type'] ?? null) !== 'heading') continue;
        $tag = $section['semantic_tag'] ?? $section['heading_level'] ?? null;
        if (isset($counts[$tag])) $counts[$tag]++;
    }
    return $counts;
};

echo PHP_EOL."=== SEO AUDYT: STRONA GŁÓWNA ===".PHP_EOL;

$home = PageBuilder::whereNull('page_id')->where('type', 'home')->first();
$pass('Builder strony głównej istnieje', (bool) $home);

$homeSections = $home?->content['sections'] ?? [];
$homeTags = $countTags($homeSections);

$pass('Dokładnie jeden H1 na stronie głównej', $homeTags['h1'] === 1, "H1={$homeTags['h1']}");
$pass('Sekcja Co tworzę jest H2', collect($homeSections)->contains(fn ($s) =>
    ($s['id'] ?? null) === 'services-heading'
    && (($s['semantic_tag'] ?? $s['heading_level'] ?? null) === 'h2')
));
$pass('Portfolio jest H2', collect($homeSections)->contains(fn ($s) =>
    ($s['id'] ?? null) === 'portfolio-heading'
    && (($s['semantic_tag'] ?? $s['heading_level'] ?? null) === 'h2')
));

$serviceTitleIds = [
    'service-kulinarna-title',
    'service-produktowa-title',
    'service-reklamowa-title',
    'service-foodstyling-title',
    'service-food-photo-title',
    'service-packaging-title',
];

foreach ($serviceTitleIds as $id) {
    $section = collect($homeSections)->firstWhere('id', $id);
    $pass(
        "{$id} = H3",
        (bool) $section && (($section['semantic_tag'] ?? $section['heading_level'] ?? null) === 'h3')
    );
}

echo PHP_EOL."=== ALT-Y 6 ZDJĘĆ W SEKCJI CO TWORZĘ ===".PHP_EOL;

$serviceImageIds = [
    'service-kulinarna-image',
    'service-produktowa-image',
    'service-reklamowa-image',
    'service-foodstyling-image',
    'service-food-photo-image',
    'service-packaging-image',
];

foreach ($serviceImageIds as $id) {
    $section = collect($homeSections)->firstWhere('id', $id);
    $photo = !empty($section['photo_id']) ? Photo::find($section['photo_id']) : null;
    $alt = trim((string) ($photo?->alt ?: ($section['photo_title'] ?? '')));
    $pass("ALT {$id}", $alt !== '', $alt !== '' ? $alt : 'PUSTY');
}

echo PHP_EOL."=== 6 PODSTRON SEO ===".PHP_EOL;

$slugs = [
    'fotografia-zywnosci',
    'fotografia-kulinarna',
    'food-styling',
    'fotografia-produktowa-zywnosci',
    'zdjecia-na-opakowania',
    'fotografia-reklamowa-zywnosci',
];

$pages = Page::whereIn('slug', $slugs)->get()->keyBy('slug');

foreach ($slugs as $slug) {
    $page = $pages->get($slug);
    echo PHP_EOL."-- /strona/{$slug} --".PHP_EOL;

    $pass('Strona istnieje', (bool) $page);
    if (!$page) continue;

    $pass('Opublikowana', (bool) $page->published);
    $pass('Indexable', (bool) $page->indexable);
    $pass('SEO title', trim((string) $page->seo_title) !== '', (string) $page->seo_title);
    $pass('Meta description', trim((string) $page->seo_description) !== '', (string) $page->seo_description);

    $builder = PageBuilder::where('page_id', $page->id)->where('type', 'page')->first();
    $sections = $builder?->content['sections'] ?? [];
    $tags = $countTags($sections);

    $pass('Dokładnie jeden H1', $tags['h1'] === 1, "H1={$tags['h1']}");
    $pass('Jest co najmniej jeden H2', $tags['h2'] >= 1, "H2={$tags['h2']}");
}

echo PHP_EOL."=== SITEMAP.XML ===".PHP_EOL;

try {
    $xml = app(SeoController::class)->sitemap()->getContent();

    $pass('Strona główna w sitemap', str_contains($xml, '<loc>'.e(url('/')).'</loc>') || str_contains($xml, '<loc>'.url('/').'</loc>'));

    foreach ($slugs as $slug) {
        $page = $pages->get($slug);
        if (!$page) {
            $pass("{$slug} w sitemap", false, 'brak strony');
            continue;
        }

        $url = \App\Support\Seo::pageUrl($page);
        $pass("{$slug} w sitemap", str_contains($xml, $url), $url);
    }
} catch (Throwable $e) {
    $pass('Generowanie sitemap', false, $e->getMessage());
}

echo PHP_EOL."=== WYNIK ===".PHP_EOL;
echo $ok
    ? "SEO AUDYT: WSZYSTKIE KONTROLE OK".PHP_EOL
    : "SEO AUDYT: SĄ PUNKTY DO POPRAWY — WKLEJ TEN WYNIK DO CHATGPT".PHP_EOL;

exit($ok ? 0 : 1);
