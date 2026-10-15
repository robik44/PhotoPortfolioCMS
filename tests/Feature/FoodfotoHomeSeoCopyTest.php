<?php

namespace Tests\Feature;

use App\Models\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodfotoHomeSeoCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_update_is_opt_in_and_does_not_touch_image_or_layout(): void
    {
        $builder = PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => [
                'settings' => ['background_color' => '#eef'],
                'sections' => [
                    [
                        'id' => 'services-lead', 'type' => 'text',
                        'content' => 'Specjalizuję się w fotografii jedzenia, food stylingu i tworzeniu dopracowanych kadrów dla branży spożywczej.',
                        'semantic_tag' => 'p', 'position_x' => 9.7, 'position_y' => 59.1,
                        'element_width' => 51.5, 'style' => ['font_size' => 16.2],
                    ],
                    [
                        'id' => 'service-packaging-text', 'type' => 'text',
                        'content' => 'Fotografia potraw i produktów przygotowana z myślą o opakowaniach, etykietach i projektach marki.',
                        'position_x' => 55, 'position_y' => 133.6,
                    ],
                    [
                        'id' => 'hero-image', 'type' => 'image', 'photo_id' => 112,
                        'position_x' => 0, 'position_y' => 10,
                    ],
                ],
            ],
        ]);
        $before = $builder->content;

        $this->artisan('foodfoto:home-seo-copy')->assertSuccessful();
        $this->assertSame($before, $builder->fresh()->content);

        $this->artisan('foodfoto:home-seo-copy --apply')->assertSuccessful();
        $after = $builder->fresh()->content;
        $this->assertStringContainsString('fotografii żywności i jedzenia', $after['sections'][0]['content']);
        $this->assertStringContainsString('Fotografia na opakowania', $after['sections'][1]['content']);
        foreach (['style', 'position_x', 'position_y', 'semantic_tag', 'element_width'] as $key) {
            $this->assertSame($before['sections'][0][$key], $after['sections'][0][$key]);
        }
        $this->assertSame($before['sections'][2], $after['sections'][2]);
        $this->assertSame($before['settings'], $after['settings']);

        $this->artisan('foodfoto:home-seo-copy --apply')->assertSuccessful();
        $this->assertSame($after, $builder->fresh()->content);
    }

    public function test_newer_manual_content_is_not_overwritten(): void
    {
        $builder = PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['sections' => [[
                'id' => 'services-lead', 'type' => 'text',
                'content' => 'Autorski tekst pozostaje bez zmian.',
            ]]],
        ]);

        $before = $builder->content;
        $this->artisan('foodfoto:home-seo-copy --apply')->assertSuccessful();
        $this->assertSame($before, $builder->fresh()->content);
    }
}
