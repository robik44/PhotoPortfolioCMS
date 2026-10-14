<?php

namespace Tests\Feature;

use App\Models\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodfotoHomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function home(): PageBuilder
    {
        $sections = [];
        for ($i = 0; $i < 32; $i++) {
            $sections[] = [
                'id' => 'existing-'.$i,
                'type' => 'text',
                'content' => 'Original content '.$i,
                'position_x' => 5,
                'position_y' => $i * 10,
                'element_width' => 90,
                'style' => ['font_size' => 20],
            ];
        }
        $sections[0]['type'] = 'image';
        $sections[0]['photo_id'] = 45;
        $sections[3]['type'] = 'heading';
        $sections[3]['content'] = 'Co tworzę';
        $sections[23]['type'] = 'heading';
        $sections[23]['content'] = 'Portfolio';
        $sections[24]['type'] = 'gallery';
        $sections[24]['gallery_ids'] = [1, 2];
        $sections[30]['type'] = 'thumbnail_gallery';
        $sections[30]['photo_ids'] = [18, 77, 46];
        $sections[31]['type'] = 'image';
        $sections[31]['photo_id'] = 96;

        return PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => ['version' => 1, 'settings' => [], 'sections' => $sections],
        ]);
    }

    public function test_preview_does_not_change_homepage(): void
    {
        $builder = $this->home();
        $original = $builder->content;

        $this->artisan('foodfoto:home-layout')->assertSuccessful();

        $this->assertSame($original, $builder->fresh()->content);
    }

    public function test_apply_refuses_to_change_homepage_without_confirmation(): void
    {
        $builder = $this->home();
        $original = $builder->content;

        $this->artisan('foodfoto:home-layout', ['--apply' => true])->assertFailed();

        $this->assertSame($original, $builder->fresh()->content);
    }

    public function test_unexpected_layout_is_not_modified(): void
    {
        $builder = $this->home();
        $data = $builder->content;
        $data['sections'][31]['photo_id'] = 999;
        $builder->content = $data;
        $builder->save();

        $this->artisan('foodfoto:home-layout', [
            '--apply' => true,
            '--confirm-production' => true,
        ])->assertFailed();

        $this->assertSame($data, $builder->fresh()->content);
    }

    public function test_apply_creates_backup_and_restore_recovers_original(): void
    {
        $builder = $this->home();
        $original = $builder->content;
        $directory = storage_path('app/foodfoto-layout-backups');
        $before = glob($directory.'/home-*.json') ?: [];

        try {
            $this->artisan('foodfoto:home-layout', [
                '--apply' => true,
                '--confirm-production' => true,
            ])->assertSuccessful();

            $after = glob($directory.'/home-*.json') ?: [];
            $created = array_values(array_diff($after, $before));
            $this->assertCount(1, $created);
            $updated = $builder->fresh()->content;

            $this->assertSame(32, count($updated['sections']));
            $this->assertSame('editorial-preserve-32-v1', $updated['settings']['foodfoto_layout_version']);
            $this->assertSame($original['sections'][0], $updated['sections'][0]);
            $this->assertSame($original['sections'][31]['photo_id'], $updated['sections'][31]['photo_id']);
            $this->assertSame($original['sections'][24]['gallery_ids'], $updated['sections'][24]['gallery_ids']);
            $this->assertSame($original['sections'][30]['photo_ids'], $updated['sections'][30]['photo_ids']);

            $this->artisan('foodfoto:home-layout', [
                '--restore' => basename($created[0]),
                '--confirm-production' => true,
            ])->assertSuccessful();
            $this->assertSame($original, $builder->fresh()->content);
        } finally {
            foreach (array_values(array_diff(glob($directory.'/home-*.json') ?: [], $before)) as $file) {
                @unlink($file);
            }
        }
    }
}
