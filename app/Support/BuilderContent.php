<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;

class BuilderContent
{
    public static function validate(array $content): void
    {
        foreach ($content['sections'] ?? [] as $index => $element) {
            if (($element['type'] ?? null) === 'icon') {
                Validator::make($element, [
                    'icon_name' => ['required', 'in:phone'],
                    'icon_size' => ['required', 'numeric', 'between:16,120'],
                    'icon_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                ])->validate();
            }
            if (($element['type'] ?? null) === 'color_panel') {
                Validator::make($element, [
                    'panel_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'panel_opacity' => ['sometimes', 'numeric', 'between:0,100'],
                    'panel_layer' => ['sometimes', 'integer', 'between:0,150'],
                    'element_height' => ['required', 'numeric', 'between:1,1500'],
                ])->validate();
            }
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
                    $rules['image_opacity'] = ['sometimes', 'numeric', 'between:0,100'];
                    $rules['image_layer'] = ['sometimes', 'integer', 'between:0,150'];
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
                    'process_design.show_numbers' => ['sometimes', 'boolean'],
                    'process_design.branch_visible' => ['sometimes', 'boolean'],
                    'process_design.heading_italic' => ['sometimes', 'boolean'],
                    'process_design.connector_style' => ['sometimes', 'in:line,arrow,none'],
                    'process_design.decorative_photo_id' => ['sometimes', 'nullable', 'integer', 'exists:photos,id'],
                ];
                foreach (['background', 'text', 'muted', 'accent', 'icon_background', 'icon_color', 'wash_color'] as $key) {
                    $rules['process_design.'.$key] = ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'];
                }
                foreach ([
                    'pad_x' => [0,160], 'pad_y' => [0,160],
                    'head_gap' => [0,140], 'section_gap' => [0,150],
                    'steps_gap' => [0,120], 'icon_size' => [36,150],
                    'title_size' => [20,100], 'title_weight' => [100,900],
                    'lead_size' => [10,36], 'step_title_size' => [12,40],
                    'step_description_size' => [10,30],
                    'wash_opacity' => [0,100],
                    'branch_opacity' => [0,100],
                    'branch_width' => [70,650],
                    'branch_x' => [-350,350],
                    'branch_y' => [-350,350],
                    'number_size' => [12,100],
                    'icon_border_width' => [0,8],
                    'connector_length' => [0,110],
                    'decorative_photo_opacity' => [0,100],
                    'decorative_photo_width' => [40,900],
                    'decorative_photo_x' => [-300,100],
                    'decorative_photo_y' => [-500,500],
                    'decorative_photo_rotate' => [-180,180],
                ] as $key => [$min, $max]) {
                    $rules['process_design.'.$key] = ['sometimes', 'numeric', 'between:'.$min.','.$max];
                }
                Validator::make($element, $rules)->validate();

                // Free 2D positions for exactly the editable parts of the process
                // section. Reject malformed data instead of accepting arbitrary CSS.
                if (array_key_exists('process_positions', $element)) {
                    $validParts = [
                        'kicker', 'title', 'intro', 'branch', 'decorative_photo',
                        'step1_number','step1_icon','step1_title','step1_description','step1_connector',
                        'step2_number','step2_icon','step2_title','step2_description','step2_connector',
                        'step3_number','step3_icon','step3_title','step3_description',
                    ];
                    $positions = $element['process_positions'];
                    if (!is_array($positions)) {
                        throw \\Illuminate\\Validation\\ValidationException::withMessages([
                            'process_positions' => 'Nieprawidłowe położenie elementów procesu.',
                        ]);
                    }
                    foreach ($positions as $part => $position) {
                        if (!in_array($part, $validParts, true) || !is_array($position)) {
                            throw \\Illuminate\\Validation\\ValidationException::withMessages([
                                'process_positions' => 'Niedozwolony element lub przesunięcie.',
                            ]);
                        }
                        Validator::make($position, [
                            'x' => ['sometimes','numeric','between:-1800,1800'],
                            'y' => ['sometimes','numeric','between:-1000,1000'],
                        ])->validate();
                    }
                }
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
            if (($element['type'] ?? null) === 'gallery_mosaic') {
                // One self-contained composition: six independently configured
                // gallery covers, safely linked to actual CMS pages/galleries.
                Validator::make($element, [
                    'mosaic_layout' => ['required', 'in:editorial,even'],
                    'mosaic_kicker' => ['nullable', 'string', 'max:120'],
                    'mosaic_title' => ['required', 'string', 'max:240'],
                    'mosaic_intro' => ['nullable', 'string', 'max:800'],
                    'mosaic_more_label' => ['nullable', 'string', 'max:120'],
                    'mosaic_more_action_text' => ['sometimes', 'string', 'max:24'],
                    'mosaic_more_link' => ['nullable', 'string', 'max:2048'],
                    'mosaic_gap' => ['required', 'numeric', 'between:0,60'],
                    'mosaic_row_height' => ['required', 'numeric', 'between:150,550'],
                    'mosaic_side_width' => ['required', 'numeric', 'between:17,34'],
                    'mosaic_title_size' => ['required', 'numeric', 'between:22,76'],
                    'mosaic_descriptions' => ['sometimes', 'boolean'],
                    'mosaic_more_button' => ['sometimes', 'boolean'],
                    'mosaic_more_bg' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'mosaic_more_border' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'mosaic_more_radius' => ['sometimes', 'numeric', 'between:0,100'],
                    'mosaic_more_padding_x' => ['sometimes', 'numeric', 'between:0,80'],
                    'mosaic_more_padding_y' => ['sometimes', 'numeric', 'between:0,50'],
                    'mosaic_typography' => ['sometimes', 'array'],
                    'mosaic_cards.*.typography' => ['sometimes', 'array'],
                    'mosaic_cards.*.action_text' => ['sometimes', 'string', 'max:24'],
                    'mosaic_cards' => ['required', 'array', 'list', 'size:6'],
                    'mosaic_cards.*' => ['required', 'array'],
                    'mosaic_cards.*.gallery_id' => ['nullable', 'integer', 'exists:galleries,id'],
                    'mosaic_cards.*.photo_id' => ['nullable', 'integer', 'exists:photos,id'],
                    'mosaic_cards.*.title' => ['nullable', 'string', 'max:160'],
                    'mosaic_cards.*.category' => ['nullable', 'string', 'max:160'],
                    'mosaic_cards.*.description' => ['nullable', 'string', 'max:300'],
                    'mosaic_cards.*.link' => ['nullable', 'string', 'max:2048'],
                    'mosaic_cards.*.crop_x' => ['required', 'integer', 'between:0,100'],
                    'mosaic_cards.*.crop_y' => ['required', 'integer', 'between:0,100'],
                ])->validate();
                MosaicTypography::validate($element);
                foreach ($element['mosaic_cards'] as $card) {
                    $url = trim((string) ($card['link'] ?? ''));
                    if ($url !== '' && BuilderButton::href($url) === null) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'mosaic_cards' => 'Wybierz prawidłowy link do podstrony dla karty galerii.',
                        ]);
                    }
                }
                $moreUrl = trim((string)($element['mosaic_more_link'] ?? ''));
                if ($moreUrl !== '' && BuilderButton::href($moreUrl) === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'mosaic_more_link' => 'Wybierz prawidłowy link do portfolio.',
                    ]);
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
