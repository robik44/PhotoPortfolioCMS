<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $home = DB::table('page_builders')
            ->whereNull('page_id')
            ->where('type', 'home')
            ->first();

        if (!$home) {
            return;
        }

        // One-click rollback safety: keep the exact previous homepage JSON.
        if (!DB::table('site_settings')->where('key', 'home_builder_backup_20261009_services')->exists()) {
            DB::table('site_settings')->insert([
                'key' => 'home_builder_backup_20261009_services',
                'value' => $home->content,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $data = json_decode($home->content ?: '{}', true);
        if (!is_array($data)) $data = [];
        $data['version'] ??= 1;
        $data['settings'] ??= [];
        $sections = array_values($data['sections'] ?? []);

        $photoUrl = static function (?object $photo): ?string {
            if (!$photo) return null;
            $file = $photo->webp ?: $photo->filename;
            if (!$file) return null;
            $file = ltrim($file, '/');
            if (!str_starts_with($file, 'photos/')) $file = 'photos/'.$file;
            return '/storage/'.$file;
        };

        $allPhotos = DB::table('photos')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $galleryPhotos = static function (array $slugs) use ($allPhotos) {
            $galleryIds = DB::table('galleries')->whereIn('slug', $slugs)->pluck('id');
            if ($galleryIds->isEmpty()) return collect();

            $ids = DB::table('gallery_photo')
                ->whereIn('gallery_id', $galleryIds)
                ->orderBy('sort_order')
                ->pluck('photo_id')
                ->unique()
                ->values();

            return $allPhotos->whereIn('id', $ids)->values();
        };

        $stylePhotos = $galleryPhotos(['stylizacja', 'zywnosc']);
        $packPhotos = $galleryPhotos(['opakowania']);
        $magPhotos = $galleryPhotos(['czasopisma']);

        $used = [];
        $pick = static function ($pool, $fallback) use (&$used) {
            foreach ($pool as $photo) {
                if (!in_array((int) $photo->id, $used, true)) {
                    $used[] = (int) $photo->id;
                    return $photo;
                }
            }
            foreach ($fallback as $photo) {
                if (!in_array((int) $photo->id, $used, true)) {
                    $used[] = (int) $photo->id;
                    return $photo;
                }
            }
            return $fallback->first();
        };

        $servicePhotos = [
            'kulinarna' => $pick($stylePhotos, $allPhotos),
            'produktowa' => $pick($packPhotos, $allPhotos),
            'reklamowa' => $pick($magPhotos->concat($stylePhotos)->values(), $allPhotos),
            'foodstyling' => $pick($stylePhotos, $allPhotos),
        ];

        $imageElement = static function (string $id, ?object $photo, float $x, float $y, float $width, float $height, ?string $link = null) use ($photoUrl): array {
            return [
                'id' => $id,
                'type' => 'image',
                'content' => '',
                'style' => [
                    'color' => '#222222',
                    'font_size' => 18,
                    'font_weight' => 400,
                    'text_align' => 'left',
                    'line_height' => 1.6,
                    'letter_spacing' => 0,
                    'font_family' => 'Arial',
                    'word_spacing' => 0,
                ],
                'position_x' => $x,
                'position_y' => $y,
                'element_width' => $width,
                'element_height' => $height,
                'z_index' => 20,
                'photo_id' => $photo?->id,
                'photo_url' => $photoUrl($photo),
                'photo_title' => $photo?->title ?: $photo?->alt ?: '',
                'image_link' => $link,
                'image_lightbox' => false,
                'image_width' => 100,
                'image_radius' => 0,
                'image_fit' => 'cover',
                'image_ratio' => '4 / 3',
                'image_height' => $height,
            ];
        };

        $heading = static function (string $id, string $text, float $x, float $y, float $width, float $size = 30, string $tag = 'h2'): array {
            return [
                'id' => $id,
                'type' => 'heading',
                'content' => $text,
                'heading_level' => $tag,
                'semantic_tag' => $tag,
                'style' => [
                    'color' => '#222222',
                    'font_size' => $size,
                    'font_weight' => 400,
                    'text_align' => 'left',
                    'line_height' => 1.15,
                    'letter_spacing' => 0,
                    'font_family' => 'Times New Roman',
                    'word_spacing' => 0,
                ],
                'position_x' => $x,
                'position_y' => $y,
                'element_width' => $width,
                'element_height' => 0,
                'z_index' => 100,
            ];
        };

        $text = static function (string $id, string $copy, float $x, float $y, float $width, float $size = 16): array {
            return [
                'id' => $id,
                'type' => 'text',
                'semantic_tag' => 'p',
                'content' => $copy,
                'style' => [
                    'color' => '#222222',
                    'font_size' => $size,
                    'font_weight' => 400,
                    'text_align' => 'left',
                    'line_height' => 1.5,
                    'letter_spacing' => 0,
                    'font_family' => 'Times New Roman',
                    'word_spacing' => 0,
                ],
                'position_x' => $x,
                'position_y' => $y,
                'element_width' => $width,
                'element_height' => 0,
                'z_index' => 100,
            ];
        };

        $button = static function (string $id, string $label, string $href, float $x, float $y, float $width): array {
            return [
                'id' => $id,
                'type' => 'button',
                'content' => $label,
                'style' => [
                    'color' => '#ffffff',
                    'font_size' => 13,
                    'font_weight' => 400,
                    'text_align' => 'center',
                    'line_height' => 1.2,
                    'letter_spacing' => .2,
                    'font_family' => 'Arial',
                    'word_spacing' => 0,
                ],
                'position_x' => $x,
                'position_y' => $y,
                'element_width' => $width,
                'element_height' => 42,
                'z_index' => 120,
                'button_link' => $href,
                'button_new_tab' => false,
                'button_background' => '#4f5f43',
                'button_background_opacity' => 100,
                'button_border_color' => '#4f5f43',
                'button_border_width' => 0,
                'button_radius' => 3,
                'button_padding_y' => 10,
                'button_padding_x' => 14,
            ];
        };

        // Strengthen the hero without changing its image/layout.
        foreach ($sections as &$section) {
            if (($section['id'] ?? null) === 'hero-heading') {
                $section['content'] = 'Fotografia żywności i stylizacja żywności';
                $section['semantic_tag'] = 'h1';
                $section['heading_level'] = 'h1';
            }
            if (($section['id'] ?? null) === 'hero-text') {
                $section['content'] = 'Fotografia kulinarna, fotografia reklamowa i fotografia produktowa dla marek, restauracji i wydawnictw.';
                $section['semantic_tag'] = 'p';
            }
        }
        unset($section);

        $portfolioIndex = null;
        foreach ($sections as $i => $section) {
            if (($section['id'] ?? null) === 'portfolio-heading' || (
                ($section['type'] ?? null) === 'heading'
                && mb_strtolower(trim((string) ($section['content'] ?? ''))) === 'portfolio'
            )) {
                $portfolioIndex = $i;
                break;
            }
        }

        $portfolioY = $portfolioIndex !== null
            ? (float) ($sections[$portfolioIndex]['position_y'] ?? 70)
            : 70.0;

        // Reserve vertical space for the new cooperation/services block while preserving all current homepage content below it.
        $shift = 58.0;
        foreach ($sections as &$section) {
            $y = (float) ($section['position_y'] ?? 0);
            if ($y >= $portfolioY) {
                $section['position_y'] = $y + $shift;
            }
        }
        unset($section);

        $base = max(52.0, $portfolioY);
        $new = [];

        $new[] = $heading('services-heading', 'Co tworzę', 6.5, $base, 45, 34, 'h2');
        $new[] = $text(
            'services-lead',
            'Specjalizuję się w fotografii jedzenia, food stylingu i tworzeniu dopracowanych kadrów dla branży spożywczej.',
            6.5, $base + 6.5, 87, 16
        );

        $cards = [
            [
                'key' => 'kulinarna',
                'x' => 6.5,
                'title' => 'Fotografia kulinarna',
                'copy' => 'Apetytne zdjęcia potraw, deserów i napojów do menu, kampanii i publikacji.',
                'url' => '/strona/fotografia-kulinarna',
            ],
            [
                'key' => 'produktowa',
                'x' => 29.0,
                'title' => 'Fotografia produktowa',
                'copy' => 'Profesjonalne zdjęcia produktów spożywczych, opakowań i materiałów marki.',
                'url' => '/strona/fotografia-produktowa-zywnosci',
            ],
            [
                'key' => 'reklamowa',
                'x' => 51.5,
                'title' => 'Fotografia reklamowa',
                'copy' => 'Kadry budujące wizerunek marki i wspierające sprzedaż produktów spożywczych.',
                'url' => '/strona/fotografia-reklamowa-zywnosci',
            ],
            [
                'key' => 'foodstyling',
                'x' => 74.0,
                'title' => 'Stylizacja żywności / food styling',
                'copy' => 'Przygotowanie produktu, kompozycja planu i aranżacja potraw do fotografii.',
                'url' => '/strona/food-styling',
            ],
        ];

        foreach ($cards as $card) {
            $new[] = $imageElement('service-'.$card['key'].'-image', $servicePhotos[$card['key']], $card['x'], $base + 13, 19.5, 180, $card['url']);
            $new[] = $heading('service-'.$card['key'].'-title', $card['title'], $card['x'], $base + 34.5, 19.5, 19, 'h3');
            $new[] = $text('service-'.$card['key'].'-text', $card['copy'], $card['x'], $base + 40, 19.5, 14);
        }

        $new[] = $button('service-food-photo-link', 'Fotografia żywności', '/strona/fotografia-zywnosci', 29, $base + 50.5, 18);
        $new[] = $button('service-packaging-link', 'Zdjęcia na opakowania', '/strona/zdjecia-na-opakowania', 52, $base + 50.5, 18);

        $sections = array_merge($sections, $new);
        usort($sections, fn ($a, $b) => ((float) ($a['position_y'] ?? 0)) <=> ((float) ($b['position_y'] ?? 0)));

        $data['sections'] = array_values($sections);
        DB::table('page_builders')->where('id', $home->id)->update([
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ]);

        // Create the six SEO landing pages with ready-to-edit builder content.
        $landingPages = [
            'fotografia-zywnosci' => [
                'title' => 'Fotografia żywności',
                'seo_title' => 'Fotografia żywności i zdjęcia jedzenia | Magda Gugała',
                'seo_description' => 'Profesjonalna fotografia żywności, potraw i produktów spożywczych. Zdjęcia reklamowe, produktowe, kulinarne i na opakowania wraz ze stylizacją.',
                'photo' => $servicePhotos['kulinarna'],
                'intro' => 'Fotografia żywności to moja główna specjalizacja. Fotografuję produkty spożywcze, potrawy, gotowe dania i składniki do reklamy, na opakowania, do katalogów, publikacji i komunikacji internetowej.',
                'h2' => 'Zdjęcia produktów spożywczych i potraw',
                'body' => 'Dobre zdjęcie jedzenia powinno wyglądać naturalnie, świeżo i apetycznie. Światło, kolor, faktura produktu, kompozycja i sposób podania budują jeden obraz. W zależności od projektu realizuję zarówno proste fotografie produktowe, jak i bardziej rozbudowane aranżacje. Fotografia i stylizacja żywności często łączą się w mojej pracy w jeden proces.',
            ],
            'fotografia-kulinarna' => [
                'title' => 'Fotografia kulinarna',
                'seo_title' => 'Fotografia kulinarna i zdjęcia potraw | Magda Gugała',
                'seo_description' => 'Fotografia kulinarna i profesjonalne zdjęcia potraw, dań oraz jedzenia do reklamy, publikacji, menu i materiałów promocyjnych.',
                'photo' => $servicePhotos['kulinarna'],
                'intro' => 'Fotografuję potrawy, dania, desery, napoje oraz produkty spożywcze, dbając o naturalność obrazu i charakter fotografowanej marki.',
                'h2' => 'Zdjęcia kulinarne do reklamy i publikacji',
                'body' => 'Realizuję sesje przeznaczone do kampanii reklamowych, materiałów promocyjnych, czasopism, katalogów, menu, stron internetowych i social mediów. W razie potrzeby zajmuję się również stylizacją jedzenia i aranżacją planu.',
            ],
            'food-styling' => [
                'title' => 'Food styling i stylizacja żywności',
                'seo_title' => 'Foodstylista – food styling i stylizacja żywności | Magda Gugała',
                'seo_description' => 'Food styling, stylizacja żywności i aranżacja potraw do fotografii reklamowej, sesji kulinarnych, zdjęć produktowych i na opakowania.',
                'photo' => $servicePhotos['foodstyling'],
                'intro' => 'Food styling, czyli stylizacja żywności, jest naturalnym uzupełnieniem fotografii kulinarnej. Przygotowuję potrawy i produkty spożywcze do sesji, dbając o kolor, strukturę, świeżość i sposób podania.',
                'h2' => 'Stylizacja jedzenia do zdjęć',
                'body' => 'Układam składniki, dobieram naczynia, dodatki, tła i rekwizyty. Stylizacja może być bardzo subtelna albo obejmować stworzenie całej aranżacji. Pracuję przy sesjach dla producentów żywności, marek, agencji reklamowych i wydawnictw.',
            ],
            'fotografia-produktowa-zywnosci' => [
                'title' => 'Fotografia produktowa żywności',
                'seo_title' => 'Fotografia produktowa żywności | Magda Gugała',
                'seo_description' => 'Fotografia produktowa żywności i profesjonalne zdjęcia produktów spożywczych do reklamy, katalogów, internetu, e-commerce oraz opakowań.',
                'photo' => $servicePhotos['produktowa'],
                'intro' => 'Fotografia produktowa żywności łączy precyzyjne pokazanie produktu z atrakcyjnym obrazem jego smaku, jakości i charakteru.',
                'h2' => 'Zdjęcia produktów spożywczych',
                'body' => 'Realizuję zdjęcia do katalogów, materiałów reklamowych, stron internetowych, e-commerce, prezentacji handlowych i opakowań. W zależności od projektu wykonuję zarówno czyste fotografie produktu, jak i stylizowane zdjęcia produktowe będące częścią większej kompozycji.',
            ],
            'zdjecia-na-opakowania' => [
                'title' => 'Zdjęcia żywności na opakowania',
                'seo_title' => 'Zdjęcia żywności na opakowania | Magda Gugała',
                'seo_description' => 'Profesjonalne zdjęcia potraw i produktów spożywczych na opakowania. Fotografia produktowa, stylizacja żywności i aranżacja kadru.',
                'photo' => $servicePhotos['produktowa'],
                'intro' => 'Zdjęcie na opakowaniu często jest pierwszym kontaktem klienta z produktem. Musi być atrakcyjne, czytelne i jednocześnie wiernie oddawać charakter żywności.',
                'h2' => 'Fotografia produktów na opakowania',
                'body' => 'Realizuję fotografie produktów spożywczych i gotowych dań przeznaczone na opakowania i etykiety. Podczas sesji uwzględniam kompozycję projektu, miejsce na logo i typografię oraz późniejsze kadrowanie. W razie potrzeby przygotowuję także stylizację żywności.',
            ],
            'fotografia-reklamowa-zywnosci' => [
                'title' => 'Fotografia reklamowa żywności',
                'seo_title' => 'Fotografia reklamowa żywności | Magda Gugała',
                'seo_description' => 'Fotografia reklamowa żywności, potraw i produktów spożywczych. Zdjęcia do kampanii, materiałów marketingowych, katalogów i komunikacji marek.',
                'photo' => $servicePhotos['reklamowa'],
                'intro' => 'Fotografia reklamowa żywności nie kończy się na pokazaniu produktu. Obraz powinien odpowiadać charakterowi marki, zwracać uwagę i działać w miejscu, do którego został zaprojektowany.',
                'h2' => 'Zdjęcia reklamowe produktów i potraw',
                'body' => 'Realizuję zdjęcia reklamowe produktów spożywczych, potraw i gotowych dań do kampanii, materiałów marketingowych, katalogów, prasy i internetu. Jeżeli projekt tego wymaga, przygotowuję również stylizację żywności i aranżację planu.',
            ],
        ];

        $pagePhoto = static function (?object $photo) use ($photoUrl): array {
            return [
                'id' => 'landing-image',
                'type' => 'image',
                'content' => '',
                'style' => ['color' => '#222222', 'font_size' => 18, 'font_weight' => 400, 'text_align' => 'left', 'line_height' => 1.6, 'letter_spacing' => 0],
                'position_x' => 7,
                'position_y' => 18,
                'element_width' => 42,
                'element_height' => 360,
                'z_index' => 10,
                'photo_id' => $photo?->id,
                'photo_url' => $photoUrl($photo),
                'photo_title' => $photo?->title ?: $photo?->alt ?: '',
                'image_link' => null,
                'image_lightbox' => true,
                'image_width' => 100,
                'image_radius' => 0,
                'image_fit' => 'cover',
                'image_ratio' => '4 / 3',
                'image_height' => 360,
            ];
        };

        foreach ($landingPages as $slug => $pageData) {
            $page = DB::table('pages')->where('slug', $slug)->first();

            if (!$page) {
                $pageId = DB::table('pages')->insertGetId([
                    'title' => $pageData['title'],
                    'slug' => $slug,
                    'content' => null,
                    'featured_image' => null,
                    'published' => true,
                    'sort_order' => 50,
                    'seo_title' => $pageData['seo_title'],
                    'seo_description' => $pageData['seo_description'],
                    'social_photo_id' => $pageData['photo']?->id,
                    'indexable' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $pageId = $page->id;
                DB::table('pages')->where('id', $pageId)->update([
                    'title' => $pageData['title'],
                    'seo_title' => $pageData['seo_title'],
                    'seo_description' => $pageData['seo_description'],
                    'social_photo_id' => $pageData['photo']?->id,
                    'indexable' => true,
                    'published' => true,
                    'updated_at' => $now,
                ]);
            }

            $pageSections = [
                $heading('landing-heading', $pageData['title'], 7, 5, 82, 46, 'h1'),
                $pagePhoto($pageData['photo']),
                $text('landing-intro', $pageData['intro'], 54, 20, 38, 18),
                $heading('landing-subheading', $pageData['h2'], 54, 45, 38, 28, 'h2'),
                $text('landing-body', $pageData['body'], 54, 52, 38, 17),
                $button('landing-contact', 'Skontaktuj się', '/kontakt', 54, 76, 16),
            ];

            DB::table('page_builders')->updateOrInsert(
                ['page_id' => $pageId, 'type' => 'page'],
                [
                    'content' => json_encode([
                        'version' => 1,
                        'settings' => [],
                        'sections' => $pageSections,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $backup = DB::table('site_settings')
            ->where('key', 'home_builder_backup_20261009_services')
            ->value('value');

        if ($backup) {
            DB::table('page_builders')
                ->whereNull('page_id')
                ->where('type', 'home')
                ->update([
                    'content' => $backup,
                    'updated_at' => now(),
                ]);
        }
    }
};
