<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HeaderSettings
{
    public const DEFAULTS = [
        'logo' => 'MAGDA GUGAŁA',
        'logo_subtitle' => 'FOTOGRAFIA ŻYWNOŚCI',
        'header_logo_font_family' => 'Arial',
        'header_subtitle_font_family' => 'Arial',
        'header_logo_font_size' => 28,
        'header_subtitle_font_size' => 10,
        'header_logo_font_weight' => 700,
        'header_subtitle_font_weight' => 400,
        'header_logo_color' => '#222222',
        'header_subtitle_color' => '#777777',
        'header_logo_letter_spacing' => 0.08,
        'header_subtitle_letter_spacing' => 0.14,
        'header_logo_subtitle_gap' => 4,
        'header_layout' => 'left',
        'header_padding_top' => 48,
        'header_padding_bottom' => 48,
    ];

    public static function keys(): array
    {
        return [...array_keys(self::DEFAULTS), 'header_padding_y', HeaderFonts::SETTING_KEY];
    }

    public static function rules(array $fonts = []): array
    {
        return [
            'logo' => ['required', 'string', 'max:255'],
            'logo_subtitle' => ['nullable', 'string', 'max:255'],
            'header_logo_font_family' => ['required', 'string', Rule::in(array_keys(HeaderFonts::families($fonts)))],
            'header_subtitle_font_family' => ['required', 'string', Rule::in(array_keys(HeaderFonts::families($fonts)))],
            'header_logo_font_size' => ['required', 'integer', 'between:8,96'],
            'header_subtitle_font_size' => ['required', 'integer', 'between:8,48'],
            'header_logo_font_weight' => ['required', 'integer', 'in:100,200,300,400,500,600,700,800,900'],
            'header_subtitle_font_weight' => ['required', 'integer', 'in:100,200,300,400,500,600,700,800,900'],
            'header_logo_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'header_subtitle_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'header_logo_letter_spacing' => ['required', 'numeric', 'between:0,1'],
            'header_subtitle_letter_spacing' => ['required', 'numeric', 'between:0,1'],
            'header_logo_subtitle_gap' => ['required', 'integer', 'between:0,80'],
            'header_layout' => ['required', 'in:left,center,right'],
            'header_padding_top' => ['sometimes', 'required', 'integer', 'between:0,160'],
            'header_padding_bottom' => ['sometimes', 'required', 'integer', 'between:0,160'],
        ];
    }

    public static function resolve(array $settings): array
    {
        $defaults = self::DEFAULTS;
        // Older installations have one shared padding value. Each new field
        // falls back independently until it is saved through the header form.
        $legacyPadding = $settings['header_padding_y'] ?? null;
        if (Validator::make(['padding' => $legacyPadding], [
            'padding' => ['required', 'integer', 'between:0,160'],
        ])->passes()) {
            $defaults['header_padding_top'] = (int) $legacyPadding;
            $defaults['header_padding_bottom'] = (int) $legacyPadding;
        }

        $values = array_intersect_key($settings, self::DEFAULTS);
        $validator = Validator::make($values, self::rules(HeaderFonts::custom($settings)));
        $validator->passes();

        foreach ($defaults as $key => $default) {
            if (!isset($values[$key]) || $validator->errors()->has($key)) {
                $values[$key] = $default;
            }
        }

        return $values;
    }
}
