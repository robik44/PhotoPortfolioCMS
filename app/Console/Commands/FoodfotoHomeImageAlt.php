<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FoodfotoHomeImageAlt extends Command
{
    protected $signature = 'foodfoto:home-image-alts
        {--apply : Zapisz ALT trzech rozpoznanych zdjęć po utworzeniu kopii zapasowej}';

    protected $description = 'Uzupełnij ALT zdjęć 112, 117 i 113 po sprawdzeniu fotografii, bez zmian innych pól CMS.';

    /**
     * Photo IDs, filenames and image content have all been verified against
     * the production audit and the three images supplied by the site editor.
     */
    private const PHOTOS = [
        112 => [
            'file' => 'eDKV5eavwa8h830JcyzIaH2eekFta7hScTfofiix',
            'alt' => 'Pieczone marchewki z ziołami i kremowym dodatkiem na ceramicznym talerzu – fotografia kulinarna',
        ],
        117 => [
            'file' => 'C1GXfqAvAklI81MFyYRdlssNFhB74iBF01HNLYvV',
            'alt' => 'Deser z malinami, puszystym kremem i pestkami granatu – fotografia jedzenia',
        ],
        113 => [
            'file' => 'BVHDZh5IWgODm79Lc4dSqU7E5gHlvPwm0TuPaI0p',
            'alt' => 'Świeże zielone liście bazylii na jasnym tle – fotografia żywności',
        ],
    ];

    public function handle(): int
    {
        $this->info('FOODFOTO — opisy ALT trzech obejrzanych zdjęć strony głównej');

        $backupRows = [];
        $pending = [];
        // Validate all three IDs and files BEFORE any modification.
        foreach (self::PHOTOS as $id => $target) {
            $photo = Photo::find($id);
            if (!$photo) {
                $this->error('Nie znaleziono zdjęcia ID '.$id.'. Nie zapisano żadnych zmian.');
                return self::FAILURE;
            }
            $webpOrFilename = (string) ($photo->webp ?: $photo->filename);
            if (!str_contains($webpOrFilename, $target['file'])) {
                $this->error('Niezgodny plik zdjęcia ID '.$id.'. Nie zgaduję ALT i niczego nie zmieniam.');
                return self::FAILURE;
            }

            $currentAlt = trim((string) ($photo->alt ?? ''));
            $backupRows[] = [
                'id' => $id,
                'filename' => $photo->filename,
                'webp' => $photo->webp,
                'title' => $photo->title,
                'alt_before' => $photo->alt,
                'alt_proposed' => $target['alt'],
            ];
            $this->line('ID '.$id.': '.$target['alt']);
            if ($currentAlt === '') {
                $pending[$id] = $target;
            } elseif ($currentAlt === $target['alt']) {
                $this->info('  Już zapisano poprawny ALT. Pomijam.');
            } else {
                $this->warn('  Zdjęcie ma już własny ALT: '.$currentAlt.' — nie nadpisuję go.');
            }
        }

        if (!$this->option('apply')) {
            $this->comment('Podgląd bez zapisu. Aby zastosować: php artisan foodfoto:home-image-alts --apply');
            return self::SUCCESS;
        }
        if (!$pending) {
            $this->info('Wszystkie trzy zdjęcia mają już ALT. Nic nie zmieniono.');
            return self::SUCCESS;
        }

        $dir = storage_path('app/backups');
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->error('Brak możliwości utworzenia katalogu kopii zapasowej. Nic nie zmieniono.');
            return self::FAILURE;
        }
        $backup = $dir.'/home-image-alts-before-'.date('Ymd-His').'-'.substr(uniqid(), -6).'.json';
        $json = json_encode($backupRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($backup, $json, LOCK_EX) === false) {
            $this->error('Nie udało się zapisać kopii zapasowej. Nic nie zmieniono.');
            return self::FAILURE;
        }

        $saved = 0;
        try {
            DB::transaction(function () use ($pending, &$saved) {
                foreach ($pending as $id => $target) {
                    $photo = Photo::query()->lockForUpdate()->findOrFail($id);
                    $file = (string) ($photo->webp ?: $photo->filename);
                    if (!str_contains($file, $target['file'])) {
                        throw new \RuntimeException('Plik zdjęcia ID '.$id.' zmienił się podczas aktualizacji.');
                    }
                    // Never overwrite another editor's ALT.
                    if (trim((string) ($photo->alt ?? '')) !== '') continue;
                    $photo->alt = $target['alt'];
                    $photo->save();
                    $saved++;
                }
            });
        } catch (\Throwable $error) {
            $this->error('Błąd zapisu: '.$error->getMessage().'. Transakcja wycofana.');
            return self::FAILURE;
        }

        $this->info('Gotowe: zapisano '.$saved.' opisów ALT.');
        $this->line('Kopia zapasowa: '.$backup);
        $this->line('Nie zmieniono nazw, tytułów, plików zdjęć, tekstów, pozycji, nagłówków ani podstron.');
        return self::SUCCESS;
    }
}
