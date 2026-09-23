<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class HeaderFontFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $formats = [
            'woff2' => ['signature' => 'wOF2', 'mimes' => ['font/woff2', 'application/font-woff2']],
            'woff' => ['signature' => 'wOFF', 'mimes' => ['font/woff', 'application/font-woff', 'application/x-font-woff']],
            'ttf' => ['signature' => "\x00\x01\x00\x00", 'mimes' => ['font/ttf', 'font/sfnt', 'application/x-font-ttf', 'application/font-sfnt', 'application/x-font-truetype']],
            'otf' => ['signature' => 'OTTO', 'mimes' => ['font/otf', 'font/sfnt', 'application/vnd.ms-opentype', 'application/x-font-opentype', 'application/font-sfnt']],
        ];

        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('Nie udało się odczytać pliku czcionki.');
            return;
        }

        $format = $formats[strtolower($value->getClientOriginalExtension())] ?? null;
        if (!$format || !in_array($value->getMimeType(), $format['mimes'], true)
            || file_get_contents($value->getRealPath(), false, null, 0, 4) !== $format['signature']) {
            $fail('Wybierz prawidłową czcionkę WOFF2, WOFF, TTF lub OTF. Rozszerzenie i zawartość muszą być zgodne.');
        }
    }
}
