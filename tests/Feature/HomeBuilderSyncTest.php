<?php

namespace Tests\Feature;

use App\Models\PageBuilder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeBuilderSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);

        DB::purge('sqlite');
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_home_builder_typography_is_used_on_public_homepage(): void
    {
        PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => [
                'version' => 1,
                'settings' => [],
                'sections' => [
                    [
                        'id' => 'hero-heading',
                        'type' => 'heading',
                        'content' => 'Fotografia i stylizacja żywności',
                        'element_width' => 24,
                        'element_height' => 150,
                        'position_x' => 7,
                        'style' => [
                            'font_size' => 40,
                            'font_weight' => 400,
                            'color' => '#000000',
                            'text_align' => 'left',
                            'line_height' => 1.2,
                            'letter_spacing' => 1,
                        ],
                    ],
                    [
                        'id' => 'hero-text',
                        'type' => 'text',
                        'content' => 'Fotografia kulinarna i artystyczna',
                        'style' => [
                            'font_size' => 22,
                            'font_weight' => 500,
                            'color' => '#123456',
                            'text_align' => 'left',
                            'line_height' => 1.4,
                            'letter_spacing' => 0,
                        ],
                    ],
                    [
                        'id' => 'portfolio-heading',
                        'type' => 'heading',
                        'content' => 'Moje portfolio',
                        'style' => [
                            'font_size' => 37,
                            'font_weight' => 600,
                            'color' => '#654321',
                            'text_align' => 'left',
                            'line_height' => 1.2,
                            'letter_spacing' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('Fotografia i stylizacja żywności')
            ->assertSee('Fotografia kulinarna i artystyczna')
            ->assertSee('Moje portfolio')
            ->assertSee('font-size:40px;font-weight:400;color:#000000;', false)
            ->assertSee('width:24%;max-width:none;margin-left:7%;box-sizing:border-box;min-height:150px;white-space:normal;overflow-wrap:anywhere;', false)
            ->assertSee('font-size:22px;font-weight:500;color:#123456;', false)
            ->assertSee('font-size:37px;font-weight:600;color:#654321;', false);
    }

    public function test_opening_home_builder_exposes_missing_editable_homepage_texts(): void
    {
        $builder = PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => [
                'version' => 1,
                'settings' => [],
                'sections' => [[
                    'type' => 'heading',
                    'content' => 'Istniejący nagłówek',
                    'style' => ['color' => '#000000'],
                ]],
            ],
        ]);

        $this->get(route('home-builder.edit'))->assertOk()
            ->assertSee('applyHomepagePreviewLayout')
            ->assertSee('hero-heading')
            ->assertSee('hero-text')
            ->assertSee('portfolio-heading');

        $sections = collect($builder->fresh()->content['sections']);

        $this->assertSame('Istniejący nagłówek', $sections->firstWhere('id', 'hero-heading')['content']);
        $this->assertSame('Fotografia kulinarna i artystyczna', $sections->firstWhere('id', 'hero-text')['content']);
        $this->assertSame('Portfolio', $sections->firstWhere('id', 'portfolio-heading')['content']);
    }
}
