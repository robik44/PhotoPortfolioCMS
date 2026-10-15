<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Inserts the SVG as a real, movable homepage builder element.
// Run once on production after saving the contact section.
Artisan::command('foodfoto:place-phone-icon', function () {
    $builder = \App\Models\PageBuilder::query()
        ->whereNull('page_id')->where('type', 'home')->first();

    if (!$builder) {
        $this->error('Brak buildera strony głównej. Niczego nie zmieniono.');
        return 1;
    }

    $content = $builder->content ?? [];
    $sections = array_values($content['sections'] ?? []);
    if (collect($sections)->contains(fn ($row) => ($row['id'] ?? '') === 'foodfoto-contact-phone-icon')) {
        $this->info('Ikona telefonu jest już w builderze. Niczego nie zmieniono.');
        return 0;
    }

    $normalize = static fn (array $item) => mb_strtolower(
        trim(preg_replace('/\s+/u', ' ', (string) ($item['content'] ?? '')))
    );
    $label = collect($sections)
        ->filter(fn ($row) => in_array($row['type'] ?? null, ['text', 'heading'], true)
            && str_contains($normalize($row), 'zadzwo'))
        ->sortByDesc(fn ($row) => (float) ($row['position_y'] ?? 0))
        ->first();

    if (!$label) {
        $this->error('Nie znalazłem napisu "LUB ZADZWOŃ" w zapisanym builderze. Zapisz sekcję i ponów komendę.');
        return 1;
    }

    $labelY = (float) ($label['position_y'] ?? 0);
    $mailIcon = collect($sections)
        ->filter(fn ($row) => in_array($row['type'] ?? null, ['text', 'heading'], true)
            && str_contains((string) ($row['content'] ?? ''), '✉')
            && abs((float) ($row['position_y'] ?? 0) - $labelY) < 65)
        ->sortByDesc(fn ($row) => (float) ($row['position_y'] ?? 0))
        ->first();
    $mailLabel = collect($sections)
        ->filter(fn ($row) => in_array($row['type'] ?? null, ['text', 'heading'], true)
            && str_contains($normalize($row), 'napisz do mnie')
            && abs((float) ($row['position_y'] ?? 0) - $labelY) < 65)
        ->first();

    // Align with the already-present envelope if possible.
    $x = $mailIcon
        ? (float) ($mailIcon['position_x'] ?? 56)
        : ($mailLabel
            ? (float) ($mailLabel['position_x'] ?? 64) - 6.0
            : (float) ($label['position_x'] ?? 64) - 6.0);
    $x = max(0, min(94, $x));
    $y = max(0, $labelY - 0.6);

    $icon = [
        'id' => 'foodfoto-contact-phone-icon',
        'type' => 'icon',
        'icon_name' => 'phone',
        'icon_size' => 40,
        'icon_color' => '#242424',
        'position_x' => $x,
        'position_y' => $y,
        'element_width' => 5,
        'element_height' => 0,
        'flow_mode' => 'free',
        'flow_anchor' => (float) ($label['flow_anchor'] ?? 0),
        'flow_anchor_contributions' => $label['flow_anchor_contributions'] ?? [],
        'style' => ['color' => '#242424'],
    ];
    if (!empty($label['flow_group'])) {
        $icon['flow_group'] = $label['flow_group'];
    }

    $directory = storage_path('app/backups');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        $this->error('Nie mogę utworzyć kopii zapasowej. Niczego nie zmieniono.');
        return 1;
    }
    $backup = $directory.'/home-before-phone-icon-'.date('Ymd-His').'.json';
    $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!$json || file_put_contents($backup, $json) === false) {
        $this->error('Nie udało się zapisać kopii zapasowej. Niczego nie zmieniono.');
        return 1;
    }

    $sections[] = $icon;
    $content['sections'] = $sections;
    $builder->content = $content;
    $builder->save();

    $this->info('Gotowe: ikona telefonu dodana pod kopertą, obok „LUB ZADZWOŃ”.');
    $this->line('Położenie w builderze: X='.round($x, 2).'%, Y='.round($y, 2).'%.');
    $this->line('Kopia ustawień: '.$backup);
    $this->line('Odśwież builder oraz stronę główną. Ikonę możesz normalnie przeciągać.');
    return 0;
})->purpose('Dodaj ikonę telefonu obok numeru w sekcji kontaktowej FoodFoto');
