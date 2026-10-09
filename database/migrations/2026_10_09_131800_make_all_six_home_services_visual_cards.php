<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $home = DB::table('page_builders')
            ->whereNull('page_id')
            ->where('type', 'home')
            ->first();

        if (!$home) return;

        $data = json_decode($home->content ?: '{}', true);
        if (!is_array($data)) return;

        $sections = array_values($data['sections'] ?? []);
        $now = now();

        $byId = [];
        foreach ($sections as $i => $section) {
            if (!empty($section['id'])) $byId[$section['id']] = $i;
        }

        if (!isset($byId['services-heading'])) return;

        $servicesY = (float) ($sections[$byId['services-heading']]['position_y'] ?? 70);

        // Remove the two temporary text buttons. These services become full visual cards.
        $sections = array_values(array_filter($sections, fn ($section) =>
            !in_array($section['id'] ?? null, [
                'service-food-photo-link',
                'service-packaging-link',
                'service-food-photo-image',
                'service-food-photo-title',
                'service-food-photo-text',
                'service-packaging-image',
                'service-packaging-title',
                'service-packaging-text',
            ], true)
        ));

        // Find representative photos directly from the existing library.
        $galleryPhoto = static function (array $slugs, array $exclude = []) {
            $galleryIds = DB::table('galleries')->whereIn('slug', $slugs)->pluck('id');
            if ($galleryIds->isEmpty()) return null;

            $photoIds = DB::table('gallery_photo')
                ->whereIn('gallery_id', $galleryIds)
                ->orderBy('sort_order')
                ->pluck('photo_id')
                ->unique()
                ->values();

            return DB::table('photos')
                ->whereIn('id', $photoIds)
                ->when($exclude, fn ($q) => $q->whereNotIn('id', $exclude))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        };

        $used = collect($sections)
            ->filter(fn ($section) => str_starts_with((string) ($section['id'] ?? ''), 'service-'))
            ->pluck('photo_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $foodPhoto = $galleryPhoto(['zywnosc', 'stylizacja'], $used);
        if ($foodPhoto) $used[] = (int) $foodPhoto->id;
        $packPhoto = $galleryPhoto(['opakowania'], $used)
            ?? $galleryPhoto(['opakowania']);

        $photoUrl = static function (?object $photo): ?string {
            if (!$photo) return null;
            $file = $photo->webp ?: $photo->filename;
            if (!$file) return null;
            $file = ltrim($file, '/');
            if (!str_starts_with($file, 'photos/')) $file = 'photos/'.$file;
            return '/storage/'.$file;
        };

        $image = static function (string $id, ?object $photo, float $x, float $y, float $width, string $url) use ($photoUrl): array {
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
                'element_height' => 190,
                'z_index' => 20,
                'photo_id' => $photo?->id,
                'photo_url' => $photoUrl($photo),
                'photo_title' => $photo?->title ?: $photo?->alt ?: '',
                'image_link' => $url,
                'image_lightbox' => false,
                'image_width' => 100,
                'image_radius' => 0,
                'image_fit' => 'cover',
                'image_ratio' => '4 / 3',
                'image_height' => 190,
            ];
        };

        $heading = static function (string $id, string $label, float $x, float $y, float $width): array {
            return [
                'id' => $id,
                'type' => 'heading',
                'content' => $label,
                'heading_level' => 'h3',
                'semantic_tag' => 'h3',
                'style' => [
                    'color' => '#222222',
                    'font_size' => 19,
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

        $text = static function (string $id, string $copy, float $x, float $y, float $width): array {
            return [
                'id' => $id,
                'type' => 'text',
                'semantic_tag' => 'p',
                'content' => $copy,
                'style' => [
                    'color' => '#222222',
                    'font_size' => 14,
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

        // Re-layout all six services into two equal rows of three.
        $layout = [
            'kulinarna'   => [6.5,  $servicesY + 13],
            'produktowa'  => [36.75,$servicesY + 13],
            'reklamowa'   => [67.0, $servicesY + 13],
            'foodstyling' => [6.5,  $servicesY + 49],
        ];

        foreach ($layout as $key => [$x, $y]) {
            foreach (['image' => 0, 'title' => 23.5, 'text' => 29.0] as $part => $dy) {
                $id = 'service-'.$key.'-'.$part;
                foreach ($sections as &$section) {
                    if (($section['id'] ?? null) !== $id) continue;
                    $section['position_x'] = $x;
                    $section['position_y'] = $y + $dy;
                    $section['element_width'] = 26.5;
                    if ($part === 'image') {
                        $section['element_height'] = 190;
                        $section['image_height'] = 190;
                    }
                }
                unset($section);
            }
        }

        $sections[] = $image('service-food-photo-image', $foodPhoto, 36.75, $servicesY + 49, 26.5, '/strona/fotografia-zywnosci');
        $sections[] = $heading('service-food-photo-title', 'Fotografia żywności', 36.75, $servicesY + 72.5, 26.5);
        $sections[] = $text('service-food-photo-text', 'Zdjęcia żywności, potraw i produktów spożywczych do reklamy, publikacji i komunikacji marek.', 36.75, $servicesY + 78, 26.5);

        $sections[] = $image('service-packaging-image', $packPhoto, 67.0, $servicesY + 49, 26.5, '/strona/zdjecia-na-opakowania');
        $sections[] = $heading('service-packaging-title', 'Zdjęcia na opakowania', 67.0, $servicesY + 72.5, 26.5);
        $sections[] = $text('service-packaging-text', 'Fotografia potraw i produktów przygotowana z myślą o opakowaniach, etykietach i projektach marki.', 67.0, $servicesY + 78, 26.5);

        // Make room for the second row: move Portfolio and everything below it down.
        $portfolioY = null;
        foreach ($sections as $section) {
            if (($section['id'] ?? null) === 'portfolio-heading') {
                $portfolioY = (float) ($section['position_y'] ?? 0);
                break;
            }
        }

        if ($portfolioY !== null) {
            foreach ($sections as &$section) {
                if ((float) ($section['position_y'] ?? 0) >= $portfolioY) {
                    $section['position_y'] = (float) ($section['position_y'] ?? 0) + 38;
                }
            }
            unset($section);
        }

        usort($sections, fn ($a, $b) => ((float) ($a['position_y'] ?? 0)) <=> ((float) ($b['position_y'] ?? 0)));
        $data['sections'] = array_values($sections);

        DB::table('page_builders')
            ->where('id', $home->id)
            ->update([
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Visual refinement migration; no destructive rollback.
    }
};
