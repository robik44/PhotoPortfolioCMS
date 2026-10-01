<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;

class BuilderButton
{
    public static function href(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') return null;
        $value = trim($value);
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $value)) return null;
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) return $value;
        if (str_starts_with($value, '#')) return $value;
        if (preg_match('/^https?:\/\//i', $value) && filter_var($value, FILTER_VALIDATE_URL)) return $value;
        if (preg_match('/^(mailto|tel):[^:]+$/i', $value)) return $value;
        return null;
    }

    public static function validate(array $element): void
    {
        $rules = [
            'button_link' => ['sometimes', 'nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                if (self::href($value) === null) $fail('Podaj ścieżkę /strona, adres https://, mailto: lub tel:.');
            }],
            'button_new_tab' => ['sometimes', 'boolean'],
            'button_background_opacity' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
        ];
        foreach (['background', 'border_color'] as $field) $rules['button_'.$field] = ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        foreach (['border_width', 'radius', 'padding_y', 'padding_x'] as $field) $rules['button_'.$field] = ['sometimes', 'nullable', 'numeric', 'between:0,200'];
        Validator::make($element, $rules)->validate();
    }

    public static function css(array $element): string
    {
        $css = '';
        foreach (['background' => 'background-color', 'border_color' => 'border-color'] as $key => $property) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $element['button_'.$key] ?? '')) {
                if ($key === 'background' && isset($element['button_background_opacity']) && is_numeric($element['button_background_opacity'])) {
                    $hex = ltrim($element['button_background'], '#');
                    $alpha = max(0, min(100, (float) $element['button_background_opacity'])) / 100;
                    $css .= 'background:rgba('.hexdec(substr($hex, 0, 2)).','.hexdec(substr($hex, 2, 2)).','.hexdec(substr($hex, 4, 2)).','.$alpha.') !important;';
                } else {
                    $css .= $property.':'.$element['button_'.$key].';';
                }
            }
        }
        foreach (['border_width' => ['border-width'], 'radius' => ['border-radius'], 'padding_y' => ['padding-top', 'padding-bottom'], 'padding_x' => ['padding-left', 'padding-right']] as $key => $properties) {
            $value = $element['button_'.$key] ?? null;
            if ($value === null || !is_numeric($value)) continue;
            if ($key === 'border_width') $css .= 'border-style:solid;';
            foreach ($properties as $property) $css .= $property.':'.max(0, min(200, (float) $value)).'px;';
        }
        return $css;
    }
}
