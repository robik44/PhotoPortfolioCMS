<?php

namespace App\Console\Commands;

use App\Models\PageBuilder;
use App\Models\Photo;
use Illuminate\Console\Command;

class FoodfotoHomeSeoStructure extends Command
{
    protected $signature = 'foodfoto:home-seo-structure {--apply : Zapisz dwie bezpieczne poprawki strony głównej po utworzeniu kopii zapasowej}';

    protected $description = 'Sprawdź hierarchię H2, literówkę akapitu oraz zdjęcia bez ALT; bez zmian domyślnie.';

    public function handle(): int
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')
            ->where('published', true)->first();
        if (!$builder) {
            $this->error('Brak opublikowanego buildera strony głównej. Niczego nie zmieniono.');
            return self::FAILURE;
        }

        $content = $builder->content ?? [];
        $sections = $content['sections'] ?? [];
        if (!is_array($sections)) {
            $this->error('Nieprawidłowa struktura sekcji. Niczego nie zmieniono.');
            return self::FAILURE;
        }

        $changes = [];
        foreach ($sections as $index => &$element) {
            if (!is_array($element)) continue;

            // A visual headline is already present before the six service cards.
            // Setting its semantic HTML to H2 preserves its text block and styling.
            if (($element['id'] ?? '') === 'fve-1792017662127-5bnp9p4slmk') {
                if (in_array(($element['type'] ?? ''), ['text', 'heading'], true)
                    && trim((string) ($element['content'] ?? '')) === 'Piękne obrazy dla smacznych historii'
                    && !in_array(($element['semantic_tag'] ?? 'p'), ['h1','h2','h3'], true)) {
                    $element['semantic_tag'] = 'h2';
                    $changes[] = 'HTML H2 dla „Piękne obrazy dla smacznych historii”';
                } else {
                    $this->warn('Nie zmieniam H2: oryginalna treść lub typ elementu różni się od audytu.');
                }
            }

            // The editor's actual paragraph has a missing initial "T".
            if (($element['id'] ?? '') === 'fve-1792068921133-k11vzhegm7d') {
                $copy = (string) ($element['content'] ?? '');
                if (preg_match('/^\s*worzę obrazy,/u', $copy)) {
                    $element['content'] = preg_replace('/^(\s*)worzę obrazy,/u', '$1Tworzę obrazy,', $copy, 1);
                    $changes[] = 'Literówka: „Tworzę obrazy…”';
                } else {
                    $this->warn('Nie zmieniam akapitu: jego początek jest inny niż w audycie.');
                }
            }
        }
        unset($element);

        $this->info('SEO STRONY GŁÓWNEJ — STRUKTURA I BRAKUJĄCE ALT');
        foreach ($changes as $change) $this->line('Do poprawienia: '.$change);
        if (!$changes) $this->info('Brak zmian do wykonania.');

        $this->newLine();
        $this->info('ZDJĘCIA DO OBEJRZENIA — nie uzupełniam ALT bez rozpoznania fotografii');
        foreach ([112, 117, 113] as $photoId) {
            $photo = Photo::find($photoId);
            if (!$photo) {
                $this->warn('Photo ID '.$photoId.': nie istnieje w bibliotece.');
                continue;
            }
            $this->line('Photo ID '.$photoId.': '.$photo->imageUrl());
            $this->line('  ALT teraz: '.(trim((string) $photo->alt) ?: '[brak]'));
            $this->line('  Tytuł: '.(trim((string) $photo->title) ?: '[brak]'));
        }
        $this->line('Element z pustym photo_id nie jest obrazem; nie przypisujemy mu sztucznego ALT.');

        if (!$this->option('apply')) {
            $this->comment('TYLKO ODCZYT — strona nie została zmieniona.');
            return self::SUCCESS;
        }
        if (!$changes) {
            $this->info('Brak zmian w bazie.');
            return self::SUCCESS;
        }

        $dir = storage_path('app/backups');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->error('Nie udało się utworzyć folderu kopii zapasowej. Niczego nie zmieniono.');
            return self::FAILURE;
        }
        $backup = $dir.'/home-seo-structure-before-'.date('Ymd-His').'.json';
        $oldContent = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$oldContent || file_put_contents($backup, $oldContent, LOCK_EX) === false) {
            $this->error('Nie udało się zapisać kopii zapasowej. Niczego nie zmieniono.');
            return self::FAILURE;
        }

        $content['sections'] = $sections;
        $builder->content = $content;
        $builder->save();
        $this->info('Poprawiono: '.implode(' oraz ', $changes));
        $this->line('Kopia zapasowa: '.$backup);
        $this->line('Nie zmieniono zdjęć, ALT, położenia, typografii, adresów URL ani meta.');
        return self::SUCCESS;
    }
}
