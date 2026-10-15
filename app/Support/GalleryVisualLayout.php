<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Validation\ValidationException;

/**
 * Opt-in visual canvas for gallery pages.
 *
 * Existing galleries keep their current Blade template until a layout is published.
 * The global preset is shared; gallery-level published changes are sparse overrides.
 * Photo metadata, SEO and gallery_photo pivot records are never stored here.
 */
final class GalleryVisualLayout
{
    public static function defaults(): array
    {
        return [
            'version' => 1,
            'canvas_height' => 900,
            'columns' => 4,
            'columns_tablet' => 2,
            'columns_mobile' => 1,
            'gap' => 22,
            'photo_height' => 250,
            'photo_fit' => 'contain',
            'photo_mode' => 'grid',
            'photo_order' => [],
            'individual_photos' => [],
            'blocks' => [
                'title' => ['x' => 6, 'y' => 56, 'w' => 88, 'font' => 'serif', 'size' => 48, 'tablet' => 40, 'mobile' => 32, 'weight' => 400, 'color' => '#222222', 'leading' => 1.15, 'spacing' => -1],
                'description' => ['x' => 6, 'y' => 145, 'w' => 88, 'font' => 'sans', 'size' => 17, 'tablet' => 16, 'mobile' => 15, 'weight' => 400, 'color' => '#666666', 'leading' => 1.6, 'spacing' => 0],
                'services' => ['x' => 6, 'y' => 242, 'w' => 85, 'font' => 'sans', 'size' => 13, 'tablet' => 13, 'mobile' => 12, 'weight' => 400, 'color' => '#333333', 'leading' => 1.6, 'spacing' => 0],
                'back' => ['x' => 6, 'y' => 283, 'w' => 48, 'font' => 'sans', 'size' => 13, 'tablet' => 13, 'mobile' => 12, 'weight' => 500, 'color' => '#333333', 'leading' => 1.5, 'spacing' => 1.5],
                'photos' => ['x' => 6, 'y' => 360, 'w' => 88],
            ],
            'extras' => [],
        ];
    }

    public static function key(string $scope, string $state, ?int $id = null): string
    {
        if ($scope === 'global') return 'gallery_visual_global_'.$state.'_v1';
        return 'gallery_visual_'.$id.'_'.$state.'_v1';
    }

    public static function read(array $settings, string $key): ?array
    {
        $raw = $settings[$key] ?? null;
        $value = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($value) && ($value['version'] ?? null) === 1 ? $value : null;
    }

    public static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) &&
                !array_is_list($value) && !array_is_list($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    public static function published(array $settings, int $galleryId): ?array
    {
        $global = self::read($settings, self::key('global', 'published'));
        $local = self::read($settings, self::key('gallery', 'published', $galleryId));

        if (!$global && !$local) return null;
        return self::merge(self::merge(self::defaults(), $global ?? []), $local ?? []);
    }

    public static function draft(array $settings, string $scope, int $galleryId): array
    {
        $global = self::read($settings, self::key('global', 'draft'))
            ?? self::read($settings, self::key('global', 'published'))
            ?? [];
        $global = self::merge(self::defaults(), $global);
        if ($scope === 'global') return $global;
        $local = self::read($settings, self::key('gallery', 'draft', $galleryId))
            ?? self::read($settings, self::key('gallery', 'published', $galleryId))
            ?? [];
        return self::merge($global, $local);
    }

    /** Compute sparse per-gallery changes so future global edits still propagate. */
    public static function diff(array $base, array $changed): array
    {
        $result = [];
        foreach ($changed as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $result[$key] = $value;
            } elseif (is_array($value) && is_array($base[$key])
                && !array_is_list($value) && !array_is_list($base[$key])) {
                $part = self::diff($base[$key], $value);
                if ($part) $result[$key] = $part;
            } elseif ($base[$key] !== $value) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public static function save(string $scope, string $state, int $galleryId, array $data): void
    {
        // Draft may contain only changed overrides. A sparse override needs version.
        $key = self::key($scope, $state, $galleryId);
        SiteSetting::updateOrCreate(['key' => $key], [
            'value' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    public static function remove(string $scope, string $state, int $galleryId): void
    {
        SiteSetting::where('key', self::key($scope, $state, $galleryId))->delete();
    }

    public static function validate(array $data): array
    {
        $invalid = fn (string $msg) => throw ValidationException::withMessages(['layout' => $msg]);
        if (($data['version'] ?? null) !== 1) $invalid('Niepoprawna wersja projektu galerii.');

        $limit = static function ($value, float $min, float $max) use ($invalid): float {
            if (!is_numeric($value) || (float) $value < $min || (float) $value > $max) $invalid('Położenie lub rozmiar poza dozwolonym zakresem.');
            return (float) $value;
        };
        foreach (['canvas_height' => [450, 30000], 'columns' => [1, 8], 'columns_tablet' => [1, 4],
                  'columns_mobile' => [1, 2], 'gap' => [0, 90], 'photo_height' => [80, 900]] as $k => [$min, $max]) {
            $data[$k] = $limit($data[$k] ?? null, $min, $max);
        }
        if (!in_array($data['photo_fit'] ?? null, ['cover', 'contain'], true)) $invalid('Niepoprawny sposób kadrowania zdjęć.');
        if (!in_array($data['photo_mode'] ?? null, ['grid', 'free'], true)) $invalid('Niepoprawny układ zdjęć.');

        $blocks = $data['blocks'] ?? null;
        if (!is_array($blocks) || array_diff(['title','description','services','back','photos'], array_keys($blocks))) {
            $invalid('Brakuje podstawowych elementów galerii.');
        }
        if (count($blocks) !== 5) $invalid('Niepoprawna liczba podstawowych elementów.');
        foreach ($blocks as $id => &$block) self::validateBlock($block, $limit, $invalid, $id !== 'photos');
        unset($block);
        $data['blocks'] = $blocks;

        $extras = $data['extras'] ?? [];
        if (!is_array($extras) || count($extras) > 30) $invalid('Maksymalnie 30 dodatkowych elementów.');
        $cleanExtras = [];
        foreach ($extras as $extra) {
            if (!is_array($extra) || !preg_match('/^extra-[a-z0-9-]{4,60}$/', (string) ($extra['id'] ?? ''))) {
                $invalid('Niepoprawny identyfikator dodatkowego elementu.');
            }
            if (isset($cleanExtras[$extra['id']])) $invalid('Powtarzający się element.');
            if (!in_array($extra['type'] ?? null, ['text','image','divider','button'], true)) {
                $invalid('Niepoprawny rodzaj dodatkowego elementu.');
            }
            self::validateBlock($extra, $limit, $invalid, true);
            $extra['text'] = mb_substr((string) ($extra['text'] ?? ''), 0, 2000);
            $extra['photo_id'] = isset($extra['photo_id']) && ctype_digit((string) $extra['photo_id']) ? (int) $extra['photo_id'] : null;
            $extra['url'] = mb_substr((string) ($extra['url'] ?? ''), 0, 500);
            if ($extra['url'] !== '' && !preg_match('~^(https?://|mailto:|tel:|/|#)~i', $extra['url'])) $invalid('Niepoprawny adres przycisku.');
            $cleanExtras[$extra['id']] = $extra;
        }
        $data['extras'] = array_values($cleanExtras);

        $photoOrder = $data['photo_order'] ?? [];
        if (!is_array($photoOrder) || count($photoOrder) > 1500) $invalid('Niepoprawna kolejność fotografii.');
        $data['photo_order'] = array_values(array_unique(array_map(static function ($id) use ($invalid) {
            if (!ctype_digit((string) $id) || (int) $id < 1) $invalid('Niepoprawny identyfikator zdjęcia.');
            return (int) $id;
        }, $photoOrder)));

        $individual = $data['individual_photos'] ?? [];
        if (!is_array($individual) || count($individual) > 1500) $invalid('Niepoprawny układ pojedynczych fotografii.');
        foreach ($individual as $id => &$position) {
            if (!ctype_digit((string) $id) || !is_array($position)) $invalid('Niepoprawna pozycja zdjęcia.');
            foreach (['x' => [0, 95], 'y' => [0, 30000], 'w' => [4, 100], 'h' => [60, 1200]] as $k => [$min, $max]) {
                $position[$k] = $limit($position[$k] ?? null, $min, $max);
            }
        }
        unset($position);
        $data['individual_photos'] = $individual;
        return $data;
    }

    private static function validateBlock(array &$block, callable $limit, callable $invalid, bool $withTypography): void
    {
        foreach (['x' => [0, 95], 'y' => [0, 30000], 'w' => [4, 100]] as $k => [$min, $max]) {
            $block[$k] = $limit($block[$k] ?? null, $min, $max);
        }
        if (!$withTypography) return;
        foreach (['size' => [8, 160], 'tablet' => [8, 160], 'mobile' => [8, 160],
                  'leading' => [.7, 4], 'spacing' => [-10, 25], 'weight' => [100, 900]] as $k => [$min, $max]) {
            $block[$k] = $limit($block[$k] ?? 16, $min, $max);
        }
        if (!in_array($block['font'] ?? '', ['serif','bodoni','sans','inter'], true)) $invalid('Niepoprawna czcionka.');
        if (!preg_match('/^#[0-9a-f]{6}$/i', (string) ($block['color'] ?? ''))) $invalid('Niepoprawny kolor tekstu.');
    }

    public static function font(string $choice): string
    {
        return match ($choice) {
            'serif' => 'Georgia, serif',
            'bodoni' => '"Bodoni Moda", Georgia, serif',
            'inter' => '"Inter", Arial, sans-serif',
            default => 'Arial, sans-serif',
        };
    }

    public static function orderedPhotos($photos, array $layout)
    {
        $order = array_flip($layout['photo_order'] ?? []);
        return $photos->sortBy(fn ($photo) => $order[$photo->id] ?? 100000 + (int) $photo->pivot->sort_order)->values();
    }
}
