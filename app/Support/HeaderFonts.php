<?php

namespace App\Support;

class HeaderFonts
{
    public const SETTING_KEY = 'header_custom_fonts';

    public const SYSTEM = [
        'Arial' => 'Arial, sans-serif',
        'Helvetica' => 'Helvetica, Arial, sans-serif',
        'Georgia' => 'Georgia, serif',
        'Times New Roman' => '"Times New Roman", serif',
        'Verdana' => 'Verdana, sans-serif',
        'Trebuchet MS' => '"Trebuchet MS", sans-serif',
        'Courier New' => '"Courier New", monospace',
        'system-ui' => 'system-ui, sans-serif',
    ];

    public const FORMATS = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'];

    public static function custom(array $settings): array
    {
        $raw = $settings[self::SETTING_KEY] ?? null;
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $fonts = [];

        foreach (is_array($decoded) ? $decoded : [] as $id => $font) {
            if (!is_string($id) || !preg_match('/\Ahf_[a-f0-9]{32}\z/', $id) || !is_array($font)) {
                continue;
            }
            $path = $font['path'] ?? null;
            if (!is_string($path) || !preg_match('/\Afonts\/' . $id . '\.(woff2|woff|ttf|otf)\z/', $path, $match)) {
                continue;
            }
            $fonts[$id] = [
                'label' => is_string($font['label'] ?? null) ? mb_substr($font['label'], 0, 100) : 'Własna czcionka',
                'path' => $path,
                'format' => self::FORMATS[$match[1]],
            ];
        }

        return $fonts;
    }

    public static function families(array $custom): array
    {
        $families = self::SYSTEM;
        foreach ($custom as $id => $font) {
            $families[$id] = $id . ', Arial, sans-serif';
        }

        return $families;
    }
}
