<?php

namespace App\Support;

use App\Models\SiteSetting;

class GalleryBackLink
{
    public static function key(int $id): string { return 'gallery_'.$id.'_back_link'; }

    public static function rules(): array
    {
        return TypographySettings::rules(['back']) + [
            'back_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'back_color' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public static function read(array $settings, int $id): array
    {
        return TypographySettings::read($settings, self::key($id));
    }

    public static function save(int $id, array $data): void
    {
        $values = array_intersect_key($data, self::rules());
        if (!$values) return;
        $key = self::key($id);
        $current = self::read([$key => SiteSetting::where('key', $key)->value('value')], $id);
        $updated = array_filter(array_replace($current, $values), fn ($value) => $value !== null && $value !== '');
        if ($updated === $current) return;
        SiteSetting::updateOrCreate(['key' => $key], ['value' => json_encode($updated, JSON_THROW_ON_ERROR)]);
    }
}
