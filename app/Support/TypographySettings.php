<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\SiteFontLibrary;
use Illuminate\Validation\Rule;

/** Optional typography overrides stored in the existing site_settings table. */
class TypographySettings
{
    public static function rules(array $fields = ['title', 'description']): array
    {
        $rules = [];
        $families = array_keys(app(SiteFontLibrary::class)->catalog()['families']);
        foreach ($fields as $field) {
            $rules[$field.'_font_family'] = ['sometimes', 'nullable', 'string', Rule::in($families)];
            $rules[$field.'_font_size'] = ['sometimes', 'nullable', 'numeric', 'between:1,200'];
        }
        return $rules;
    }

    public static function read(array $settings, string $key): array
    {
        $value = json_decode($settings[$key] ?? '{}', true);
        return is_array($value) ? $value : [];
    }

    public static function save(string $key, array $data, array $fields = ['title', 'description']): void
    {
        $values = array_intersect_key($data, self::rules($fields));
        if (!$values) return;
        $raw = SiteSetting::where('key', $key)->value('value');
        $current = self::read([$key => $raw], $key);
        $updated = array_filter(array_replace($current, $values), fn ($value) => $value !== null && $value !== '');
        if ($updated === $current) return;
        SiteSetting::updateOrCreate(['key' => $key], ['value' => json_encode($updated, JSON_THROW_ON_ERROR)]);
    }

    /** Empty overrides emit no CSS, preserving the existing cascade. */
    public static function css(array $values, string $field, array $catalog): string
    {
        $css = '';
        if (!empty($values[$field.'_font_family'])) {
            $css .= 'font-family:'.SiteFontLibrary::css($values[$field.'_font_family'], $catalog).';';
        }
        if (isset($values[$field.'_font_size']) && is_numeric($values[$field.'_font_size'])) {
            $css .= 'font-size:'.max(1, min(200, (float) $values[$field.'_font_size'])).'px;';
        }
        return $css;
    }
}
