<?php

namespace App\Console\Commands;

use App\Models\PageBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FoodfotoHomeLayout extends Command
{
    protected $signature = 'foodfoto:home-layout
        {--apply : Save the planned layout after creating a backup}
        {--restore= : Restore from a previously generated backup filename}
        {--force : Allow replacement when the composition was already applied}';

    protected $description = 'Safely preview, apply or restore the FoodFoto homepage layout without changing SEO copy or gallery selections.';

    public function handle(): int
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')->first();

        if (!$builder) {
            $this->error('Home builder not found.');
            return self::FAILURE;
        }

        if ($this->option('restore')) {
            return $this->restore($builder, (string) $this->option('restore'));
        }

        $content = $builder->content ?? [];
        $sections = array_values($content['sections'] ?? []);

        $required = [
            0 => ['image', 45],
            3 => ['heading', 'Co tworzę'],
            23 => ['heading', 'Portfolio'],
            24 => ['gallery', null],
            30 => ['thumbnail_gallery', null],
            31 => ['image', 96],
        ];

        if (count($sections) !== 32) {
            $this->error('Expected 32 elements, found '.count($sections).'. No changes made.');
            return self::FAILURE;
        }

        foreach ($required as $index => [$type, $expected]) {
            $s = $sections[$index];
            if (($s['type'] ?? '') !== $type) {
                $this->error('Unexpected type at element '.($index + 1).'. No changes made.');
                return self::FAILURE;
            }
            if (is_int($expected) && (int) ($s['photo_id'] ?? 0) !== $expected) {
                $this->error('Unexpected photo at element '.($index + 1).'. No changes made.');
                return self::FAILURE;
            }
            if (is_string($expected) && trim($s['content'] ?? '') !== $expected) {
                $this->error('Unexpected heading at element '.($index + 1).'. No changes made.');
                return self::FAILURE;
            }
        }

        if (!empty($content['settings']['foodfoto_layout_version']) && !$this->option('force')) {
            $this->error('Layout already applied. Use --restore=BACKUP or inspect carefully before using --force.');
            return self::FAILURE;
        }

        // Keep original element ids, text, photo references, gallery settings and SEO metadata.
        // The first 23 existing elements remain entirely unchanged.
        $layout = [
            23 => [145, 1.1261731543159, 40],  // Existing Portfolio H2, visually hidden below.
            24 => [145, 0, 100],                 // Original gallery
            25 => [194, 0, 100],                 // Large editorial photo
            26 => [244, 8, 84],                  // Editorial body copy
            27 => [284, 65, 27],                 // Original three photos
            28 => [284, 36, 27],
            29 => [284, 7, 27],
            30 => [338, 1, 98],                  // Thumbnail gallery
            31 => [425, 0, 100],                 // Existing bottom banner photo 96
        ];

        foreach ($layout as $i => [$y, $x, $width]) {
            $sections[$i]['position_y'] = $y;
            $sections[$i]['position_x'] = $x;
            $sections[$i]['element_width'] = $width;
        }

        // Keeping this h2 as a hidden visual label avoids deleting existing text;
        // preserve accessible gallery heading and #portfolio anchor.
        $sections[23]['style']['color'] = '#222222';
        $sections[23]['element_width'] = 40;

        $content['sections'] = $sections;
        $content['settings']['foodfoto_layout_version'] = 'editorial-preserve-32-v1';

        $this->info('Validated 32 production homepage elements.');
        $this->line('Preserved all original photo IDs, six SEO service cards, gallery settings, copy and the contact banner (photo 96).');
        $this->line('Planned changes: positions of existing elements 24-32 only. No content replacement.');
        $this->line('This step does NOT insert the new editorial presets automatically.');
        $this->table(['Element', 'Old Y', 'New Y'], collect($layout)->map(
            fn ($position, $index) => [((int)$index) + 1, round((float)($builder->content['sections'][$index]['position_y'] ?? 0), 2), $position[0]]
        )->values()->all());

        if (!$this->option('apply')) {
            $this->comment('DRY RUN — database unchanged. Pass --apply to save with automatic backup.');
            return self::SUCCESS;
        }

        $directory = storage_path('app/foodfoto-layout-backups');
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            $this->error('Could not create backup directory. Aborting.');
            return self::FAILURE;
        }

        $name = 'home-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
        $backup = json_encode($builder->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if (file_put_contents($directory.'/'.$name, $backup, LOCK_EX) === false) {
            $this->error('Could not save backup. Aborting.');
            return self::FAILURE;
        }
        @chmod($directory.'/'.$name, 0600);

        $builder->content = $content;
        $builder->save();

        $this->info('Applied. Backup: '.$name);
        $this->warn('Reload the public homepage to review the result.');
        return self::SUCCESS;
    }

    private function restore(PageBuilder $builder, string $filename): int
    {
        if (!preg_match('/^home-\d{8}-\d{6}-[0-9a-f]{8}\.json$/', $filename)) {
            $this->error('Invalid backup filename.');
            return self::FAILURE;
        }

        $path = storage_path('app/foodfoto-layout-backups/'.$filename);
        if (!is_file($path)) {
            $this->error('Backup not found.');
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['sections']) || !is_array($data['sections'])) {
            $this->error('Invalid backup content.');
            return self::FAILURE;
        }

        $builder->content = $data;
        $builder->save();
        $this->info('Restored layout from '.$filename);
        return self::SUCCESS;
    }
}
