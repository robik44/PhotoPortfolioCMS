<?php

namespace App\Support;

use App\Services\SiteFontLibrary;

class GalleryTypography
{
    /** Legacy public constant kept for compatibility with older code/tests. */
    public const FIELDS = [
        'title_font_family',
        'description_font_family',
        'caption_font_family',
        'title_font_size',
        'description_font_size',
        'caption_font_size',
    ];

    public static function key(int $id): string
    {
        return 'gallery_' . $id . '_typography';
    }

    public static function read(array $settings, int $id): array
    {
        $raw = $settings[self::key($id)] ?? null;
        $values = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($values)
            ? array_intersect_key($values, array_flip(TypographySettings::keys(['title', 'description', 'caption'])))
            : [];
    }

    public static function rules(): array
    {
        return TypographySettings::rules(['title', 'description', 'caption']);
    }

    public static function save(int $id, array $data): void
    {
        TypographySettings::save(self::key($id), $data, ['title', 'description', 'caption']);
    }

    public static function css(array $fonts, string $field, array $catalog): string
    {
        $fallback = $catalog['defaults'][$field === 'title_font_family'
            ? 'site_heading_font_family'
            : 'site_body_font_family'];

        return SiteFontLibrary::css($fonts[$field] ?? null, $catalog, $fallback);
    }
}
