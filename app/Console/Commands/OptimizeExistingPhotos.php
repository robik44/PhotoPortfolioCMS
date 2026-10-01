<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Services\PhotoVariantService;
use Illuminate\Console\Command;
use Throwable;

class OptimizeExistingPhotos extends Command
{
    protected $signature = 'photos:optimize-existing
        {--force : Regenerate variants even when both files already exist}
        {--limit= : Process only the first N photos}';

    protected $description = 'Generate optimized WebP and thumbnail variants for existing library photos';

    public function handle(PhotoVariantService $variants): int
    {
        $limit = $this->option('limit');

        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('--limit must be a positive integer.');
            return self::INVALID;
        }

        $query = Photo::query()->orderBy('id');
        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $total = (clone $query)->count();
        $optimized = 0;
        $skipped = 0;
        $failed = 0;

        if ($total === 0) {
            $this->info('Brak zdjęć do przetworzenia.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->each(function (Photo $photo) use ($variants, &$optimized, &$skipped, &$failed, $bar) {
            try {
                if ($variants->optimize($photo, (bool) $this->option('force'))) {
                    $optimized++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $this->newLine();
                $this->error("Nie udało się zoptymalizować zdjęcia #{$photo->id}: {$photo->filename}");
            } finally {
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Zoptymalizowano: {$optimized}; pominięto: {$skipped}; błędy: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
