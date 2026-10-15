<?php

namespace Tests\Feature;

use App\Models\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodfotoHomeSeoStructureTest extends TestCase
{
    use RefreshDatabase;

    private function home(string $headline = 'Piękne obrazy dla smacznych historii'): PageBuilder
    {
        return PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['settings' => ['background_color' => '#fafafa'],
                'sections' => [
                    [
                        'id' => 'fve-1792017662127-5bnp9p4slmk',
                        'type' => 'text', 'content' => $headline,
                        'semantic_tag' => 'p', 'position_x' => 12,
                        'position_y' => 61, 'style' => ['font_size' => 43.2, 'color' => '#222222'],
                    ],
                    [
                        'id' => 'fve-1792068921133-k11vzhegm7d',
                        'type' => 'text',
                        'content' => 'worzę obrazy, które sprawiają, że jedzenie wygląda tak dobrze, jak smakuje.',
                        'position_x' => 15, 'position_y' => 318.6,
                    ],
                    [
                        'id' => 'hero-image', 'type' => 'image', 'photo_id' => 112,
                        'position_x' => 0, 'position_y' => 10, 'style' => ['opacity' => 100],
                    ],
                ],
            ],
        ]);
    }

    public function test_command_is_read_only_until_apply_and_preserves_layout(): void
    {
        $builder = $this->home();
        $before = $builder->content;

        $this->artisan('foodfoto:home-seo-structure')->assertSuccessful();
        $this->assertSame($before, $builder->fresh()->content);

        $this->artisan('foodfoto:home-seo-structure --apply')->assertSuccessful();
        $after = $builder->fresh()->content;
        $this->assertSame('h2', $after['sections'][0]['semantic_tag']);
        $this->assertSame($before['sections'][0]['style'], $after['sections'][0]['style']);
        $this->assertSame($before['sections'][0]['position_x'], $after['sections'][0]['position_x']);
        $this->assertSame($before['sections'][0]['position_y'], $after['sections'][0]['position_y']);
        $this->assertStringStartsWith('Tworzę obrazy', $after['sections'][1]['content']);
        $this->assertSame($before['sections'][2], $after['sections'][2]);
        $this->assertSame($before['settings'], $after['settings']);

        $this->artisan('foodfoto:home-seo-structure --apply')->assertSuccessful();
        $this->assertSame($after, $builder->fresh()->content);
    }

    public function test_command_does_not_overwrite_changed_headline_or_copy(): void
    {
        $builder = $this->home('Inny nagłówek napisany w CMS');
        $content = $builder->content;
        $content['sections'][1]['content'] = 'Nowszy tekst, poprawiony ręcznie.';
        $builder->content = $content;
        $builder->save();
        $before = $builder->content;

        $this->artisan('foodfoto:home-seo-structure --apply')->assertSuccessful();
        $this->assertSame($before, $builder->fresh()->content);
    }
}
