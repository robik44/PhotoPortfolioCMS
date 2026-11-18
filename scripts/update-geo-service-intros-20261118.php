<?php
/**
 * FoodFoto GEO — podmien tylko trzy zaakceptowane akapity w PageBuilderze:
 * food-styling (pierwszy), fotografia-zywnosci (pierwszy),
 * zdjecia-na-opakowania (drugi).
 *
 * Ochrona: najpierw sprawdza wszystkie oryginalne tresci, tworzy kopie
 * pelnych JSON-ow, a dopiero potem zapisuje zmiany w jednej transakcji.
 * Nie modyfikuje sekcji, polożenia, stylow, SEO, obrazow ani naglowka.
 */
declare(strict_types=1);

$root = '/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function ffFail(string $message): never
{
    fwrite(STDERR, "STOP — NIC NIE ZMIENIONO: {$message}\n");
    exit(2);
}

$replacements = [
    'food-styling' => [
        'old' => 'Food styling, czyli stylizacja żywności, jest naturalnym uzupełnieniem fotografii kulinarnej. Przygotowuję potrawy i produkty spożywcze do sesji, dbając o kolor, strukturę, świeżość i sposób podania.',
        'new' => 'Specjalizuję się w stylizacji żywności, czyli przygotowywaniu potraw i produktów spożywczych do profesjonalnych sesji zdjęciowych. Jako food stylistka współpracuję z fotografami kulinarnymi, producentami żywności i agencjami reklamowymi. Dbam o kolor, strukturę, świeżość i sposób podania jedzenia, tak aby dobrze prezentowało się w kadrze.',
    ],
    'fotografia-zywnosci' => [
        'old' => 'Fotografia żywności to moja główna specjalizacja. Fotografuję produkty spożywcze, potrawy, gotowe dania i składniki do reklamy, na opakowania, do katalogów, publikacji i komunikacji internetowej.',
        'new' => 'Fotografia żywności od lat jest ważną częścią mojej pracy. Mam doświadczenie w samodzielnym fotografowaniu produktów spożywczych, potraw i gotowych dań. Obecnie najczęściej zajmuję się stylizacją żywności do zdjęć, współpracując z fotografami i studiami fotograficznymi przy realizacjach reklamowych, katalogowych i opakowaniowych. W zależności od projektu mogę również samodzielnie wykonać fotografie.',
    ],
    'zdjecia-na-opakowania' => [
        'old' => 'Realizuję fotografie produktów spożywczych i gotowych dań przeznaczone na opakowania i etykiety. Podczas sesji uwzględniam kompozycję projektu, miejsce na logo i typografię oraz późniejsze kadrowanie. W razie potrzeby przygotowuję także stylizację żywności.',
        'new' => 'Pracuję przy realizacji zdjęć produktów spożywczych i gotowych dań przeznaczonych na opakowania oraz etykiety. Przygotowuję i stylizuję żywność do fotografii, dbając o apetyczny wygląd produktu, jego naturalny kolor i odpowiednią kompozycję. Uwzględniam również wymagania projektu opakowania, miejsce na logo, typografię i późniejsze kadrowanie. W zależności od potrzeb współpracuję z fotografem lub samodzielnie realizuję zdjęcia.',
    ],
];

$pending = [];
$backupData = [];
$already = 0;

foreach ($replacements as $slug => $texts) {
    $pages = \App\Models\Page::query()->where('slug', $slug)->get();
    if ($pages->count() !== 1) ffFail("Nie znaleziono dokladnie jednej strony {$slug}.");

    $builders = \App\Models\PageBuilder::query()
        ->where('page_id', $pages[0]->id)
        ->where('type', 'page')
        ->get();
    if ($builders->count() !== 1) ffFail("Nie znaleziono dokladnie jednego buildera strony {$slug}.");

    $builder = $builders[0];
    $data = $builder->content;
    if (!is_array($data) || !isset($data['sections']) || !is_array($data['sections'])) {
        ffFail("Brak tablicy sections dla {$slug}.");
    }

    $oldIndexes = [];
    $newIndexes = [];
    foreach ($data['sections'] as $index => $section) {
        if (($section['type'] ?? '') !== 'text') continue;
        $content = trim((string) ($section['content'] ?? ''));
        if ($content === $texts['old']) $oldIndexes[] = $index;
        if ($content === $texts['new']) $newIndexes[] = $index;
    }

    if (count($oldIndexes) === 0 && count($newIndexes) === 1) {
        echo "JUZ POPRAWIONE: {$slug} — pozostawiono bez zmian.\n";
        $already++;
        continue;
    }

    if (count($oldIndexes) !== 1 || count($newIndexes) !== 0) {
        ffFail("Tresc strony {$slug} rozni sie od sprawdzonej wersji. Nie nadpisuje zmian uzytkownika.");
    }

    $backupData[$slug] = [
        'page_id' => $pages[0]->id,
        'builder_id' => $builder->id,
        'original_updated_at' => (string) $builder->updated_at,
        'original_content' => $data,
    ];

    $data['sections'][$oldIndexes[0]]['content'] = $texts['new'];
    $pending[] = ['slug' => $slug, 'builder' => $builder, 'content' => $data];
}

if (count($pending) === 0) {
    echo "GOTOWE: wszystkie trzy akapity sa juz zaktualizowane. Brak zmian.\n";
    exit(0);
}

$backupDir = $root.'/storage/app/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) ffFail('Nie mozna utworzyc katalogu kopii.');

$backup = $backupDir.'/geo-service-intros-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
$json = json_encode([
    'created_at' => date(DATE_ATOM),
    'purpose' => 'GEO: only three service introduction paragraphs',
    'builders' => $backupData,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
if (file_put_contents($backup, $json, LOCK_EX) !== strlen($json)) ffFail('Nie zapisano pelnej kopii JSON.');
@chmod($backup, 0600);

try {
    \Illuminate\Support\Facades\DB::transaction(static function () use ($pending, $replacements): void {
        foreach ($pending as $item) {
            // Recheck the exact paragraph under the DB lock to avoid overwriting
            // a later editor change between inspection and saving.
            $current = \App\Models\PageBuilder::query()->findOrFail($item['builder']->id);
            $data = $current->content;
            $old = $replacements[$item['slug']]['old'];
            $new = $replacements[$item['slug']]['new'];
            $found = [];
            foreach (($data['sections'] ?? []) as $index => $section) {
                if (($section['type'] ?? '') === 'text' && trim((string)($section['content'] ?? '')) === $old) {
                    $found[] = $index;
                }
            }
            if (count($found) !== 1) {
                throw new \RuntimeException("Tekst zmienil sie w czasie zapisu: {$item['slug']}");
            }
            $data['sections'][$found[0]]['content'] = $new;
            $current->content = $data;
            $current->save();
        }
    });
} catch (\Throwable $e) {
    fwrite(STDERR, "BLAD — transakcja cofnieta, dane stron bez zmian: ".$e->getMessage()."\n");
    fwrite(STDERR, "KOPIA: ".$backup."\n");
    exit(2);
}

foreach ($pending as $item) echo "ZMIENIONO: {$item['slug']} — tylko jeden akapit.\n";
echo "GOTOWE: ".count($pending)." podmiany; {$already} juz bylo poprawionych.\n";
echo "KOPIA JSON: {$backup}\n";
echo "BEZ ZMIAN: H1, H2, SEO, opisy meta, zdjecia, styl, pozycje elementow, menu, naglowek i kod strony.\n";
