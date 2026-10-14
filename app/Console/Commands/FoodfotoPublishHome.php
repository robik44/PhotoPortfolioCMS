<?php

namespace App\Console\Commands;

use App\Models\PageBuilder;
use App\Models\SiteSetting;
use Illuminate\Console\Command;

class FoodfotoPublishHome extends Command
{
    protected $signature = 'foodfoto:publish-home {--publish : Publish editorial homepage} {--rollback : Restore existing homepage} {--confirm-production : Confirm public-facing switch}';
    protected $description = 'Safely switch the public homepage between the existing CMS and editorial FoodFoto design.';

    public function handle(): int
    {
        $publish = (bool) $this->option('publish');
        $rollback = (bool) $this->option('rollback');
        if ($publish && $rollback) {
            $this->error('Choose exactly one of --publish or --rollback.');
            return self::FAILURE;
        }
        if (!$publish && !$rollback) {
            $current = SiteSetting::where('key', 'foodfoto_new_home_published')->value('value') === '1';
            $this->info($current ? 'Homepage: NEW editorial FoodFoto' : 'Homepage: EXISTING CMS design');
            return self::SUCCESS;
        }
        if (!$this->option('confirm-production')) {
            $this->error('This affects the public website. Add --confirm-production to proceed.');
            return self::FAILURE;
        }
        if ($publish) {
            $builder = PageBuilder::whereNull('page_id')->where('type', 'home')->where('published', true)->first();
            $sections = array_values($builder?->content['sections'] ?? []);
            if (count($sections) !== 32
                || ($sections[3]['content'] ?? null) !== 'Co tworzę'
                || ($sections[24]['type'] ?? null) !== 'gallery'
                || ($sections[30]['type'] ?? null) !== 'thumbnail_gallery'
                || (int) ($sections[31]['photo_id'] ?? 0) !== 96) {
                $this->error('Publication blocked: live builder no longer matches approved layout.');
                return self::FAILURE;
            }
        }
        SiteSetting::updateOrCreate(
            ['key' => 'foodfoto_new_home_published'],
            ['value' => $publish ? '1' : '0']
        );
        $this->info($publish ? 'New homepage enabled. Use --rollback to revert instantly.' : 'Original homepage restored.');
        return self::SUCCESS;
    }
}
