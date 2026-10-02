<?php

namespace App\Support;

use App\Services\SiteFontLibrary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BuilderTypography
{
    public const BREAKPOINTS = ['desktop', 'tablet', 'mobile'];
    public const TABLET_MAX = 900;
    public const MOBILE_MAX = 520;

    public static function validate(array $element, int $index): void
    {
        if (!array_key_exists('typography', $element)) {
            return;
        }

        if (!is_array($element['typography'])) {
            throw ValidationException::withMessages([
                "content.sections.$index.typography" => 'Ustawienia typografii muszą być obiektem.',
            ]);
        }

        $typography = $element['typography'];

        if (isset($typography['mode']) && !in_array($typography['mode'], ['manual', 'fluid'], true)) {
            throw ValidationException::withMessages([
                "content.sections.$index.typography.mode" => 'Nieprawidłowy tryb typografii.',
            ]);
        }

        if (isset($typography['fluid'])) {
            Validator::make($typography['fluid'], [
                'min_size' => ['sometimes', 'nullable', 'numeric', 'between:1,300'],
                'max_size' => ['sometimes', 'nullable', 'numeric', 'between:1,300'],
                'vw' => ['sometimes', 'nullable', 'numeric', 'between:0.1,20'],
            ])->validate();
        }

        $catalog = app(SiteFontLibrary::class)->catalog();

        foreach (self::BREAKPOINTS as $breakpoint) {
            if (!isset($typography[$breakpoint])) {
                continue;
            }

            if (!is_array($typography[$breakpoint])) {
                throw ValidationException::withMessages([
                    "content.sections.$index.typography.$breakpoint" => 'Ustawienia breakpointu muszą być obiektem.',
                ]);
            }

            Validator::make($typography[$breakpoint], [
                'font_family' => ['sometimes', 'nullable', 'string', 'max:100'],
                'font_size' => ['sometimes', 'nullable', 'numeric', 'between:1,300'],
                'font_weight' => ['sometimes', 'nullable', 'integer', 'between:100,900'],
                'font_style' => ['sometimes', 'nullable', 'in:normal,italic'],
                'text_transform' => ['sometimes', 'nullable', 'in:none,uppercase,lowercase,capitalize'],
                'text_align' => ['sometimes', 'nullable', 'in:left,center,right,justify'],
                'line_height' => ['sometimes', 'nullable', 'numeric', 'between:0.1,10'],
                'line_height_unit' => ['sometimes', 'nullable', 'in:unitless,px'],
                'letter_spacing' => ['sometimes', 'nullable', 'numeric', 'between:-10,20'],
                'letter_spacing_unit' => ['sometimes', 'nullable', 'in:px,em'],
                'word_spacing' => ['sometimes', 'nullable', 'numeric', 'between:-50,100'],
                'color' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'color_alpha' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
                'opacity' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
                'paragraph_spacing' => ['sometimes', 'nullable', 'numeric', 'between:0,500'],
                'margin_top' => ['sometimes', 'nullable', 'numeric', 'between:-500,500'],
                'margin_bottom' => ['sometimes', 'nullable', 'numeric', 'between:-500,500'],
                'text_width' => ['sometimes', 'nullable', 'numeric', 'between:0,2000'],
                'text_width_unit' => ['sometimes', 'nullable', 'in:px,%'],
                'max_width' => ['sometimes', 'nullable', 'numeric', 'between:0,3000'],
                'max_line_length' => ['sometimes', 'nullable', 'numeric', 'between:10,120'],
                'offset_x' => ['sometimes', 'nullable', 'numeric', 'between:-1000,1000'],
                'offset_y' => ['sometimes', 'nullable', 'numeric', 'between:-1000,1000'],
            ])->validate();

            $font = $typography[$breakpoint]['font_family'] ?? null;
            if ($font !== null && $font !== '' && !isset($catalog['families'][$font])) {
                throw ValidationException::withMessages([
                    "content.sections.$index.typography.$breakpoint.font_family" => 'Wybierz rodzaj czcionki z biblioteki.',
                ]);
            }
        }
    }

    public static function base(array $element, array $catalog): array
    {
        $style = is_array($element['style'] ?? null) ? $element['style'] : [];

        return array_replace([
            'font_family' => 'Arial',
            'font_size' => ($element['type'] ?? null) === 'heading' ? 42 : 18,
            'font_weight' => 400,
            'font_style' => 'normal',
            'text_transform' => 'none',
            'text_align' => 'left',
            'line_height' => 1.4,
            'line_height_unit' => 'unitless',
            'letter_spacing' => 0,
            'letter_spacing_unit' => 'px',
            'word_spacing' => 0,
            'color' => '#222222',
            'color_alpha' => 100,
            'opacity' => 100,
            'paragraph_spacing' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'offset_x' => 0,
            'offset_y' => 0,
        ], $style, self::breakpoint($element, 'desktop'));
    }

    public static function breakpoint(array $element, string $breakpoint): array
    {
        $values = $element['typography'][$breakpoint] ?? [];

        return is_array($values)
            ? array_filter($values, static fn ($value) => $value !== null && $value !== '')
            : [];
    }

    public static function resolved(array $element, string $breakpoint, array $catalog): array
    {
        $values = self::base($element, $catalog);

        if (in_array($breakpoint, ['tablet', 'mobile'], true)) {
            $values = array_replace($values, self::breakpoint($element, 'tablet'));
        }

        if ($breakpoint === 'mobile') {
            $values = array_replace($values, self::breakpoint($element, 'mobile'));
        }

        return $values;
    }

    public static function inlineCss(array $element, array $catalog): string
    {
        return self::css(self::base($element, $catalog), $catalog, $element, true, false);
    }

    public static function responsiveCss(array $element, array $catalog, string $selector): string
    {
        $css = '';

        foreach (['tablet' => self::TABLET_MAX, 'mobile' => self::MOBILE_MAX] as $breakpoint => $maxWidth) {
            if (!self::breakpoint($element, $breakpoint)) {
                continue;
            }

            $resolved = self::resolved($element, $breakpoint, $catalog);
            $css .= '@media (max-width:' . $maxWidth . 'px){' . $selector . '{'
                . self::css($resolved, $catalog, $element, false, true) . '}}';
        }

        return $css;
    }

    private static function css(
        array $values,
        array $catalog,
        array $element,
        bool $includeFluid,
        bool $important
    ): string {
        $suffix = $important ? ' !important' : '';
        $out = static fn (string $property, string $value): string => $property . ':' . $value . $suffix . ';';
        $css = '';

        $css .= $out('font-family', SiteFontLibrary::css($values['font_family'] ?? null, $catalog));

        $fontSize = self::num($values['font_size'] ?? null, 1, 300);
        if ($includeFluid && ($element['typography']['mode'] ?? null) === 'fluid') {
            $fluid = is_array($element['typography']['fluid'] ?? null) ? $element['typography']['fluid'] : [];
            $min = self::num($fluid['min_size'] ?? null, 1, 300);
            $max = self::num($fluid['max_size'] ?? null, 1, 300);
            $vw = self::num($fluid['vw'] ?? null, 0.1, 20);
            if ($min !== null && $max !== null && $vw !== null) {
                $fontSize = 'clamp(' . self::fmt(min($min, $max)) . 'px,' . self::fmt($vw)
                    . 'vw,' . self::fmt(max($min, $max)) . 'px)';
            }
        }

        if (is_float($fontSize)) {
            $fontSize = self::fmt($fontSize) . 'px';
        }
        if (is_string($fontSize)) {
            $css .= $out('font-size', $fontSize);
        }

        $css .= $out('font-weight', (string) max(100, min(900, (int) ($values['font_weight'] ?? 400))));

        $fontStyle = in_array($values['font_style'] ?? 'normal', ['normal', 'italic'], true)
            ? $values['font_style'] : 'normal';
        $css .= $out('font-style', $fontStyle);

        $transform = in_array($values['text_transform'] ?? 'none', ['none', 'uppercase', 'lowercase', 'capitalize'], true)
            ? $values['text_transform'] : 'none';
        $css .= $out('text-transform', $transform);

        $align = in_array($values['text_align'] ?? 'left', ['left', 'center', 'right', 'justify'], true)
            ? $values['text_align'] : 'left';
        $css .= $out('text-align', $align);

        $line = self::num($values['line_height'] ?? null, 0.1, 10);
        if ($line !== null) {
            $css .= $out(
                'line-height',
                self::fmt($line) . (($values['line_height_unit'] ?? 'unitless') === 'px' ? 'px' : '')
            );
        }

        $letter = self::num($values['letter_spacing'] ?? null, -10, 20);
        if ($letter !== null) {
            $css .= $out(
                'letter-spacing',
                self::fmt($letter) . (($values['letter_spacing_unit'] ?? 'px') === 'em' ? 'em' : 'px')
            );
        }

        $word = self::num($values['word_spacing'] ?? null, -50, 100);
        if ($word !== null) {
            $css .= $out('word-spacing', self::fmt($word) . 'px');
        }

        $color = (string) ($values['color'] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $alpha = self::num($values['color_alpha'] ?? 100, 0, 100) ?? 100;
            if ($alpha >= 100) {
                $css .= $out('color', $color);
            } else {
                $r = hexdec(substr($color, 1, 2));
                $g = hexdec(substr($color, 3, 2));
                $b = hexdec(substr($color, 5, 2));
                $css .= $out('color', 'rgba(' . $r . ',' . $g . ',' . $b . ',' . self::fmt($alpha / 100) . ')');
            }
        }

        $opacity = self::num($values['opacity'] ?? 100, 0, 100);
        if ($opacity !== null) {
            $css .= $out('opacity', self::fmt($opacity / 100));
        }

        foreach (['margin_top' => 'margin-top', 'margin_bottom' => 'margin-bottom'] as $key => $property) {
            $number = self::num($values[$key] ?? null, -500, 500);
            if ($number !== null) {
                $css .= $out($property, self::fmt($number) . 'px');
            }
        }

        $width = self::num($values['text_width'] ?? null, 0, 2000);
        if ($width !== null) {
            $unit = ($values['text_width_unit'] ?? '%') === 'px' ? 'px' : '%';
            $css .= $out('width', self::fmt($width) . $unit);
        }

        $lineLength = self::num($values['max_line_length'] ?? null, 10, 120);
        $maxWidth = self::num($values['max_width'] ?? null, 0, 3000);
        if ($lineLength !== null) {
            $css .= $out('max-width', self::fmt($lineLength) . 'ch');
        } elseif ($maxWidth !== null) {
            $css .= $out('max-width', self::fmt($maxWidth) . 'px');
        }

        $x = self::num($values['offset_x'] ?? 0, -1000, 1000) ?? 0;
        $y = self::num($values['offset_y'] ?? 0, -1000, 1000) ?? 0;
        if ($x != 0.0 || $y != 0.0) {
            $css .= $out('transform', 'translate(' . self::fmt($x) . 'px,' . self::fmt($y) . 'px)');
        }

        return $css;
    }

    private static function num(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return max($min, min($max, (float) $value));
    }

    private static function fmt(float|int $value): string
    {
        $formatted = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
        return $formatted === '-0' ? '0' : $formatted;
    }
}
