<?php

namespace App\Support;

use App\Services\SiteFontLibrary;
use Illuminate\Support\Facades\Validator;

/**
 * Shared typography contract for mosaic: one whitelist for saving and
 * the same CSS mapping for the public renderer as the editor preview.
 */
final class MosaicTypography
{
    public const ROLES = ['kicker', 'heading', 'intro', 'more', 'category', 'description', 'arrow'];
    public const CARD_ROLES = ['category', 'description', 'arrow'];

    public static function validate(array $element): void
    {
        $catalog = app(SiteFontLibrary::class)->catalog();
        $groups = [
            'mosaic_typography' => $element['mosaic_typography'] ?? [],
        ];
        foreach (($element['mosaic_cards'] ?? []) as $index => $card) {
            $groups['mosaic_cards.'.$index.'.typography'] = $card['typography'] ?? [];
        }
        foreach ($groups as $key => $styles) {
            if (!is_array($styles)) {
                Validator::make([$key => $styles], [$key => ['array']])->validate();
            }
            $allowedRoles = str_starts_with($key, 'mosaic_cards.') ? self::CARD_ROLES : self::ROLES;
            foreach ($styles as $role => $style) {
                if (!in_array($role, $allowedRoles, true) || !is_array($style)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        $key => 'Niedozwolony typ tekstu w mozaice.',
                    ]);
                }
                Validator::make($style, [
                    'font_family' => ['sometimes', 'nullable', 'string', 'max:100', 'in:'.implode(',', array_keys($catalog['families']))],
                    'font_size' => ['sometimes', 'numeric', 'between:7,120'],
                    'font_weight' => ['sometimes', 'integer', 'in:100,200,300,400,500,600,700,800,900'],
                    'font_style' => ['sometimes', 'in:normal,italic'],
                    'text_transform' => ['sometimes', 'in:none,uppercase,lowercase,capitalize'],
                    'text_align' => ['sometimes', 'in:left,center,right,justify'],
                    'line_height' => ['sometimes', 'numeric', 'between:0.7,3.2'],
                    'letter_spacing' => ['sometimes', 'numeric', 'between:-10,30'],
                    'word_spacing' => ['sometimes', 'numeric', 'between:-20,60'],
                    'color' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'text_decoration' => ['sometimes', 'in:none,underline,line-through'],
                ])->validate();
            }
        }
    }

    public static function css(array $style, array $catalog): string
    {
        $css = [];
        $number = static function ($value, $min, $max): ?float {
            return is_numeric($value) && is_finite((float) $value)
                ? max($min, min($max, (float) $value)) : null;
        };
        $family = $style['font_family'] ?? null;
        if (is_string($family) && isset($catalog['families'][$family])) {
            $css[] = 'font-family:'.SiteFontLibrary::css($family, $catalog);
        }
        $values = [
            'font_size' => ['font-size',7,120,'px'],
            'font_weight' => ['font-weight',100,900,''],
            'line_height' => ['line-height',0.7,3.2,''],
            'letter_spacing' => ['letter-spacing',-10,30,'px'],
            'word_spacing' => ['word-spacing',-20,60,'px'],
        ];
        foreach ($values as $key => [$property,$min,$max,$unit]) {
            if (!array_key_exists($key, $style)) continue;
            $value = $number($style[$key],$min,$max);
            if ($value === null) continue;
            $css[] = $property.':'.$value.$unit;
        }
        foreach ([
            'font_style' => ['font-style',['normal','italic']],
            'text_transform' => ['text-transform',['none','uppercase','lowercase','capitalize']],
            'text_align' => ['text-align',['left','center','right','justify']],
            'text_decoration' => ['text-decoration',['none','underline','line-through']],
        ] as $key => [$property,$allowed]) {
            if (in_array($style[$key] ?? null,$allowed,true)) $css[]=$property.':'.$style[$key];
        }
        if (is_string($style['color'] ?? null) && preg_match('/^#[0-9a-fA-F]{6}$/', $style['color'])) {
            $css[] = 'color:'.$style['color'];
        }
        return implode(';',$css);
    }
}