<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FoodfotoHomeSeo extends Command
{
    protected $signature = 'foodfoto:home-seo
        {--apply : Zapisz SEO strony głównej po utworzeniu kopii zapasowej}
        {--fix-overlap : Dodatkowo rozróżnij konkurencyjne tytuły stron O mnie, food-styling i galerii stylizacja}
        {--update-hero-copy : Dostosuj krótki opis hero, ale tylko jeśli nadal ma znaną, niezmienioną treść}';

    protected $description = 'Audyt produkcyjnej strony głównej: nagłówki, teksty, frazy, alty, meta i konkurencja podstron. Bez zmian domyślnie.';

    private const HOME_TITLE = 'Fotografia żywności i stylistka żywności | Magda Gugała';

    private const HOME_DESCRIPTION = 'Magda Gugała — stylistka żywności (food stylistka) i fotografka kulinarna. Food styling, stylizacja potraw i zdjęcia reklamowe jedzenia oraz produktów.';

    public function handle(): int
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')
            ->where('published', true)->first();
        if (!$builder) {
            $this->error('Brak opublikowanego buildera strony głównej. Nic nie zmieniono.');
            return self::FAILURE;
        }
        $sections = collect($builder->content['sections'] ?? []);
        $settings = Seo::settings();
        $meta = Seo::meta(null);

        $this->info('FOODFOTO: AUDYT SEO STRONY GŁÓWNEJ (odczyt z produkcyjnej bazy CMS)');
        $this->line('URL i canonical: '.$meta['canonical']);
        $this->line('Robots: '.$meta['robots']);
        $this->line('TITLE TERAZ ('.mb_strlen($meta['title']).'): '.$meta['title']);
        $this->line('DESCRIPTION TERAZ ('.mb_strlen($meta['description']).'): '.$meta['description']);
        $this->line('TITLE PROPONOWANY ('.mb_strlen(self::HOME_TITLE).'): '.self::HOME_TITLE);
        $this->line('DESCRIPTION PROPONOWANY ('.mb_strlen(self::HOME_DESCRIPTION).'): '.self::HOME_DESCRIPTION);

        $headingRows = $sections->map(function ($section) {
            $tag = $section['semantic_tag'] ?? (
                ($section['type'] ?? '') === 'heading' ? ($section['heading_level'] ?? 'div') : null
            );
            return [
                'Y' => round((float) ($section['position_y'] ?? 0), 1),
                'Typ' => $section['type'] ?? '-',
                'Tag' => $tag ?? '-',
                'Treść' => mb_substr(preg_replace('/\s+/u', ' ', trim((string) ($section['content'] ?? ''))), 0, 115),
            ];
        })->filter(fn ($row) => in_array($row['Tag'], ['h1', 'h2', 'h3'], true))
            ->sortBy('Y')->values();
        $this->newLine();
        $processHeadings = $sections->filter(fn ($section) => ($section['type'] ?? '') === 'process_steps')
            ->map(fn ($section) => [
                'Y' => round((float) ($section['position_y'] ?? 0), 1),
                'Typ' => 'process_steps',
                'Tag' => 'h2',
                'Treść' => mb_substr(trim((string) ($section['process_title'] ?? '')), 0, 115),
            ]);
        $headingRows = $headingRows->concat($processHeadings)->sortBy('Y')->values();
        $this->info('NAGŁÓWKI — '.count($headingRows).' z buildera');
        $this->table(['Y', 'Typ', 'Tag', 'Treść'], $headingRows->all());
        $h1 = $headingRows->where('Tag', 'h1')->count();
        if ($h1 !== 1) $this->warn('H1: '.$h1.'. Rekomendowany dokładnie jeden główny nagłówek opisujący usługę.');
        $firstH2 = $headingRows->first(fn ($row) => $row['Tag'] === 'h2');
        $firstH3 = $headingRows->first(fn ($row) => $row['Tag'] === 'h3');
        if ($firstH3 && (!$firstH2 || $firstH3['Y'] < $firstH2['Y'])) {
            $this->warn('Hierarchia H1 → H3 bez H2. Znajdź widoczny nagłówek sekcji usług i ustaw semantykę H2.');
        }
        $this->info('TEKSTY Z CMS: początek strony, sekcja usług i okolice nagłówka „Więcej niż zwykłe zdjęcia”');
        $bodyRows = $sections->filter(fn ($row) => in_array(($row['type'] ?? ''), ['text','heading'], true))
            ->sortBy(fn ($row) => (float) ($row['position_y'] ?? 0))
            ->filter(fn ($row) => (float) ($row['position_y'] ?? 0) < 345)
            ->map(fn ($row) => [
                'ID' => $row['id'] ?? '-',
                'Y' => round((float) ($row['position_y'] ?? 0), 1),
                'Tekst' => mb_substr(preg_replace('/\\s+/u', ' ', trim((string) ($row['content'] ?? ''))), 0, 160),
            ])->values();
        $this->table(['ID','Y','Tekst'], $bodyRows->take(28)->all());

        $texts = $sections->filter(fn ($row) => in_array(($row['type'] ?? ''), ['heading','text','section'], true))
            ->pluck('content')->filter()->implode("\n");
        foreach ($sections->filter(fn ($row) => ($row['type'] ?? '') === 'process_steps') as $process) {
            $texts .= "\n".($process['process_title'] ?? '').' '.($process['process_intro'] ?? '');
            foreach (($process['process_steps'] ?? []) as $step) {
                $texts .= ' '.($step['title'] ?? '').' '.($step['description'] ?? '');
            }
        }
        $normalized = mb_strtolower($texts);
        $keywords = [
            'stylistka żywności', 'food stylistka', 'foodstylistka', 'food stylist',
            'foodstylist', 'food styling', 'stylizacja żywności', 'stylizacja potraw',
            'stylizacja jedzenia', 'fotografia żywności', 'fotografia kulinarna',
            'fotografia reklamowa',
        ];
        $kwRows = [];
        foreach ($keywords as $phrase) {
            $kwRows[] = ['Fraza' => $phrase, 'W treści strony głównej' => mb_substr_count($normalized, $phrase)];
        }
        $this->info('FRAZY NA WIDOCZNEJ STRONIE GŁÓWNEJ (nie tylko w meta)');
        $this->table(['Fraza', 'W treści strony głównej'], $kwRows);

        $imageRows = [];
        foreach ($sections->filter(fn ($row) => ($row['type'] ?? '') === 'image') as $image) {
            $photo = isset($image['photo_id']) ? Photo::find($image['photo_id']) : null;
            $alt = trim((string) ($photo?->alt ?: $photo?->title ?: ($image['photo_title'] ?? '')));
            $imageRows[] = [
                'ID elementu' => $image['id'] ?? '',
                'Photo ID' => $photo?->id ?? '-',
                'ALT na stronie' => mb_substr($alt, 0, 100),
                'Stan' => $alt === '' ? 'BRAK ALT' : ($photo && !trim((string) $photo->alt) ? 'tylko fallback' : 'OK'),
            ];
        }
        // Images in the homepage thumbnail grids and portfolio cover cards
        // are visible to Google as well; audit those in addition to standalone photos.
        foreach ($sections->filter(fn ($row) => ($row['type'] ?? '') === 'thumbnail_gallery') as $row) {
            foreach (collect($row['photo_ids'] ?? [])->filter(fn ($id) => is_numeric($id))->unique() as $id) {
                $photo = Photo::find((int) $id);
                if (!$photo) continue;
                $alt = trim((string) ($photo->alt ?: $photo->title ?: ''));
                $imageRows[] = [
                    'ID elementu' => 'miniatury:'.($row['id'] ?? ''),
                    'Photo ID' => $photo->id,
                    'ALT na stronie' => mb_substr($alt, 0, 100),
                    'Stan' => $alt === '' ? 'BRAK ALT' : (!trim((string) $photo->alt) ? 'tylko fallback' : 'OK'),
                ];
            }
        }
        foreach ($sections->filter(fn ($row) => ($row['type'] ?? '') === 'gallery') as $row) {
            $query = Gallery::with('photos')->where('published', true);
            if (($row['gallery_mode'] ?? 'all') === 'single') {
                $query->whereKey($row['gallery_id'] ?? 0);
            } elseif (($row['gallery_mode'] ?? 'all') === 'selected') {
                $query->whereIn('id', $row['gallery_ids'] ?? []);
            }
            if (is_numeric($row['gallery_collection_id'] ?? null)) {
                $query->where('gallery_collection_id', (int) $row['gallery_collection_id']);
            }
            foreach ($query->get() as $gallery) {
                $cover = $gallery->photos->first(fn ($photo) => (bool) $photo->pivot->is_cover)
                    ?: $gallery->photos->first();
                if (!$cover) continue;
                $alt = trim((string) ($cover->alt ?: $gallery->title));
                $imageRows[] = [
                    'ID elementu' => 'okładka:'.$gallery->slug,
                    'Photo ID' => $cover->id,
                    'ALT na stronie' => mb_substr($alt, 0, 100),
                    'Stan' => $alt === '' ? 'BRAK ALT' : (!trim((string) $cover->alt) ? 'tylko fallback' : 'OK'),
                ];
            }
        }
        $this->newLine();
        $this->info('ZDJĘCIA NA STRONIE GŁÓWNEJ (pojedyncze, miniatury, okładki galerii) — '.count($imageRows));
        $this->table(['ID elementu', 'Photo ID', 'ALT na stronie', 'Stan'], array_slice($imageRows, 0, 55));
        if (count($imageRows) > 55) $this->line('Pokazano pierwsze 55 wierszy; łącznie: '.count($imageRows));
        $this->warn('Nie zmieniam ALT automatycznie. Opis musi wynikać z zawartości konkretnego zdjęcia, nie tylko z fraz SEO.');

        $this->newLine();
        $this->info('POTENCJALNIE KONKURENCYJNE PODSTRONY / GALERIE — ich tytuły SEO');
        $related = collect();
        foreach (Page::query()->where('published', true)->get() as $page) {
            if (preg_match('/food.?styl|stylist|stylizac|żywnoś|jedzeni/iu', ($page->seo_title ?? '').' '.($page->title ?? ''))) {
                $related->push(['Strona', $page->slug, Seo::meta($page)['title']]);
            }
        }
        foreach (Gallery::query()->where('published', true)->get() as $gallery) {
            if (preg_match('/food.?styl|stylist|stylizac|żywnoś|jedzeni/iu', ($gallery->seo_title ?? '').' '.($gallery->title ?? ''))) {
                $related->push(['Galeria', $gallery->slug, Seo::meta($gallery)['title']]);
            }
        }
        $this->table(['Typ', 'Slug', 'TITLE'], $related->take(30)->all());
        $this->line('Podstrony zachowują własne adresy i canonical. Nie ustawiam noindex ani przekierowań do strony głównej.');

        if (!$this->option('apply')) {
            $this->newLine();
            $this->comment('TYLKO ODCZYT. Nic nie zostało zmienione.');
            $this->comment('Aby zapisać wyłącznie nowe meta strony głównej: php artisan foodfoto:home-seo --apply');
            return self::SUCCESS;
        }

        $updates = [
            'home_seo_title' => self::HOME_TITLE,
            'home_seo_description' => self::HOME_DESCRIPTION,
        ];
        $homeContent = $builder->content;
        $newHeroCopy = null;
        if ($this->option('update-hero-copy')) {
            $oldHero = 'Fotografia kulinarna, fotografia reklamowa i fotografia produktowa dla marek, restauracji i wydawnictw.';
            $copy = 'Jako stylistka żywności (food stylistka) łączę food styling z fotografią kulinarną i reklamową dla marek.';
            foreach ($homeContent['sections'] as &$section) {
                if (($section['id'] ?? '') !== 'hero-text') continue;
                $current = trim(preg_replace('/\\s+/u', ' ', (string) ($section['content'] ?? '')));
                if (mb_strtolower($current) !== mb_strtolower($oldHero)) {
                    $this->warn('Tekst hero jest inny niż audytowany. Nie zmieniam go automatycznie.');
                    break;
                }
                $section['content'] = $copy;
                $newHeroCopy = $copy;
                break;
            }
            unset($section);
            if ($newHeroCopy === null) $this->warn('Tekst hero pozostaje bez zmian. Uzupełnimy go ręcznie po analizie treści z CMS.');
        }
        // Only change exact, previously audited SEO titles. Never overwrite
        // a title the editor has since customized.
        $overlap = collect();
        if ($this->option('fix-overlap')) {
            $targets = [
                'pages' => [
                    'food-styling' => [
                        'old' => 'Foodstylista – food styling i stylizacja żywności | Magda Gugała',
                        'new' => 'Food styling – przygotowanie potraw do sesji | Magda Gugała',
                        'description' => 'Przygotowanie i aranżacja potraw do fotografii kulinarnej, zdjęć reklamowych i produktowych. Poznaj proces stylizacji planu zdjęciowego.',
                    ],
                    'o-mnie' => [
                        'old' => 'Fotograf żywności i foodstylista | O mnie | Magda Gugała',
                        'new' => 'O mnie – doświadczenie fotograficzne | Magda Gugała',
                        'description' => 'Poznaj Magdę Gugałę i jej doświadczenie w fotografii żywności, aranżacji potraw i realizowaniu zdjęć reklamowych dla marek.',
                    ],
                ],
                'galleries' => [
                    'stylizacja' => [
                        'old' => 'Food stylistka i food styling – stylizacja żywności | Magda Gugała',
                        'new' => 'Stylizacja potraw – portfolio realizacji | Magda Gugała',
                        'description' => 'Galeria wykonanych stylizacji potraw, kompozycji i aranżacji dań do zdjęć. Zobacz przykłady realizacji fotografii jedzenia.',
                    ],
                ],
            ];
            foreach ($targets as $model => $definitions) {
                foreach ($definitions as $slug => $item) {
                    $entity = ($model === 'pages' ? Page::query() : Gallery::query())
                        ->where('slug', $slug)->first();
                    if ($entity && $entity->seo_title === $item['old']) {
                        $overlap->push(['entity' => $entity, 'target' => $item, 'type' => $model]);
                    } else {
                        $this->warn('Bez zmian: '.$model.'/'.$slug.' — tytuł w bazie różni się od audytowanego.');
                    }
                }
            }
        }

        $dir = storage_path('app/backups');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->error('Nie udało się utworzyć katalogu kopii zapasowej. Brak zmian.');
            return self::FAILURE;
        }
        $backupPath = $dir.'/home-seo-before-'.date('Ymd-His').'.json';
        $backupData = [
            'site_settings' => $settings,
            'overlap_records' => $overlap->map(fn ($row) => [
                'type' => $row['type'],
                'old' => $row['entity']->only(['id','slug','seo_title','seo_description']),
                'new' => $row['target'],
            ])->all(),
            'home_builder_id' => $builder->id,
            'home_builder_content' => $builder->content,
        ];
        if (!file_put_contents($backupPath, json_encode($backupData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
            $this->error('Nie udało się zapisać kopii zapasowej. Brak zmian.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($updates, $overlap, $builder, $homeContent, $newHeroCopy) {
            foreach ($updates as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            foreach ($overlap as $row) {
                $entity = $row['entity'];
                $entity->seo_title = $row['target']['new'];
                $entity->seo_description = $row['target']['description'];
                $entity->save();
            }
            if ($newHeroCopy !== null) {
                $builder->content = $homeContent;
                $builder->save();
            }
        });
        $this->info('ZAPISANO nowe TITLE i DESCRIPTION strony głównej. Zdjęcia, ALT i pozycje elementów bez zmian.');
        if ($newHeroCopy !== null) $this->info('Zmieniono tylko krótkie zdanie hero, zachowując położenie i typografię.');
        if ($overlap->isNotEmpty()) $this->info('Rozróżniono '.count($overlap).' tytuły SEO podstron/galerii. Nie zmieniono ich indeksowania ani adresów.');
        $this->line('Kopia zapasowa: '.$backupPath);
        return self::SUCCESS;
    }
}
