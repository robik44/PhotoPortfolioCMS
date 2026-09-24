<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;

class BuilderContent
{
    public static function validate(array $content): void
    {
        foreach ($content['sections'] ?? [] as $element) {
            if (($element['type'] ?? null) === 'heading') {
                Validator::make($element, ['heading_level' => ['sometimes', 'in:div,h1,h2,h3']])->validate();
            }
            if (($element['type'] ?? null) === 'gallery') {
                Validator::make($element, [
                    'gallery_mode' => ['sometimes', 'in:all,single'],
                    'gallery_id' => ['required_if:gallery_mode,single', 'nullable', 'integer', 'exists:galleries,id'],
                ])->validate();
            }
        }
    }
}
