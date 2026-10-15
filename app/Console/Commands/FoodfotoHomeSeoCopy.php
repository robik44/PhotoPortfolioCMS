<?php

namespace App\Console\Commands;

use App\Models\PageBuilder;
use Illuminate\Console\Command;

class FoodfotoHomeSeoCopy extends Command
{
    protected $signature = 'foodfoto:home-seo-copy {--apply : Zapisz ostrożnie nowe opisy usług po utworzeniu kopii zapasowej}';

    protected $description = 'Naturalne frazy foto/food styling na stronie głównej, bez zmiany H1, meta, ALT, layoutu i podstron.';

    /**
     * ID and complete old text must BOTH match before any text is changed.
     * This is deliberately conservative for a visual production builder.
     */
    private const EDITS = [
        'services-lead' => [
            'from' => 'Specjalizuję się w fotografii jedzenia, food stylingu i tworzeniu dopracowanych kadrów dla branży spożywczej.',
            'to' => 'Specjalizuję się w fotografii żywności i jedzenia, food stylingu oraz zdjęciach reklamowych i na opakowania.',
        ],
        'service-kulinarna-text' => [
            'from' => 'Apetytne zdjęcia potraw, deserów i napojów do menu, kampanii i publikacji.',
            'to' => 'Fotografia jedzenia: zdjęcia potraw, deserów i napojów do menu i kampanii.',
        ],
        'service-produktowa-text' => [
            'from' => 'Profesjonalne zdjęcia produktów spożywczych, opakowań i materiałów marki.',
            'to' => 'Fotografia produktowa żywności, opakowań i materiałów reklamowych marek.',
        ],
        'service-foodstyling-text' => [
            'from' => 'Przygotowanie produktu, kompozycja planu i aranżacja potraw do fotografii.',
            'to' => 'Stylizacja potraw i jedzenia: przygotowanie produktu i aranżacja do zdjęć.',
        ],
        'service-packaging-text' => [
            'from' => 'Fotografia potraw i produktów przygotowana z myślą o opakowaniach, etykietach i projektach marki.',
            'to' => 'Fotografia na opakowania: zdjęcia potraw i produktów do etykiet, opakowań i projektów marki.',
        ],
    ];

    public function handle(): int
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')
            ->where('published', true)->first();

        if (!$builder) {
            $this->error('Brak opublikowanego buildera strony głównej. Nic nie zmieniono.');
            return self::FAILURE;
        }

        $original = $builder->content ?? [];
        $sections = $original['sections'] ?? null;
        if (!is_array($sections)) {
            $this->error('Brak prawidłowej listy sekcji. Nic nie zmieniono.');
            return self::FAILURE;
        }

        $edits = [];
        $skips = [];
        foreach ($sections as &$section) {
            if (!is_array($section)) continue;
            $id = $section['id'] ?? '';
            if (!isset(self::EDITS[$id])) continue;
            $entry = self::EDITS[$id];
            if (($section['content'] ?? null) === $entry['from']) {
                $section['content'] = $entry['to'];
                $edits[$id] = $entry;
            } elseif (($section['content'] ?? null) !== $entry['to']) {
                $skips[] = $id.' — ręcznie zmieniony tekst, pomijam';
            }
        }
        unset($section);

        $this->info('FOODFOTO — TEKSTY SEO NA STRONIE GŁÓWNEJ');
        $this->line('Frazy: fotografia żywności, fotografia jedzenia, kulinarna, produktowa, reklamowa, stylizacja potraw, zdjęcia na opakowania i food styling.');
        foreach ($edits as $id => $entry) {
            $this->line($id);
            $this->line('  BYŁO: '.$entry['from']);
            $this->line('  BĘDZIE: '.$entry['to']);
        }
        foreach ($skips as $skip) $this->warn($skip);

        if (!$this->option('apply')) {
            $this->comment('Podgląd. Nic nie zmieniono. Aby zapisać: php artisan foodfoto:home-seo-copy --apply');
            return self::SUCCESS;
        }
        if (!$edits) {
            $this->info('Nie ma tekstów do zmiany. Nic nie zapisano.');
            return self::SUCCESS;
        }

        $dir = storage_path('app/backups');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->error('Błąd katalogu kopii zapasowej. Nic nie zmieniono.');
            return self::FAILURE;
        }
        $backup = $dir.'/home-seo-copy-before-'.date('Ymd-His').'.json';
        $json = json_encode($original, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$json || file_put_contents($backup, $json, LOCK_EX) === false) {
            $this->error('Nie można zapisać kopii zapasowej. Nic nie zmieniono.');
            return self::FAILURE;
        }

        $original['sections'] = $sections;
        $builder->content = $original;
        $builder->save();

        $this->info('Zapisano '.count($edits).' tekstów. Bez zmian typografii, współrzędnych, zdjęć, ALT i podstron.');
        $this->line('Kopia zapasowa: '.$backup);
        return self::SUCCESS;
    }
}
