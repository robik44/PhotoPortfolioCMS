<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\SiteFontLibrary;
use Illuminate\Validation\Rule;

class GalleryTypography
{
    public const FIELDS = ['title_font_family', 'description_font_family', 'caption_font_family'];

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
        return array_fill_keys(self::FIELDS, [
            'sometimes', 'required', 'string', Rule::in(array_keys(app(SiteFontLibrary::class)->catalog()['families'])),
        ]);
    }

    public static function save(int $id, array $data): void
    {
        $fonts = array_intersect_key($data, array_flip(self::FIELDS));
        if (!$fonts) {
            return;
        }
        $key = self::key($id);
        $current = self::read([$key => SiteSetting::where('key', $key)->value('value')], $id);
        SiteSetting::updateOrCreate(['key' => $key], ['value' => json_encode(array_replace($current, $fonts), JSON_THROW_ON_ERROR)]);
    }

    public static function css(array $fonts, string $field, array $catalog): string
    {
        $fallback = $catalog['defaults'][$field === 'title_font_family' ? 'site_heading_font_family' : 'site_body_font_family'];
        return SiteFontLibrary::css($fonts[$field] ?? null, $catalog, $fallback);
    }
}
