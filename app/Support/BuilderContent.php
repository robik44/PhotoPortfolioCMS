<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;

class BuilderContent
{
    public static function validate(array $content): void
    {
        foreach ($content['sections'] ?? [] as $index => $element) {
            if (($element['type'] ?? null) === 'button') BuilderButton::validate($element);
            if (in_array($element['type'] ?? null, \App\Services\SiteFontLibrary::TEXT_BLOCKS, true)) {
                BuilderTypography::validate($element, $index);
            }
            if (in_array($element['type'] ?? null, ['image', 'gallery'], true)) {
                $rules = [
                    'caption' => ['sometimes', 'nullable', 'string'],
                    'caption_font_size' => ['sometimes', 'nullable', 'numeric', 'between:1,200'],
                ];

                if (($element['type'] ?? null) === 'image') {
                    $rules['image_fit'] = ['sometimes', 'in:cover,contain'];
                    $rules['image_ratio'] = ['sometimes', 'in:auto,1 / 1,4 / 3,3 / 2,16 / 9'];
                    $rules['image_radius'] = ['sometimes', 'numeric', 'between:0,200'];
                    $rules['image_height'] = ['sometimes', 'numeric', 'between:0,2000'];
                    $rules['image_link'] = ['sometimes', 'nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                        if ($value !== null && trim((string) $value) !== '' && BuilderButton::href($value) === null) {
                            $fail('Wybierz prawidłowy cel linku zdjęcia.');
                        }
                    }];
                }

                Validator::make($element, $rules)->validate();
            }
            if (($element['type'] ?? null) === 'thumbnail_gallery') {
                Validator::make($element, [
                    'photo_ids' => ['present', 'array', 'list', 'max:2000'],
                    // Deleted library records may remain in saved JSON; rendering skips them.
                    'photo_ids.*' => ['integer', 'min:1', 'distinct'],
                    'columns_desktop' => ['sometimes', 'integer', 'between:1,12'],
                    'columns_tablet' => ['sometimes', 'integer', 'between:1,12'],
                    'columns_mobile' => ['sometimes', 'integer', 'between:1,12'],
                    'gap' => ['sometimes', 'integer', 'between:0,100'],
                    'thumbnail_height' => ['sometimes', 'integer', 'between:0,1200'],
                    'thumbnail_radius' => ['sometimes', 'integer', 'between:0,200'],
                    'thumbnail_ratio' => ['sometimes', 'in:auto,1 / 1,4 / 3,3 / 2,16 / 9'],
                    'thumbnail_fit' => ['sometimes', 'in:cover,contain'],
                    'group_align' => ['sometimes', 'in:left,center,right'],
                    'photo_settings' => ['sometimes', 'array'],
                    'photo_settings.*.width' => ['sometimes', 'numeric', 'between:0,100'],
                    'photo_settings.*.height' => ['sometimes', 'numeric', 'between:0,1600'],
                    'photo_settings.*.x_offset' => ['sometimes', 'numeric', 'between:-2000,2000'],
                    'photo_settings.*.fit' => ['sometimes', 'in:cover,contain'],
                ])->validate();
            }
            if (in_array($element['type'] ?? null, ['heading', 'text'], true)) {
                Validator::make($element, [
                    'semantic_tag' => ['sometimes', 'in:div,p,h1,h2,h3,small'],
                ])->validate();
            }
            if (($element['type'] ?? null) === 'heading') {
                // Legacy field remains valid so existing saved layouts keep rendering unchanged.
                Validator::make($element, ['heading_level' => ['sometimes', 'in:div,h1,h2,h3']])->validate();
            }
            if (($element['type'] ?? null) === 'gallery') {
                Validator::make($element, [
                    'gallery_mode' => ['sometimes', 'in:all,selected,single'],
                    'gallery_id' => ['required_if:gallery_mode,single', 'nullable', 'integer', 'exists:galleries,id'],
                    'gallery_ids' => ['sometimes', 'array', 'list', 'max:200'],
                    'gallery_ids.*' => ['integer', 'distinct', 'exists:galleries,id'],
                    'gallery_columns' => ['sometimes', 'integer', 'between:1,12'],
                    'gallery_gap' => ['sometimes', 'integer', 'between:0,100'],
                    'gallery_ratio' => ['sometimes', 'in:1 / .7,1 / 1,4 / 3,3 / 2,16 / 9'],
                ])->validate();
            }
        }
    }
}
