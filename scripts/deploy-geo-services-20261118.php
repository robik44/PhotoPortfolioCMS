<?php
/**
 * FoodFoto GEO — add Schema.org Service JSON-LD for exactly three
 * existing public service pages. No CMS or visual changes.
 *
 * Factual scope: services described by the visible content; provider
 * linked to the existing Organization @id. No prices, ratings or
 * unverified service regions.
 */
declare(strict_types=1);

$root = '/home/srv51861/domains/foodfoto.pl/laravel';
$file = $root.'/resources/views/components/seo-meta.blade.php';

function ffGeoStop(string $reason): never
{
    fwrite(STDERR, "STOP BEZ ZMIAN: ".$reason."\n");
    exit(2);
}

$before = @file_get_contents($file);
if (!is_string($before) || strlen($before) < 3000 || !is_writable($file)) {
    ffGeoStop('Nie mozna odczytac istniejacego szablonu SEO.');
}
$marker = 'FF_GEO_SERVICES_20261118';
if (str_contains($before, $marker)) {
    echo "JUZ ZAINSTALOWANE: Service JSON-LD. Bez zmian.\n";
    exit(0);
}
if (!str_contains($before, 'FF_GEO_PERSON_MAGDA_20261118')
    || !str_contains($before, "'@id' => url('/').'#organization'")
    || !str_contains($before, "'mainEntity' => (\$seo['schema_type'] ?? '') === 'AboutPage'")
    || !preg_match('/@endif\s*$/', $before)) {
    ffGeoStop('Szablon nie jest zgodny z ostatnia zweryfikowana wersja GEO.');
}

// Read-only validation of the public CMS content. Never touch the database.
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expected = [
    'food-styling' => 'Specjalizuję się w stylizacji żywności',
    'fotografia-zywnosci' => 'Fotografia żywności od lat jest ważną częścią mojej pracy',
    'zdjecia-na-opakowania' => 'Pracuję przy realizacji zdjęć produktów spożywczych',
];
foreach ($expected as $slug => $needle) {
    $page = \App\Models\Page::where('slug', $slug)->first();
    if (!$page || !$page->published) ffGeoStop("Strona {$slug} nie istnieje lub nie jest opublikowana.");
    $sections = $page->builder?->content['sections'] ?? [];
    $matches = 0;
    foreach ($sections as $section) {
        if (($section['type'] ?? '') === 'text'
            && str_contains((string)($section['content'] ?? ''), $needle)) $matches++;
    }
    if ($matches !== 1) ffGeoStop("Tekst CMS {$slug} nie odpowiada poprzedniej zatwierdzonej zmianie.");
}

// APPEND a new, independent block rather than altering the existing
// Organization, Person, SEO metadata or WebPage graph.
$addition = <<<'BLADE'

{{-- FF_GEO_SERVICES_20261118: Service schema only on three published service pages. --}}
@php
    $ffServicePagePath = rtrim((string) (parse_url($seo['canonical'] ?? '', PHP_URL_PATH) ?: ''), '/');
    $ffServiceDefinitions = [
        '/strona/food-styling' => [
            'name' => 'Food styling i stylizacja żywności',
            'serviceType' => 'Stylizacja żywności (food styling)',
            'description' => 'Przygotowywanie i stylizacja potraw oraz produktów spożywczych do profesjonalnych sesji zdjęciowych, we współpracy z fotografami i zespołami reklamowymi.',
        ],
        '/strona/fotografia-zywnosci' => [
            'name' => 'Fotografia żywności',
            'serviceType' => 'Fotografia żywności i potraw',
            'description' => 'Fotografia produktów spożywczych i potraw do zastosowań reklamowych, katalogowych i opakowaniowych, z możliwością przygotowania stylizacji żywności.',
        ],
        '/strona/zdjecia-na-opakowania' => [
            'name' => 'Zdjęcia żywności na opakowania',
            'serviceType' => 'Stylizacja żywności i fotografia na opakowania produktów spożywczych',
            'description' => 'Przygotowanie i stylizacja żywności do zdjęć produktów oraz gotowych dań na opakowania i etykiety, z uwzględnieniem kompozycji i projektu opakowania.',
        ],
    ];
    $ffServiceDefinition = $ffServiceDefinitions[$ffServicePagePath] ?? null;
@endphp
@if($ffServiceDefinition && ($seo['schema_type'] ?? '') === 'WebPage' && ($seo['robots'] ?? '') === 'index, follow')
@php
    $ffService = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        '@id' => $seo['canonical'].'#service',
        'name' => $ffServiceDefinition['name'],
        'serviceType' => $ffServiceDefinition['serviceType'],
        'description' => $ffServiceDefinition['description'],
        'url' => $seo['canonical'],
        'mainEntityOfPage' => $seo['canonical'],
        'provider' => ['@id' => url('/').'#organization'],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($ffService, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
BLADE;

$after = rtrim($before)."\n".$addition."\n";
if (substr_count($after, $marker) !== 1) ffGeoStop('Blad przygotowania zmian.');
$backupDir = $root.'/storage/app/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
    ffGeoStop('Nie mozna utworzyc katalogu kopii.');
}
$backup = $backupDir.'/seo-meta-before-geo-services-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.blade.php';
if (file_put_contents($backup, $before, LOCK_EX) !== strlen($before)) {
    ffGeoStop('Nie zapisano kompletnej kopii bezpieczenstwa.');
}
@chmod($backup, 0600);

$tmp = $file.'.geo-services-'.getmypid();
if (file_put_contents($tmp, $after, LOCK_EX) !== strlen($after)) {
    @unlink($tmp);
    ffGeoStop('Nie zapisano kompletnego nowego szablonu.');
}
@chmod($tmp, fileperms($file) & 0777);
if (!rename($tmp, $file)) {
    @unlink($tmp);
    ffGeoStop('Nie udalo sie podmienic szablonu.');
}

$output = [];
$status = 1;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1', $output, $status);
if ($status !== 0) {
    @copy($backup, $file);
    exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');
    ffGeoStop('Kompilacja Blade nieudana; poprzedni szablon przywrocono: '.implode(' | ', $output));
}
echo "SUKCES: Service JSON-LD dla food-styling, fotografia-zywnosci i zdjecia-na-opakowania.\n";
echo "POWIAZANIE: provider -> istniejąca Organization FoodFoto.\n";
echo "KOMPILACJA LARAVEL: OK.\n";
echo "BEZ ZMIAN: zawartosc CMS, H1/H2, SEO, style, zdjecia, naglowek, menu, skalowanie 1400px.\n";
echo "KOPIA: {$backup}\n";
echo "TEST: sprawdz HTML 3 stron pod katem \"@type\":\"Service\".\n";
