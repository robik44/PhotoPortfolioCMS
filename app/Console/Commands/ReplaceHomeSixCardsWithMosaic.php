<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Models\Photo;
use App\Services\SiteFontLibrary;
use App\Support\BuilderButton;
use App\Support\BuilderContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * One-time, conservative, reversible data migration. This reads the CURRENT
 * live PageBuilder and uses its real photo/link/caption values. It never
 * overwrites Page, Gallery, Photo, SEO meta, canonical URLs or redirects.
 */
class ReplaceHomeSixCardsWithMosaic extends Command
{
    protected $signature = 'foodfoto:replace-home-six-cards
        {--apply : Back up and replace the six original cards}
        {--undo-last : Restore the latest replacement, only if the page has not changed since}
        {--show-source : Print original candidate section IDs}';

    protected $description = 'Move the original 3x2 FoodFoto cards into one editable six-card SEO mosaic, reversibly.';

    private const TOPICS = [
        'kulinarna' => 'Fotografia kulinarna',
        'produktowa' => 'Fotografia produktowa',
        'reklamowa' => 'Fotografia reklamowa',
        'stylizacja' => 'Stylizacja żywności / food styling',
        'zywnosc' => 'Fotografia żywności',
        'opakowania' => 'Zdjęcia na opakowania',
    ];

    // Only used if the original description isn't a standalone builder element.
    // These are verbatim texts already visible on the existing public homepage.
    private const EXISTING_DESCRIPTIONS = [
        'kulinarna' => 'Fotografia jedzenia: zdjęcia potraw, deserów i napojów do menu i kampanii.',
        'produktowa' => 'Fotografia produktowa żywności, opakowań i materiałów reklamowych marek.',
        'reklamowa' => 'Kadry budujące wizerunek marki i wspierające sprzedaż produktów spożywczych.',
        'stylizacja' => 'Stylizacja potraw i jedzenia: przygotowanie produktu i aranżacja do zdjęć.',
        'zywnosc' => 'Zdjęcia żywności, potraw i produktów spożywczych do reklamy, publikacji i komunikacji marek.',
        'opakowania' => 'Fotografia na opakowania: zdjęcia potraw i produktów do etykiet, opakowań i projektów marki.',
    ];

    public function handle(): int
    {
        try {
            if ($this->option('undo-last')) return $this->undo();
            $model = $this->home();
            $before = $model->content;
            if (!is_array($before) || !is_array($before['sections'] ?? null)) {
                throw new RuntimeException('Niepoprawne dane buildera strony głównej.');
            }

            [$after, $report] = $this->prepare($before);
            $this->components->info('Plan podmiany: 6 oryginalnych kart → jedna mozaika FoodFoto.');
            $this->table(['Kategoria', 'Zdjęcie ID', 'Link do podstrony', 'Opis'], $report['cards']);
            $this->line('Wykryto źródło: '.$report['source']);
            $this->line('Usuwane stare bloki: '.implode(', ', $report['removed']));
            $this->line('Utworzony/uzupełniony element: '.$report['target']);
            $this->line('Wysokość rzędów nowej mozaiki: '.$report['row_height'].' px (dopasowana do miejsca).');
            $this->line('Zachowane pozostałe elementy: '.$report['preserved']);
            if ($this->option('show-source')) {
                $this->line('Oryginalne indeksy sekcji: '.implode(', ', $report['indexes']));
            }

            BuilderContent::validate($after);
            app(SiteFontLibrary::class)->validateContent($after);
            $this->components->info('Kontrola struktury, zdjęć, adresów URL i typografii: poprawna.');

            if (!$this->option('apply')) {
                $this->warn('To jest tylko analiza — strona publiczna nie została zmieniona.');
                $this->line('Aby wykonać zatwierdzoną podmianę: php artisan foodfoto:replace-home-six-cards --apply');
                return self::SUCCESS;
            }

            $beforeHash = $this->fingerprint($before);
            $afterHash = $this->fingerprint($after);
            $directory = storage_path('app/foodfoto-mosaic-backups');
            File::ensureDirectoryExists($directory);
            $backupFile = $directory.'/home-six-cards-'.date('Ymd-His').'-'.$model->id.'.json';
            $backupPayload = [
                'builder_id' => $model->id,
                'created_at' => now()->toIso8601String(),
                'before_hash' => $beforeHash,
                'after_hash' => $afterHash,
                'before' => $before,
            ];
            if (File::put($backupFile, json_encode($backupPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Nie zapisano kopii zapasowej — nic nie zostało zmienione.');
            }

            DB::transaction(function () use ($model, $after, $beforeHash) {
                $locked = PageBuilder::query()->whereKey($model->id)->lockForUpdate()->firstOrFail();
                if ($this->fingerprint($locked->content ?? []) !== $beforeHash) {
                    throw new RuntimeException('Inna edycja zmieniła stronę w tym czasie. Nie nadpisuję jej.');
                }
                $locked->content = $after;
                $locked->save();
            });
            $this->components->info('Podmiana zapisana. Zachowano oryginalne zdjęcia, linki, podpisy i pozostałe sekcje.');
            $this->line('Kopia: '.$backupFile);
            $this->line('Cofnięcie ostatniej operacji: php artisan foodfoto:replace-home-six-cards --undo-last');
            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->components->error('Podmiana przerwana: '.$error->getMessage());
            $this->warn('Jeśli kontrola nie powiodła się, dane strony nie zostały podmienione.');
            return self::FAILURE;
        }
    }

    private function prepare(array $content): array
    {
        $sections = array_values($content['sections']);
        $items = collect($sections);
        $headings = $items->filter(function ($item) {
            if (!in_array($item['type'] ?? '', ['heading', 'text'], true)) return false;
            return str_contains($this->norm($item['content'] ?? ''), 'piekne obrazy dla smacznych historii');
        });
        if ($headings->count() !== 1) {
            throw new RuntimeException('Nie udało się jednoznacznie odnaleźć starego nagłówka „Piękne obrazy dla smacznych historii”.');
        }
        $headingIndex = $headings->keys()->first();
        $heading = $sections[$headingIndex];
        $startY = (float) ($heading['position_y'] ?? 0);
        $nextY = $items
            ->filter(fn ($item) => in_array(($item['type'] ?? ''), ['heading','text'], true)
                && (float)($item['position_y'] ?? -1) > $startY + 25
                && (
                    str_contains($this->norm($item['content'] ?? ''), 'smak w kadrach')
                    || str_contains($this->norm($item['content'] ?? ''), 'galerie wybrane realizacje')
                    || str_contains($this->norm($item['content'] ?? ''), 'od pomyslu')
                    || str_contains($this->norm($item['content'] ?? ''), 'wiecej niz')
                )
            )
            ->min('position_y');
        $limitY = is_numeric($nextY) ? (float) $nextY : $startY + 125;
        if ($limitY <= $startY + 20) throw new RuntimeException('Zbyt mały obszar sześciu kart.');

        $existingMosaics = $items->filter(fn ($section) => ($section['type'] ?? null) === 'gallery_mosaic');
        if ($existingMosaics->count() > 1) {
            throw new RuntimeException('W builderze istnieje kilka mozaik; nie wybiorę jednej losowo.');
        }
        $existingIndex = $existingMosaics->keys()->first();
        $oldMosaic = $existingIndex !== null ? $sections[$existingIndex] : [];

        $inRegion = fn ($section) => (float)($section['position_y'] ?? -999) >= $startY - 2
            && (float)($section['position_y'] ?? -999) < $limitY;
        $galleryIndexes = $items->filter(fn ($s) => ($s['type'] ?? '') === 'gallery' && $inRegion($s))->keys()->all();
        $imageIndexes = $items->filter(fn ($s) => ($s['type'] ?? '') === 'image'
            && $inRegion($s)
            && (!empty($s['photo_id']) || !empty($s['photo_url'])))->keys()->all();

        $remove = [$headingIndex];
        $cards = [];
        $source = '';
        $sourceIndexes = [];

        // The six legacy covers may have been one gallery block rather than six
        // independent image blocks. Reuse the gallery's true original URLs.
        foreach ($galleryIndexes as $index) {
            $gallerySection = $sections[$index];
            $galleries = $this->gallerySource($gallerySection);
            if (count($galleries) === 6) {
                if ($source !== '') throw new RuntimeException('Znaleziono więcej niż jeden zestaw sześciu kart.');
                $source = 'galeria CMS';
                $sourceIndexes = [$index];
                foreach ($galleries as $gallery) {
                    $gallery->loadMissing('photos');
                    $cover = $gallery->photos->first(fn ($p) => (bool)($p->pivot?->is_cover))
                        ?? $gallery->photos->first();
                    if (!$cover) throw new RuntimeException('Galeria bez zdjęcia okładki: '.$gallery->title);
                    $cards[] = [
                        'gallery_id' => $gallery->id,
                        'photo_id' => $cover->id,
                        'title' => $gallery->title,
                        'link' => route('portfolio.gallery', $gallery),
                        '_x' => 0, '_y' => 0,
                        '_source_index' => $index,
                    ];
                }
            }
        }

        if ($source === '') {
            if (count($imageIndexes) !== 6) {
                throw new RuntimeException('W obszarze starej sekcji znaleziono '.count($imageIndexes)
                    .' zdjęć (potrzeba dokładnie 6). Nie usuwam niczego w ciemno.');
            }
            $source = 'sześć osobnych zdjęć';
            $sourceIndexes = $imageIndexes;
            $photos = Photo::all();
            foreach ($imageIndexes as $index) {
                $section = $sections[$index];
                $photo = $this->resolvePhoto($section, $photos);
                if (!$photo) throw new RuntimeException('Nie udało się powiązać zdjęcia źródłowego z biblioteką: '
                    .($section['id'] ?? '#'.$index));
                $cards[] = [
                    'gallery_id' => null,
                    'photo_id' => $photo->id,
                    'title' => '',
                    'link' => BuilderButton::href($section['image_link'] ?? null),
                    '_x' => (float)($section['position_x'] ?? 0),
                    '_y' => (float)($section['position_y'] ?? 0),
                    '_source_index' => $index,
                ];
            }
            usort($cards, function ($a, $b) {
                // Row-major 3x2 regardless of the JSON storage order.
                $dy = abs($a['_y'] - $b['_y']);
                return $dy <= 9 ? ($a['_x'] <=> $b['_x']) : ($a['_y'] <=> $b['_y']);
            });
        }

        if ($source === 'galeria CMS' && count($imageIndexes) >= 3) {
            throw new RuntimeException('W sekcji są jednocześnie galeria i osobne zdjęcia. Odmawiam niejednoznacznej podmiany.');
        }
        if (count($cards) !== 6) throw new RuntimeException('Nie znaleziono dokładnie sześciu kart.');

        $usedKeys = [];
        $captionsToRemove = [];
        $descriptionsToRemove = [];
        $out = [];
        foreach ($cards as $position => $card) {
            $knownKey = $this->topic($card['title']);
            $labelIndex = null;
            if ($source === 'sześć osobnych zdjęć') {
                $match = $this->findCaption($sections, $startY, $limitY,
                    $card['_x'], $card['_y'], array_merge($captionsToRemove, [$headingIndex]), $usedKeys);
                if ($match === null) {
                    throw new RuntimeException('Nie znaleziono jednoznacznego podpisu do zdjęcia '.($position + 1).'.');
                }
                [$labelIndex, $knownKey] = $match;
                $card['title'] = trim(strip_tags((string)$sections[$labelIndex]['content']));
                $captionsToRemove[] = $labelIndex;
            }
            if ($knownKey === null || isset($usedKeys[$knownKey])) {
                throw new RuntimeException('Nie można bezpiecznie rozpoznać sześciu różnych kategorii: '.$card['title']);
            }
            $usedKeys[$knownKey] = true;
            $description = $source === 'galeria CMS'
                ? $this->findExistingDescriptionByText($sections, $knownKey, $startY, $limitY,
                    array_merge($captionsToRemove, $descriptionsToRemove, [$headingIndex]))
                : $this->findDescription($sections, $knownKey, $startY, $limitY,
                    $card['_x'], $card['_y'], array_merge($captionsToRemove, $descriptionsToRemove, [$headingIndex]));
            if ($description !== null) {
                [$descriptionIndex, $text] = $description;
                $descriptionsToRemove[] = $descriptionIndex;
            } else {
                $text = self::EXISTING_DESCRIPTIONS[$knownKey];
            }
            if (empty($card['link']) || str_starts_with((string)$card['link'], '#')) {
                $card['link'] = $this->matchPublishedPage($knownKey);
            }
            $link = BuilderButton::href($card['link']);
            if (!$link || $link === '#' || str_starts_with($link, '#')) {
                throw new RuntimeException('Nie udało się zachować poprawnego linku dla: '.$card['title']);
            }
            // Existing links must point to this site's real internal pages.
            $host = parse_url($link, PHP_URL_HOST);
            if ($host && !in_array(mb_strtolower($host), ['foodfoto.pl', 'www.foodfoto.pl'], true)) {
                throw new RuntimeException('Link karty prowadzi poza foodfoto.pl: '.$link);
            }
            if ($host) $link = (string)(parse_url($link, PHP_URL_PATH) ?: '/')
                .(parse_url($link, PHP_URL_QUERY) ? '?'.parse_url($link, PHP_URL_QUERY) : '');

            $out[$knownKey] = [
                'gallery_id' => $card['gallery_id'],
                'photo_id' => $card['photo_id'],
                'title' => '',
                'category' => $card['title'],
                'description' => $text,
                'link' => $link,
                'crop_x' => 50, 'crop_y' => 50,
                'action_text' => '→',
            ];
        }
        if (count($out) !== 6) throw new RuntimeException('Brakuje kategorii lub wykryto duplikaty.');

        // Some older pages added the six captions manually on top of a
        // six-gallery block. Remove only exact, short, on-section duplicates.
        if ($source === 'galeria CMS') {
            foreach ($sections as $index => $section) {
                if (in_array($index, array_merge($captionsToRemove,$descriptionsToRemove,[$headingIndex]), true)) continue;
                if (!in_array($section['type'] ?? '', ['text','heading'], true)) continue;
                $y = (float)($section['position_y'] ?? -99);
                if ($y < $startY || $y >= $limitY) continue;
                $text = trim(strip_tags((string)($section['content'] ?? '')));
                $key = $this->topic($text);
                if ($key && isset($usedKeys[$key]) && mb_strlen($text) < 90
                    && !preg_match('/[.!?:]/u',$text)
                    && $this->norm($text) === $this->norm($out[$key]['category'])) {
                    $captionsToRemove[] = $index;
                }
            }
        }

        // Preserve visible order in the old 3x2 grid: first row, second row.
        // The mosaic layout will place cards 1 and 6 high and the four others
        // in the center, without modifying their destination URLs.
        $ordered = [];
        foreach ($cards as $card) {
            $key = $this->topic($card['title']);
            $ordered[] = $out[$key];
        }

        $introIndex = null;
        $introText = 'Fotografia kulinarna, fotografia żywności i stylizacja dla marek oraz publikacji.';
        foreach ($sections as $index => $section) {
            if (!in_array($section['type'] ?? '', ['text','heading'], true)) continue;
            if ((float)($section['position_y'] ?? 0) < $startY - 23
                || (float)($section['position_y'] ?? 0) > $startY + 10) continue;
            $value = trim(strip_tags((string)($section['content'] ?? '')));
            if (str_contains($this->norm($value), 'specjalizuje sie w fotografii')
                && mb_strlen($value) <= 800) {
                $introIndex = $index;
                $introText = $value;
                break;
            }
        }

        $new = $oldMosaic + [
            'id' => 'foodfoto-gallery-mosaic-6',
            'type' => 'gallery_mosaic',
            'flow_mode' => 'free',
            'element_height' => 0,
            'element_width' => 90,
            'mosaic_layout' => 'editorial',
            'mosaic_gap' => 12,
            'mosaic_row_height' => 260,
            'mosaic_side_width' => 26,
            'mosaic_title_size' => 44,
            'mosaic_descriptions' => true,
            'mosaic_more_label' => 'ZOBACZ WSZYSTKIE REALIZACJE',
            'mosaic_more_link' => '/#portfolio',
        ];
        $new['type'] = 'gallery_mosaic';
        $new['position_x'] = (float)($heading['position_x'] ?? 5);
        $new['position_y'] = $startY;
        $new['element_width'] = 90;
        $new['element_height'] = 0;
        $new['flow_mode'] = 'free';
        $new['mosaic_title'] = trim(strip_tags((string)($heading['content'] ?? '')));
        $new['mosaic_intro'] = $introText;
        $new['mosaic_kicker'] = 'WYBRANE REALIZACJE';
        $new['mosaic_cards'] = $ordered;
        $new['mosaic_selected_index'] = 0;
        // Keep the new block within the old section's reserved vertical
        // space. This prevents it from covering the following portfolio.
        if (is_numeric($nextY)) {
            $spacePx = ((float)$nextY - $startY) * 9;
            if ($spacePx < 510) {
                throw new RuntimeException('Następna sekcja zaczyna się zbyt blisko, aby bezpiecznie zamienić układ.');
            }
            $maxRow = (int)floor(($spacePx - 196) / 2);
            $new['mosaic_row_height'] = min(
                (int)($new['mosaic_row_height'] ?? 260),
                max(150, min(550, $maxRow))
            );
        }

        // Don't delete unrelated page sections. Replace only identified
        // originals; if an empty mosaic was already added, keep its typography.
        $remove = array_values(array_unique(array_merge(
            $remove, $sourceIndexes, $captionsToRemove, $descriptionsToRemove,
            $introIndex !== null ? [$introIndex] : [],
            $existingIndex !== null ? [$existingIndex] : []
        )));
        $removeIds = array_map(fn ($idx) => (string)($sections[$idx]['id'] ?? '#'.$idx), $remove);
        $remaining = array_values(array_filter(
            $sections,
            fn ($section, $index) => !in_array($index, $remove, true),
            ARRAY_FILTER_USE_BOTH
        ));

        $remaining[] = $new;
        $content['sections'] = $remaining;
        $report = [
            'source' => $source,
            'removed' => $removeIds,
            'indexes' => $remove,
            'target' => $new['id'],
            'row_height' => $new['mosaic_row_height'],
            'preserved' => count($remaining) - 1,
            'cards' => array_map(fn ($c) => [
                $c['category'], $c['photo_id'], $c['link'],
                Str::limit($c['description'], 78)
            ], $ordered),
        ];
        return [$content, $report];
    }

    private function gallerySource(array $element): array
    {
        $published = Gallery::query()->where('published', true);
        if (!empty($element['gallery_collection_id'])) {
            $published->where('gallery_collection_id', (int)$element['gallery_collection_id']);
        }
        return match ($element['gallery_mode'] ?? 'all') {
            'selected' => collect($element['gallery_ids'] ?? [])
                ->map(fn ($id) => (clone $published)->whereKey($id)->first())
                ->filter()->values()->all(),
            'single' => [],
            default => $published->orderBy('sort_order')->orderBy('title')->get()->all(),
        };
    }

    private function findCaption(array $sections, float $startY, float $limitY, float $imageX, float $imageY,
                                 array $excluded, array $usedKeys): ?array
    {
        $candidates = [];
        foreach ($sections as $index => $section) {
            if (in_array($index, $excluded, true)) continue;
            if (!in_array($section['type'] ?? '', ['heading','text'], true)) continue;
            $title = trim(strip_tags((string)($section['content'] ?? '')));
            $topic = $this->topic($title);
            if ($topic === null || isset($usedKeys[$topic]) || mb_strlen($title) > 100
                || preg_match('/[.!?:]/u', $title)) continue;
            $x = (float)($section['position_x'] ?? 0);
            $y = (float)($section['position_y'] ?? 0);
            if ($y < $startY || $y > $limitY || $y < $imageY - 3 || $y > $imageY + 55) continue;
            $dx = abs($x - $imageX);
            if ($dx > 13) continue;
            $score = $dx * 2 + abs(($imageY + 18) - $y);
            $candidates[] = [$index,$topic,$score];
        }
        usort($candidates, fn ($a,$b) => $a[2] <=> $b[2]);
        if (count($candidates) >= 2 && abs($candidates[0][2] - $candidates[1][2]) < 3) return null;
        return $candidates ? [$candidates[0][0],$candidates[0][1]] : null;
    }

    private function findDescription(array $sections, string $topic, float $startY, float $limitY, float $x, float $y, array $excluded): ?array
    {
        $choices = [];
        foreach ($sections as $index => $section) {
            if (in_array($index, $excluded, true) || ($section['type'] ?? '') !== 'text') continue;
            $text = trim(strip_tags((string)($section['content'] ?? '')));
            if (mb_strlen($text) < 32 || mb_strlen($text) > 300) continue;
            $sY = (float)($section['position_y'] ?? -99);
            $sX = (float)($section['position_x'] ?? -99);
            if ($sY < $startY || $sY > $limitY || $sY < $y || $sY > $y + 70) continue;
            if (abs($sX-$x) > 14) continue;
            $normalized = $this->norm($text);
            $seed = $this->norm(self::EXISTING_DESCRIPTIONS[$topic]);
            $compatible = $normalized === $seed
                || ($topic === 'opakowania' && str_contains($normalized, 'opakowan'))
                || ($topic === 'stylizacja' && str_contains($normalized, 'styliz'))
                || ($topic === 'kulinarna' && str_contains($normalized, 'jedzen'))
                || ($topic === 'produktowa' && str_contains($normalized, 'produkt'))
                || ($topic === 'reklamowa' && str_contains($normalized, 'mark'))
                || ($topic === 'zywnosc' && str_contains($normalized, 'zywnosc'));
            if (!$compatible) continue;
            $choices[] = [$index,$text,abs($sX-$x)*2+abs($sY-($y+23))];
        }
        usort($choices, fn ($a,$b) => $a[2] <=> $b[2]);
        return $choices ? [$choices[0][0],$choices[0][1]] : null;
    }

    private function findExistingDescriptionByText(array $sections, string $topic, float $startY,
                                                   float $limitY, array $excluded): ?array
    {
        $expected = $this->norm(self::EXISTING_DESCRIPTIONS[$topic]);
        $matches = [];
        foreach ($sections as $index => $section) {
            if (in_array($index, $excluded, true)
                || !in_array($section['type'] ?? '', ['text','section'], true)) continue;
            $y = (float)($section['position_y'] ?? -99);
            if ($y < $startY || $y > $limitY) continue;
            $text = trim(strip_tags((string)($section['content'] ?? '')));
            if ($this->norm($text) === $expected) $matches[] = [$index,$text];
        }
        return count($matches) === 1 ? $matches[0] : null;
    }

    private function resolvePhoto(array $section, $photos): ?Photo
    {
        if (is_numeric($section['photo_id'] ?? null)) {
            $photo = $photos->firstWhere('id', (int)$section['photo_id']);
            if ($photo) return $photo;
        }
        $raw = (string)($section['photo_url'] ?? '');
        $filename = basename(rawurldecode(parse_url($raw, PHP_URL_PATH) ?: ''));
        if ($filename === '') return null;
        $matching = $photos->filter(fn ($p) => in_array($filename, array_map(
            fn ($value) => $value ? basename((string)$value) : null,
            [$p->filename,$p->webp,$p->thumbnail]
        ), true));
        return $matching->count() === 1 ? $matching->first() : null;
    }

    private function matchPublishedPage(string $topic): string
    {
        $pages = Page::query()->where('published',true)->get()
            ->filter(fn ($page) => $this->topic($page->title) === $topic);
        if ($pages->count() !== 1) {
            throw new RuntimeException('Nie ma jednej opublikowanej podstrony dla: '.self::TOPICS[$topic]
                .' (znaleziono '.$pages->count().'). Nie zgaduję URL.');
        }
        return route('page.public', $pages->first());
    }

    private function topic(?string $label): ?string
    {
        $s = $this->norm($label ?? '');
        if ($s === '') return null;
        if (str_contains($s,'opakowan')) return 'opakowania';
        if (str_contains($s,'styliz') || str_contains($s,'food styling')) return 'stylizacja';
        if (str_contains($s,'produktow')) return 'produktowa';
        if (str_contains($s,'reklamow')) return 'reklamowa';
        if (str_contains($s,'kulinar')) return 'kulinarna';
        if (str_contains($s,'zywnosc')) return 'zywnosc';
        return null;
    }

    private function norm(string $value): string
    {
        return trim((string)preg_replace('/\s+/u',' ', (string)preg_replace('/[^a-z0-9]+/',' ',
            mb_strtolower(Str::ascii(strip_tags($value))))));
    }

    private function home(): PageBuilder
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type','home')->first();
        if (!$builder) throw new RuntimeException('Nie znaleziono buildera strony głównej.');
        return $builder;
    }

    private function fingerprint(array $value): string
    {
        return hash('sha256',json_encode($value,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function undo(): int
    {
        $files = glob(storage_path('app/foodfoto-mosaic-backups/home-six-cards-*.json')) ?: [];
        if (!$files) throw new RuntimeException('Nie odnaleziono kopii zapasowej podmiany.');
        rsort($files);
        $backup = json_decode(File::get($files[0]),true,512,JSON_THROW_ON_ERROR);
        if (!is_array($backup['before'] ?? null)) throw new RuntimeException('Kopia zapasowa jest uszkodzona.');
        DB::transaction(function () use ($backup) {
            $builder = PageBuilder::query()->whereKey($backup['builder_id'])->lockForUpdate()->firstOrFail();
            if ($this->fingerprint($builder->content ?? []) !== $backup['after_hash']) {
                throw new RuntimeException('Od podmiany zapisano nowsze zmiany. Cofanie zatrzymano, aby ich nie utracić.');
            }
            $builder->content = $backup['before'];
            $builder->save();
        });
        $this->components->info('Przywrócono dokładną poprzednią wersję strony głównej.');
        return self::SUCCESS;
    }
}
