<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\SiteFontLibrary;
use Illuminate\Validation\Rule;

/** Optional typography overrides stored in the existing site_settings table. */
class TypographySettings
{
    public const BREAKPOINTS = ['desktop', 'tablet', 'mobile'];

    public const PROPERTIES = [
        'font_family',
        'font_size',
        'font_weight',
        'font_style',
        'text_transform',
        'text_align',
        'line_height',
        'line_height_unit',
        'letter_spacing',
        'letter_spacing_unit',
        'word_spacing',
        'color',
        'color_alpha',
        'opacity',
        'paragraph_spacing',
        'margin_top',
        'margin_bottom',
        'text_width',
        'text_width_unit',
        'max_width',
        'max_line_length',
        'offset_x',
        'offset_y',
    ];

    public static function key(string $field, string $property, string $breakpoint = 'desktop'): string
    {
        return $breakpoint === 'desktop'
            ? $field . '_' . $property
            : $field . '_' . $breakpoint . '_' . $property;
    }

    public static function keys(array $fields = ['title', 'description']): array
    {
        $keys = [];
        foreach ($fields as $field) {
            foreach (self::BREAKPOINTS as $breakpoint) {
                foreach (self::PROPERTIES as $property) {
                    $keys[] = self::key($field, $property, $breakpoint);
                }
            }
        }

        return $keys;
    }

    public static function rules(array $fields = ['title', 'description']): array
    {
        $rules = [];
        $families = array_keys(app(SiteFontLibrary::class)->catalog()['families']);

        foreach ($fields as $field) {
            foreach (self::BREAKPOINTS as $breakpoint) {
                $rules[self::key($field, 'font_family', $breakpoint)] = ['sometimes', 'nullable', 'string', Rule::in($families)];
                $rules[self::key($field, 'font_size', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:1,300'];
                $rules[self::key($field, 'font_weight', $breakpoint)] = ['sometimes', 'nullable', 'integer', 'between:100,900'];
                $rules[self::key($field, 'font_style', $breakpoint)] = ['sometimes', 'nullable', Rule::in(['normal', 'italic'])];
                $rules[self::key($field, 'text_transform', $breakpoint)] = ['sometimes', 'nullable', Rule::in(['none', 'uppercase', 'lowercase', 'capitalize'])];
                $rules[self::key($field, 'text_align', $breakpoint)] = ['sometimes', 'nullable', Rule::in(['left', 'center', 'right', 'justify'])];
                $rules[self::key($field, 'line_height', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:0.1,10'];
                $rules[self::key($field, 'line_height_unit', $breakpoint)] = ['sometimes', 'nullable', Rule::in(['unitless', 'px'])];
                $rules[self::key($field, 'letter_spacing', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:-10,20'];
                $rules[self::key($field, 'letter_spacing_unit', $breakpoint)] = ['sometimes', 'nullable', Rule::in(['px', 'em'])];
                $rules[self::key($field, 'word_spacing', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:-50,100'];
                $rules[self::key($field, 'color', $breakpoint)] = ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
                $rules[self::key($field, 'color_alpha', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:0,100'];
                $rules[self::key($field, 'opacity', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:0,100'];
                $rules[self::key($field, 'paragraph_spacing', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:0,500'];
                $rules[self::key($field, 'margin_top', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:-500,500'];
                $rules[self::key($field, 'margin_bottom', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:-500,500'];
                $rules[self::key($field, 'text_width', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:0,2000'];
                $rules[self::key($field, 'text_width_unit', $breakpoint)] = ['sometimes', 'nullable', Rule::in(['px', '%'])];
                $rules[self::key($field, 'max_width', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:0,3000'];
                $rules[self::key($field, 'max_line_length', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:10,120'];
                $rules[self::key($field, 'offset_x', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:-1000,1000'];
                $rules[self::key($field, 'offset_y', $breakpoint)] = ['sometimes', 'nullable', 'numeric', 'between:-1000,1000'];
            }
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
        if (!$values) {
            return;
        }

        $raw = SiteSetting::where('key', $key)->value('value');
        $current = self::read([$key => $raw], $key);
        $updated = array_filter(
            array_replace($current, $values),
            static fn ($value) => $value !== null && $value !== ''
        );

        if ($updated === $current) {
            return;
        }

        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($updated, JSON_THROW_ON_ERROR)]
        );
    }

    /** Empty overrides emit no CSS, preserving the existing cascade. */
    public static function css(
        array $values,
        string $field,
        array $catalog,
        string $breakpoint = 'desktop',
        bool $important = false
    ): string {
        $suffix = $important ? ' !important' : '';
        $css = '';

        $fontFamilyKey = self::key($field, 'font_family', $breakpoint);
        if (!empty($values[$fontFamilyKey])) {
            $css .= 'font-family:' . SiteFontLibrary::css($values[$fontFamilyKey], $catalog) . $suffix . ';';
        }

        $fontSizeKey = self::key($field, 'font_size', $breakpoint);
        if (isset($values[$fontSizeKey]) && is_numeric($values[$fontSizeKey])) {
            $css .= 'font-size:' . self::fmt(self::clamp($values[$fontSizeKey], 1, 300)) . 'px' . $suffix . ';';
        }

        $fontWeightKey = self::key($field, 'font_weight', $breakpoint);
        if (isset($values[$fontWeightKey]) && is_numeric($values[$fontWeightKey])) {
            $css .= 'font-weight:' . (int) self::clamp($values[$fontWeightKey], 100, 900) . $suffix . ';';
        }

        $fontStyleKey = self::key($field, 'font_style', $breakpoint);
        if (!empty($values[$fontStyleKey])) {
            $css .= 'font-style:' . $values[$fontStyleKey] . $suffix . ';';
        }

        $transformKey = self::key($field, 'text_transform', $breakpoint);
        if (!empty($values[$transformKey])) {
            $css .= 'text-transform:' . $values[$transformKey] . $suffix . ';';
        }

        $alignKey = self::key($field, 'text_align', $breakpoint);
        if (!empty($values[$alignKey])) {
            $css .= 'text-align:' . $values[$alignKey] . $suffix . ';';
        }

        $lineKey = self::key($field, 'line_height', $breakpoint);
        if (isset($values[$lineKey]) && is_numeric($values[$lineKey])) {
            $unit = self::resolved($values, $field, 'line_height_unit', $breakpoint, 'unitless') === 'px' ? 'px' : '';
            $css .= 'line-height:' . self::fmt(self::clamp($values[$lineKey], 0.1, 10)) . $unit . $suffix . ';';
        }

        $letterKey = self::key($field, 'letter_spacing', $breakpoint);
        if (isset($values[$letterKey]) && is_numeric($values[$letterKey])) {
            $unit = self::resolved($values, $field, 'letter_spacing_unit', $breakpoint, 'px') === 'em' ? 'em' : 'px';
            $css .= 'letter-spacing:' . self::fmt(self::clamp($values[$letterKey], -10, 20)) . $unit . $suffix . ';';
        }

        $wordKey = self::key($field, 'word_spacing', $breakpoint);
        if (isset($values[$wordKey]) && is_numeric($values[$wordKey])) {
            $css .= 'word-spacing:' . self::fmt(self::clamp($values[$wordKey], -50, 100)) . 'px' . $suffix . ';';
        }

        $colorKey = self::key($field, 'color', $breakpoint);
        $alphaKey = self::key($field, 'color_alpha', $breakpoint);
        if (!empty($values[$colorKey]) || isset($values[$alphaKey])) {
            $color = self::resolved($values, $field, 'color', $breakpoint, null);
            if (is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $alpha = self::resolved($values, $field, 'color_alpha', $breakpoint, 100);
                $css .= 'color:' . self::color($color, (float) $alpha) . $suffix . ';';
            }
        }

        $opacityKey = self::key($field, 'opacity', $breakpoint);
        if (isset($values[$opacityKey]) && is_numeric($values[$opacityKey])) {
            $css .= 'opacity:' . self::fmt(self::clamp($values[$opacityKey], 0, 100) / 100) . $suffix . ';';
        }

        foreach ([
            'paragraph_spacing' => '--typography-paragraph-spacing',
            'margin_top' => 'margin-top',
            'margin_bottom' => 'margin-bottom',
        ] as $property => $cssProperty) {
            $key = self::key($field, $property, $breakpoint);
            if (isset($values[$key]) && is_numeric($values[$key])) {
                $css .= $cssProperty . ':' . self::fmt((float) $values[$key]) . 'px' . $suffix . ';';
            }
        }

        $widthKey = self::key($field, 'text_width', $breakpoint);
        if (isset($values[$widthKey]) && is_numeric($values[$widthKey])) {
            $unit = self::resolved($values, $field, 'text_width_unit', $breakpoint, '%') === 'px' ? 'px' : '%';
            $css .= 'width:' . self::fmt(self::clamp($values[$widthKey], 0, 2000)) . $unit . $suffix . ';';
        }

        $lineLengthKey = self::key($field, 'max_line_length', $breakpoint);
        $maxWidthKey = self::key($field, 'max_width', $breakpoint);
        if (isset($values[$lineLengthKey]) && is_numeric($values[$lineLengthKey])) {
            $css .= 'max-width:' . self::fmt(self::clamp($values[$lineLengthKey], 10, 120)) . 'ch' . $suffix . ';';
        } elseif (isset($values[$maxWidthKey]) && is_numeric($values[$maxWidthKey])) {
            $css .= 'max-width:' . self::fmt(self::clamp($values[$maxWidthKey], 0, 3000)) . 'px' . $suffix . ';';
        }

        $xKey = self::key($field, 'offset_x', $breakpoint);
        $yKey = self::key($field, 'offset_y', $breakpoint);
        if (isset($values[$xKey]) || isset($values[$yKey])) {
            $x = (float) self::resolved($values, $field, 'offset_x', $breakpoint, 0);
            $y = (float) self::resolved($values, $field, 'offset_y', $breakpoint, 0);
            $css .= 'transform:translate(' . self::fmt($x) . 'px,' . self::fmt($y) . 'px)' . $suffix . ';';
        }

        return $css;
    }

    public static function responsiveCss(array $values, string $field, array $catalog, string $selector): string
    {
        $css = '';
        foreach (['tablet' => 900, 'mobile' => 520] as $breakpoint => $maxWidth) {
            if (!self::hasBreakpoint($values, $field, $breakpoint)) {
                continue;
            }

            $css .= '@media (max-width:' . $maxWidth . 'px){' . $selector . '{'
                . self::css($values, $field, $catalog, $breakpoint, true) . '}}';
        }

        return $css;
    }

    public static function hasBreakpoint(array $values, string $field, string $breakpoint): bool
    {
        foreach (self::PROPERTIES as $property) {
            $key = self::key($field, $property, $breakpoint);
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    private static function resolved(
        array $values,
        string $field,
        string $property,
        string $breakpoint,
        mixed $fallback
    ): mixed {
        $order = match ($breakpoint) {
            'mobile' => ['mobile', 'tablet', 'desktop'],
            'tablet' => ['tablet', 'desktop'],
            default => ['desktop'],
        };

        foreach ($order as $candidate) {
            $key = self::key($field, $property, $candidate);
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return $fallback;
    }

    private static function color(string $hex, float $alpha): string
    {
        $alpha = self::clamp($alpha, 0, 100);
        if ($alpha >= 100) {
            return $hex;
        }

        [$r, $g, $b] = [
            hexdec(substr($hex, 1, 2)),
            hexdec(substr($hex, 3, 2)),
            hexdec(substr($hex, 5, 2)),
        ];

        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . self::fmt($alpha / 100) . ')';
    }

    private static function clamp(mixed $value, float $min, float $max): float
    {
        return max($min, min($max, (float) $value));
    }

    private static function fmt(float|int $value): string
    {
        $formatted = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
        return $formatted === '-0' ? '0' : $formatted;
    }
}
