<?php

namespace App\Support;

use App\Models\Page;

class ContentPages
{
    public const PAGES = [
        'o-mnie' => ['view' => 'about', 'title' => 'O mnie', 'text' => 'Tworzę fotografie, które opowiadają historie poprzez światło, formę i detal.'],
        'kontakt' => ['view' => 'contact', 'title' => 'Kontakt', 'text' => 'Zapraszam do współpracy.'],
    ];

    /** Preview only: no records or existing layouts are changed here. */
    public static function initialContent(Page $page): array
    {
        $defaults = self::PAGES[$page->slug];
        return [
            'version' => 1,
            'settings' => [],
            'sections' => [
                ['id' => 'initial-heading', 'type' => 'heading', 'content' => $page->title ?: $defaults['title'],
                    'position_x' => 5, 'position_y' => 5, 'element_width' => 85,
                    'style' => ['font_size' => 42, 'font_weight' => 400, 'color' => '#222222']],
                ['id' => 'initial-text', 'type' => 'text', 'content' => $page->content ?: $defaults['text'],
                    'position_x' => 5, 'position_y' => 16, 'element_width' => 85,
                    'style' => ['font_size' => 18, 'font_weight' => 400, 'color' => '#222222']],
            ],
        ];
    }
}
