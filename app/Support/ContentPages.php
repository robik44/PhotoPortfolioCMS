<?php

namespace App\Support;

use App\Models\Page;

class ContentPages
{
    public const PAGES = [
        'o-mnie' => ['view' => 'about', 'title' => 'O mnie', 'text' => 'Tworzę fotografie, które opowiadają historie poprzez światło, formę i detal.'],
        'kontakt' => ['view' => 'contact', 'title' => 'Kontakt', 'text' => 'Zapraszam do współpracy.'],
        'polityka-prywatnosci' => [
            'view' => 'privacy',
            'title' => 'Polityka prywatności',
            'text' => "Administratorem danych związanych z korzystaniem z serwisu foodfoto.pl jest właściciel serwisu. Kontakt z administratorem jest możliwy przez dane podane na stronie Kontakt.\n\nSerwis nie prowadzi newslettera i obecnie nie korzysta z narzędzi reklamowych ani analitycznych wymagających marketingowych plików cookies. Serwis może używać technicznych plików cookies niezbędnych do działania, w szczególności obsługi sesji i panelu administracyjnego.\n\nSerwer może zapisywać standardowe logi techniczne, takie jak adres IP, czas żądania i informacje o przeglądarce, w celach bezpieczeństwa, diagnostyki i utrzymania usługi.\n\nJeżeli kontaktujesz się za pośrednictwem poczty elektronicznej, przekazane dane są wykorzystywane do obsługi korespondencji, przygotowania oferty oraz realizacji ewentualnej współpracy i obowiązków prawnych. Dane nie są sprzedawane.\n\nW zakresie przewidzianym przez RODO przysługuje prawo dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, sprzeciwu oraz wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych.\n\nPolityka będzie aktualizowana, jeżeli w serwisie pojawią się nowe narzędzia analityczne, marketingowe, formularze lub inne funkcje przetwarzające dane.",
        ],
    ];

    /** Preview only: no records or existing layouts are changed here. */
    public static function initialContent(Page $page): array
    {
        $defaults = self::PAGES[$page->slug];
        return [
            'version' => 1,
            'settings' => [],
            'sections' => [
                ['id' => 'initial-heading', 'type' => 'heading', 'content' => $page->title ?: $defaults['title'], 'heading_level' => 'h1', 'semantic_tag' => 'h1',
                    'position_x' => 5, 'position_y' => 5, 'element_width' => 85,
                    'style' => ['font_size' => 42, 'font_weight' => 400, 'color' => '#222222']],
                ['id' => 'initial-text', 'type' => 'text', 'semantic_tag' => 'p', 'content' => $page->content ?: $defaults['text'],
                    'position_x' => 5, 'position_y' => 16, 'element_width' => 85,
                    'style' => ['font_size' => 18, 'font_weight' => 400, 'color' => '#222222']],
            ],
        ];
    }
}
