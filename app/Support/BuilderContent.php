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
                    $rules['image_lightbox'] = ['sometimes', 'boolean'];
                    $rules['image_link'] = ['sometimes', 'nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                        if ($value !== null && trim((string) $value) !== '' && BuilderButton::href($value) === null) {
                            $fail('Wybierz prawidłowy cel linku zdjęcia.');
                        }
                    }];
                }

                Validator::make($element, $rules)->validate();
            }
            if (($element['type'] ?? null) === 'process_steps') {
                $families = array_keys(app(\App\Services\SiteFontLibrary::class)->catalog()['families']);
                $rules = [
                    'process_kicker' => ['required', 'string', 'max:120'],
                    'process_title' => ['required', 'string', 'max:240'],
                    'process_intro' => ['required', 'string', 'max:1200'],
                    'process_steps' => ['required', 'array', 'size:3'],
                    'process_steps.*.title' => ['required', 'string', 'max:240'],
                    'process_steps.*.description' => ['required', 'string', 'max:1200'],
                    'process_steps.*.icon' => ['required', 'in:message,bowl,image,camera,lightbulb,check'],
                    'process_design' => ['required', 'array'],
                    'process_design.heading_font' => ['sometimes', 'string', \Illuminate\Validation\Rule::in($families)],
                    'process_design.body_font' => ['sometimes', 'string', \Illuminate\Validation\Rule::in($families)],
                    'process_design.show_arrows' => ['sometimes', 'boolean'],
                ];
                foreach (['background', 'text', 'muted', 'accent', 'icon_background', 'icon_color'] as $key) {
                    $rules['process_design.'.$key] = ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'];
                }
                foreach ([
                    'pad_x' => [0,160], 'pad_y' => [0,160],
                    'head_gap' => [0,140], 'section_gap' => [0,150],
                    'steps_gap' => [0,120], 'icon_size' => [36,150],
                    'title_size' => [20,100], 'title_weight' => [100,900],
                    'lead_size' => [10,36], 'step_title_size' => [12,40],
                    'step_description_size' => [10,30],
                ] as $key => [$min, $max]) {
                    $rules['process_design.'.$key] = ['sometimes', 'numeric', 'between:'.$min.','.$max];
                }
                Validator::make($element, $rules)->validate();

                if (isset($element['process_typography'])) {
                    Validator::make($element, [
                        'process_typography' => ['array'],
                        'process_typography.*' => ['array'],
                    ])->validate();
                    foreach (\App\Support\ProcessTypography::PARTS as $partKey => $_partType) {
                        $storedPart = $element['process_typography'][$partKey] ?? null;
                        if ($storedPart !== null && !is_array($storedPart)) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'process_typography.'.$partKey => 'Nieprawidłowe ustawienia czcionki.',
                            ]);
                        }
                    }
                }
                foreach (\App\Support\ProcessTypography::parts($element) as $textPart) {
                    \App\Support\BuilderTypography::validate($textPart, $index);
                }
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
                    'gallery_collection_id' => ['sometimes', 'nullable', 'integer', 'exists:gallery_collections,id'],
                    'gallery_mode' => ['sometimes', 'in:all,selected,single'],
                    'gallery_id' => ['required_if:gallery_mode,single', 'nullable', 'integer', 'exists:galleries,id'],
                    'gallery_ids' => ['sometimes', 'array', 'list', 'max:200'],
                    'gallery_ids.*' => ['integer', 'distinct', 'exists:galleries,id'],
                    'gallery_columns' => ['sometimes', 'integer', 'between:1,12'],
                    'gallery_gap' => ['sometimes', 'integer', 'between:0,100'],
                    'gallery_ratio' => ['sometimes', 'in:1 / .7,1 / 1,4 / 3,3 / 2,16 / 9'],
                    'gallery_align' => ['sometimes', 'in:left,center,right'],
                    'gallery_card_settings' => ['sometimes', 'array'],
                    'gallery_card_settings.*.width' => ['sometimes', 'numeric', 'between:5,100'],
                    'gallery_card_settings.*.x_offset' => ['sometimes', 'numeric', 'between:-2000,2000'],
                ])->validate();
            }
        }
    }
}
