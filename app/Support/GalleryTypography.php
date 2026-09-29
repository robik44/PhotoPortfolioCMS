<?php

namespace App\Support;

use App\Services\SiteFontLibrary;
use Illuminate\Validation\Rule;

class GalleryTypography
{
    public const FIELDS = ['title_font_family', 'description_font_family', 'caption_font_family', 'title_font_size', 'description_font_size', 'caption_font_size'];

    public static function key(int $id): string
    {
        return 'gallery_' . $id . '_typography';
    }

    public static function read(array $settings, int $id): array
    {
        $raw = $settings[self::key($id)] ?? null;
        $fonts = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($fonts) ? array_intersect_key($fonts, array_flip(self::FIELDS)) : [];
    }

    public static function rules(): array
    {
        return TypographySettings::rules() + ['caption_font_family' => [
            'sometimes', 'nullable', 'string', Rule::in(array_keys(app(SiteFontLibrary::class)->catalog()['families'])),
        ], 'caption_font_size' => ['sometimes', 'nullable', 'numeric', 'between:1,200']];
    }

    public static function save(int $id, array $data): void
    {
        TypographySettings::save(self::key($id), array_intersect_key($data, array_flip(self::FIELDS)), ['title', 'description', 'caption']);
    }

    public static function css(array $fonts, string $field, array $catalog): string
    {
        $fallback = $catalog['defaults'][$field === 'title_font_family' ? 'site_heading_font_family' : 'site_body_font_family'];
        return SiteFontLibrary::css($fonts[$field] ?? null, $catalog, $fallback);
    }
}
