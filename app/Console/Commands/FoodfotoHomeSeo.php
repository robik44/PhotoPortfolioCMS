<?php

namespace App\Console\Commands;

use App\Models\Gallery;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FoodfotoHomeSeo extends Command
{
    protected $signature = 'foodfoto:home-seo
        {--apply : Zapisz SEO strony głównej po utworzeniu kopii zapasowej}
        {--fix-overlap : Dodatkowo rozróżnij stare, identycznie ukierunkowane SEO podstrony food-styling}';

    protected $description = 'Audyt produkcyjnej strony głównej: nagłówki, teksty, frazy, alty, meta i konkurencja podstron. Bez zmian domyślnie.';

    private const HOME_TITLE = 'Stylistka żywności i fotografia kulinarna | Magda Gugała';

    private const HOME_DESCRIPTION = 'Magda Gugała — stylistka żywności (food stylistka) i fotografka kulinarna. Food styling, stylizacja potraw i zdjęcia reklamowe jedzenia oraz produktów.';

    public function handle(): int
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')
            ->where('published', true)->first();
        if (!$builder) {
            $this->error('Brak opublikowanego buildera strony głównej. Nic nie zmieniono.');
            return self::FAILURE;
        }
        $sections = collect($builder->content['sections'] ?? []);
        $settings = Seo::settings();
        $meta = Seo::meta(null);

        $this->info('FOODFOTO: AUDYT SEO STRONY GŁÓWNEJ (odczyt z produkcyjnej bazy CMS)');
        $this->line('URL i canonical: '.$meta['canonical']);
        $this->line('Robots: '.$meta['robots']);
        $this->line('TITLE TERAZ ('.mb_strlen($meta['title']).'): '.$meta['title']);
        $this->line('DESCRIPTION TERAZ ('.mb_strlen($meta['description']).'): '.$meta['description']);
        $this->line('TITLE PROPONOWANY ('.mb_strlen(self::HOME_TITLE).'): '.self::HOME_TITLE);
        $this->line('DESCRIPTION PROPONOWANY ('.mb_strlen(self::HOME_DESCRIPTION).'): '.self::HOME_DESCRIPTION);

        $headingRows = $sections->map(function ($section) {
            $tag = $section['semantic_tag'] ?? (
                ($section['type'] ?? '') === 'heading' ? ($section['heading_level'] ?? 'div') : null
            );
            return [
                'Y' => round((float) ($section['position_y'] ?? 0), 1),
                'Typ' => $section['type'] ?? '-',
                'Tag' => $tag ?? '-',
                'Treść' => mb_substr(preg_replace('/\s+/u', ' ', trim((string) ($section['content'] ?? ''))), 0, 115),
            ];
        })->filter(fn ($row) => in_array($row['Tag'], ['h1', 'h2', 'h3'], true))
            ->sortBy('Y')->values();
        $this->newLine();
        $this->info('NAGŁÓWKI — '.count($headingRows).' z buildera');
        $this->table(['Y', 'Typ', 'Tag', 'Treść'], $headingRows->all());
        $h1 = $headingRows->where('Tag', 'h1')->count();
        if ($h1 !== 1) $this->warn('H1: '.$h1.'. Rekomendowany dokładnie jeden główny nagłówek opisujący usługę.');

        $texts = $sections->filter(fn ($row) => in_array(($row['type'] ?? ''), ['heading','text','section'], true))
            ->pluck('content')->filter()->implode("\n");
        foreach ($sections->filter(fn ($row) => ($row['type'] ?? '') === 'process_steps') as $process) {
            $texts .= "\n".($process['process_title'] ?? '').' '.($process['process_intro'] ?? '');
            foreach (($process['process_steps'] ?? []) as $step) {
                $texts .= ' '.($step['title'] ?? '').' '.($step['description'] ?? '');
            }
        }
        $normalized = mb_strtolower($texts);
        $keywords = [
            'stylistka żywności', 'food stylistka', 'foodstylistka', 'food stylist',
            'foodstylist', 'food styling', 'stylizacja żywności', 'stylizacja potraw',
            'stylizacja jedzenia', 'fotografia żywności', 'fotografia kulinarna',
            'fotografia reklamowa',
        ];
        $kwRows = [];
        foreach ($keywords as $phrase) {
            $kwRows[] = ['Fraza' => $phrase, 'W treści strony głównej' => mb_substr_count($normalized, $phrase)];
        }
        $this->info('FRAZY NA WIDOCZNEJ STRONIE GŁÓWNEJ (nie tylko w meta)');
        $this->table(['Fraza', 'W treści strony głównej'], $kwRows);

        $imageRows = [];
        foreach ($sections->filter(fn ($row) => ($row['type'] ?? '') === 'image') as $image) {
            $photo = isset($image['photo_id']) ? Photo::find($image['photo_id']) : null;
            $alt = trim((string) ($photo?->alt ?: $photo?->title ?: ($image['photo_title'] ?? '')));
            $imageRows[] = [
                'ID elementu' => $image['id'] ?? '',
                'Photo ID' => $photo?->id ?? '-',
                'ALT na stronie' => mb_substr($alt, 0, 100),
                'Stan' => $alt === '' ? 'BRAK ALT' : ($photo && !trim((string) $photo->alt) ? 'tylko fallback' : 'OK'),
            ];
        }
        $this->newLine();
        $this->info('ZDJĘCIA WSTAWIONE BEZPOŚREDNIO W BUILDERZE — '.count($imageRows));
        $this->table(['ID elementu', 'Photo ID', 'ALT na stronie', 'Stan'], $imageRows);
        $this->warn('Nie zmieniam ALT automatycznie. Opis musi wynikać z zawartości konkretnego zdjęcia, nie tylko z fraz SEO.');

        $this->newLine();
        $this->info('POTENCJALNIE KONKURENCYJNE PODSTRONY / GALERIE — ich tytuły SEO');
        $related = collect();
        foreach (Page::query()->where('published', true)->get() as $page) {
            if (preg_match('/food.?styl|stylist|stylizac|żywnoś|jedzeni/iu', ($page->seo_title ?? '').' '.($page->title ?? ''))) {
                $related->push(['Strona', $page->slug, Seo::meta($page)['title']]);
            }
        }
        foreach (Gallery::query()->where('published', true)->get() as $gallery) {
            if (preg_match('/food.?styl|stylist|stylizac|żywnoś|jedzeni/iu', ($gallery->seo_title ?? '').' '.($gallery->title ?? ''))) {
                $related->push(['Galeria', $gallery->slug, Seo::meta($gallery)['title']]);
            }
        }
        $this->table(['Typ', 'Slug', 'TITLE'], $related->take(30)->all());
        $this->line('Podstrony zachowują własne adresy i canonical. Nie ustawiam noindex ani przekierowań do strony głównej.');

        if (!$this->option('apply')) {
            $this->newLine();
            $this->comment('TYLKO ODCZYT. Nic nie zostało zmienione.');
            $this->comment('Aby zapisać wyłącznie nowe meta strony głównej: php artisan foodfoto:home-seo --apply');
            return self::SUCCESS;
        }

        $updates = [
            'home_seo_title' => self::HOME_TITLE,
            'home_seo_description' => self::HOME_DESCRIPTION,
        ];
        $overlap = null;
        if ($this->option('fix-overlap')) {
            $page = Page::query()->where('slug', 'food-styling')->first();
            if ($page && $page->seo_title === 'Foodstylista – food styling i stylizacja żywności | Magda Gugała') {
                $overlap = $page;
            }
        }

        $dir = storage_path('app/backups');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->error('Nie udało się utworzyć katalogu kopii zapasowej. Brak zmian.');
            return self::FAILURE;
        }
        $backupPath = $dir.'/home-seo-before-'.date('Ymd-His').'.json';
        $backupData = [
            'site_settings' => $settings,
            'page_food_styling' => $overlap?->only(['id','slug','seo_title','seo_description']),
            'home_builder_id' => $builder->id,
            'home_builder_content' => $builder->content,
        ];
        if (!file_put_contents($backupPath, json_encode($backupData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
            $this->error('Nie udało się zapisać kopii zapasowej. Brak zmian.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($updates, $overlap) {
            foreach ($updates as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            if ($overlap) {
                $overlap->seo_title = 'Stylizacja potraw do sesji reklamowych i zdjęć | Magda Gugała';
                $overlap->seo_description = 'Przygotowanie i aranżacja potraw do reklam, sesji kulinarnych i fotografii produktów. Zobacz, jak wygląda współpraca przy stylizacji planu zdjęciowego.';
                $overlap->save();
            }
        });
        $this->info('ZAPISANO nowe TITLE i DESCRIPTION strony głównej. Układ, teksty, zdjęcia i ALT bez zmian.');
        if ($overlap) $this->info('Rozróżniono również SEO podstrony food-styling, bez usuwania jej z indeksu.');
        $this->line('Kopia zapasowa: '.$backupPath);
        return self::SUCCESS;
    }
}
