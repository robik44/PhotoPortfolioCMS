<?php

namespace Tests\Feature;

use App\Models\{Gallery, Page, Photo, SiteSetting, User};
use App\Services\SiteFontLibrary;
use App\Support\{GalleryTypography, TypographySettings};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class BuilderButtonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Always use a fresh disposable SQLite database so tests do not depend on
        // development data committed in database/database.sqlite.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Storage::fake('public');
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_button_links_appearance_and_legacy_defaults(): void
    {
        $page = Page::create(['title' => 'Buttons', 'slug' => 'test-buttons', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Button target', 'slug' => 'test-button-target', 'published' => true]);
        $button = ['type' => 'button', 'content' => 'Stary przycisk', 'position_x' => 12, 'position_y' => 20, 'element_width' => 40,
            'style' => ['font_family' => 'Georgia', 'font_size' => 24, 'font_weight' => 700, 'color' => '#123456']];
        $save = route('pages.builder.save', $page);
        $public = route('page.public', $page);
        $this->postJson($save, ['content' => ['sections' => [$button]]])->assertOk();
        $legacy = $this->get($public)->assertOk();
        $legacy->assertSee('padding-top:13px;', false)->assertSee('padding-left:24px;', false)
            ->assertSee('background:rgba(34,34,34,1) !important;', false)->assertSee('border-radius:4px;', false);
        $legacy->assertDontSee('<a href="#" style="color:#123456;', false);
        $button['content'] = 'Nowy przycisk';
        $button += ['button_background' => '#abcdef', 'button_border_color' => '#654321', 'button_border_width' => 3,
            'button_radius' => 9, 'button_padding_y' => 15, 'button_padding_x' => 31, 'button_background_opacity' => 45];
        foreach (['/o-mnie', route('portfolio.gallery', $gallery), 'https://example.com/path?a=1&b=2', 'mailto:test@example.com', 'tel:+48123456789'] as $url) {
            $button['button_link'] = $url;
            $button['button_new_tab'] = true;
            $layout = ['sections' => [$button]];
            $this->postJson($save, ['content' => $layout])->assertOk();
            $this->assertSame($layout, $page->fresh()->builder->content);
            $editor = $this->get(route('pages.builder', $page))->assertOk()->assertSee('Galeria: Button target');
            $this->assertSame($layout, $editor->viewData('builder')->content);
            $response = $this->get($public)->assertOk()->assertSee('Nowy przycisk')
                ->assertSee('href="'.e($url).'"', false)->assertSee('target="_blank" rel="noopener noreferrer"', false)
                ->assertSee('font-family:Georgia, serif;', false)->assertSee('font-size:24px;', false)->assertSee('font-weight:700;', false)
                ->assertSee('color:#123456;', false)->assertSee('background:rgba(171,205,239,0.45) !important;', false)
                ->assertSee('border-color:#654321;', false)->assertSee('border-width:3px;', false)
                ->assertSee('border-radius:9px;', false)->assertSee('padding-top:15px;', false)->assertSee('padding-left:31px;', false);
        }
        $button['button_new_tab'] = false;
        $this->postJson($save, ['content' => ['sections' => [$button]]])->assertOk();
        $this->get($public)->assertDontSee('target="_blank"', false);
        $button['button_link'] = '';
        $this->postJson($save, ['content' => ['sections' => [$button]]])->assertOk();
        $this->get($public)->assertOk()->assertDontSee('href=""', false);
        foreach (['javascript:alert(1)', 'data:text/html,bad', '//example.com', '/\\example.com'] as $url) {
            $button['button_link'] = $url;
            $this->postJson($save, ['content' => ['sections' => [$button]]])->assertStatus(422);
        }
    }

    public function test_gallery_back_link_saves_and_keeps_automatic_destination(): void
    {
        $gallery = Gallery::create(['title' => 'Back test', 'slug' => 'test-back-link', 'published' => true]);
        $public = route('portfolio.gallery', $gallery);
        $baseline = $this->get($public)->assertOk()->assertSee('← Powrót do galerii')->getContent();
        $data = ['title' => $gallery->title, 'back_text' => 'Wróć do portfolio', 'back_font_family' => 'Georgia', 'back_font_size' => 20, 'back_color' => '#654321'];
        $this->put(route('galleries.update', $gallery), $data)->assertSessionHasNoErrors();
        $form = $this->get(route('galleries.edit', $gallery))->assertOk()->assertSee('Przycisk / link Powrót do galerii');
        foreach (array_diff_key($data, ['title' => true]) as $key => $value) $this->assertSame($value, $form->viewData('backLink')[$key]);
        $this->get($public)->assertOk()->assertSee('← Wróć do portfolio')
            ->assertSee('href="'.url('/').'#portfolio"', false)
            ->assertSee('font-family:Georgia, serif;font-size:20px;color:#654321;', false);
        $this->put(route('galleries.update', $gallery), ['title' => $gallery->title, 'back_text' => '', 'back_font_family' => '', 'back_font_size' => '', 'back_color' => ''])->assertSessionHasNoErrors();
        $this->assertSame($baseline, $this->get($public)->assertOk()->getContent());
    }
    public function test_uploaded_central_font_is_available_for_button_and_back_link(): void
    {
        $before = app(SiteFontLibrary::class)->catalog()['fonts'];
        $this->post(route('fonts.store'), ['font_file' => UploadedFile::fake()->createWithContent('Button font.ttf', file_get_contents(__DIR__.'/../Fixtures/header-test.ttf'))])->assertSessionHasNoErrors();
        $id = array_key_first(array_diff_key(app(SiteFontLibrary::class)->catalog()['fonts'], $before));
        $page = Page::create(['title' => 'Font button', 'slug' => 'font-button', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Font back', 'slug' => 'font-back', 'published' => true]);
        $this->get(route('pages.builder', $page))->assertOk()->assertSee($id);
        $this->get(route('galleries.edit', $gallery))->assertOk()->assertSee($id)->assertSee('name="back_font_family"', false);
        $this->postJson(route('pages.builder.save', $page), ['content' => ['sections' => [['type' => 'button', 'content' => 'Własny font', 'style' => ['font_family' => $id]]]]])->assertOk();
        $this->get(route('page.public', $page))->assertOk()->assertSee('font-family:'.$id, false)->assertSee('storage/fonts/'.$id.'.ttf', false);
        $this->put(route('galleries.update', $gallery), ['title' => $gallery->title, 'back_font_family' => $id])->assertSessionHasNoErrors();
        $this->get(route('portfolio.gallery', $gallery))->assertOk()->assertSee('font-family:'.$id, false)->assertSee('storage/fonts/'.$id.'.ttf', false);
    }




    public function test_builder_and_public_page_use_the_same_non_overlapping_vertical_flow(): void
    {
        $builderView = file_get_contents(resource_path('views/admin/pages/builder.blade.php'));
        $publicView = file_get_contents(resource_path('views/pages/show.blade.php'));

        $this->assertStringContainsString('position: relative;', $builderView);
        $this->assertStringContainsString('margin-bottom: 36px;', $builderView);
        $this->assertStringContainsString("sort((a, b) => (Number(a.position_y) || 0) - (Number(b.position_y) || 0))", $builderView);
        $this->assertStringContainsString('position: relative;', $publicView);
        $this->assertStringContainsString('margin-bottom: 36px;', $publicView);
        $this->assertStringContainsString("sortBy(fn (\$element) => (float) (\$element['position_y'] ?? 0))", $publicView);
        $this->assertStringNotContainsString('top:{{ ($y / 100) * $designHeight }}px;', $publicView);
    }

    public function test_every_new_page_builder_exposes_shared_button_controls(): void
    {
        $page = Page::create(['title' => 'Future page', 'slug' => 'future-page', 'published' => true]);

        $editor = $this->get(route('pages.builder', $page))->assertOk();
        $editor->assertSee('+ Przycisk')
            ->assertSee('builder-button.js');

        $button = ['type' => 'button', 'content' => 'Future button', 'button_background' => '#336699',
            'button_background_opacity' => 25, 'style' => ['color' => '#ffffff']];
        $this->postJson(route('pages.builder.save', $page), ['content' => ['sections' => [$button]]])->assertOk();
        $this->get(route('page.public', $page))->assertOk()
            ->assertSee('background:rgba(51,102,153,0.25) !important;', false);
    }

    public function test_homepage_renders_builder_button_background_and_opacity(): void
    {
        \App\Models\PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => [
                'version' => 1,
                'settings' => [],
                'sections' => [[
                    'type' => 'button',
                    'content' => 'Portfolio',
                    'button_link' => '/portfolio',
                    'button_background' => '#123456',
                    'button_background_opacity' => 40,
                    'style' => ['color' => '#ffffff'],
                ]],
            ],
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Portfolio')
            ->assertSee('background:rgba(18,52,86,0.4) !important;', false)
            ->assertDontSee('.button:hover {\n            background: #fff;', false);
    }

    public function test_home_builder_save_persists_zero_and_partial_button_opacity(): void
    {
        $payload = [
            'version' => 1,
            'settings' => [],
            'sections' => [[
                'type' => 'button',
                'content' => 'Opacity',
                'button_background' => '#336699',
                'button_background_opacity' => 0,
                'style' => ['color' => '#ffffff'],
            ]],
        ];

        $this->postJson(route('home-builder.save'), ['content' => $payload])->assertOk();
        $this->assertSame(0, \App\Models\PageBuilder::whereNull('page_id')->where('type', 'home')->firstOrFail()->content['sections'][0]['button_background_opacity']);
        $this->get('/')->assertOk()->assertSee('background:rgba(51,102,153,0) !important;', false);

        $payload['sections'][0]['button_background_opacity'] = 35;
        $this->postJson(route('home-builder.save'), ['content' => $payload])->assertOk();
        $this->get('/')->assertOk()->assertSee('background:rgba(51,102,153,0.35) !important;', false);
    }

    public function test_public_views_use_consistent_mobile_breakpoints(): void
    {
        $home = file_get_contents(resource_path('views/welcome.blade.php'));
        $page = file_get_contents(resource_path('views/pages/show.blade.php'));

        $this->assertStringContainsString('desktop 4, tablet 2, phone 1', $home);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $home);
        $this->assertStringContainsString('@media (max-width: 520px)', $home);
        $this->assertStringContainsString('grid-template-columns: 1fr;', $home);
        $this->assertStringNotContainsString('@media (max-width: 1400px)', $home);
        $this->assertStringContainsString('margin-left: 0 !important;', $page);
        $this->assertStringContainsString('max-width: 100%;', $page);
    }


}
