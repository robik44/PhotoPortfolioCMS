<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HeaderSettings
{
    public const DEFAULTS = [
        'logo' => 'ROBERT WOŹNIAK',
        'logo_subtitle' => 'FOTOGRAFIA',
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
        'header_layout' => 'left',
    ];

    public static function keys(): array
    {
        return [...array_keys(self::DEFAULTS), HeaderFonts::SETTING_KEY];
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
            'header_layout' => ['required', 'in:left,center'],
        ];
    }

    public static function resolve(array $settings): array
    {
        $values = array_intersect_key($settings, self::DEFAULTS);
        $validator = Validator::make($values, self::rules(HeaderFonts::custom($settings)));
        $validator->passes();

        foreach (self::DEFAULTS as $key => $default) {
            if (!isset($values[$key]) || $validator->errors()->has($key)) {
                $values[$key] = $default;
            }
        }

        return $values;
    }
}
