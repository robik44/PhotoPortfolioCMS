<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteFontLibrary;
use App\Support\GalleryTypography;
use App\Support\HeaderFonts;
use App\Support\HeaderSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteTypographyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Storage::fake('public');
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    private function uploadFont(): string
    {
        $this->post(route('fonts.store'), [
            'font_file' => UploadedFile::fake()->createWithContent('Wspólna.ttf', file_get_contents(__DIR__ . '/../Fixtures/header-test.ttf')),
        ])->assertSessionHasNoErrors();
        return array_key_first(app(SiteFontLibrary::class)->catalog()['fonts']);
    }

    private function layout(?string $font = null): array
    {
        $sections = [];
        foreach (SiteFontLibrary::TEXT_BLOCKS as $index => $type) {
            $style = ['font_size' => 24, 'font_weight' => 600, 'color' => '#123456', 'letter_spacing' => 2, 'custom_style' => 'keep'];
            if ($font !== null) {
                $style['font_family'] = $font;
            }
            $sections[] = ['id' => 'block-' . $index, 'type' => $type, 'content' => 'Tekst ' . $type,
                'style' => $style, 'position_x' => 12, 'position_y' => 10 * $index, 'element_width' => 50,
                'custom_property' => ['keep' => true]];
        }
        return ['version' => 1, 'settings' => ['background_color' => '#eeeeee'], 'sections' => $sections, 'custom_root' => 'preserve'];
    }

    public function test_all_text_block_types_save_system_and_custom_fonts_without_losing_other_data(): void
    {
        $page = Page::create(['title' => 'Test', 'slug' => 'test', 'published' => true]);
        $custom = $this->uploadFont();
        foreach (['Georgia', $custom] as $font) {
            $layout = $this->layout($font);
            $this->postJson(route('pages.builder.save', $page), ['content' => $layout])->assertOk();
            $this->assertSame($layout, $page->fresh()->builder->content);
            $response = $this->get('/strona/test')->assertOk();
            $css = SiteFontLibrary::css($font, app(SiteFontLibrary::class)->catalog());
            foreach (SiteFontLibrary::TEXT_BLOCKS as $type) {
                $this->assertMatchesRegularExpression('/class="page-element page-element-' . $type . '"\s+style="[^"]*font-family:' . preg_quote($css, '/') . '/s', $response->getContent());
            }
            if ($font === $custom) {
                $response->assertSee('@font-face', false)->assertSee('storage/fonts/' . $custom . '.ttf', false);
            }
        }
        $this->assertCount(1, Storage::disk('public')->allFiles('fonts'));
    }

    public function test_old_layouts_still_save_and_unknown_fonts_are_rejected_and_render_safely(): void
    {
        $page = Page::create(['title' => 'Stara', 'slug' => 'stara', 'published' => true]);
        $layout = $this->layout();
        $this->postJson(route('pages.builder.save', $page), ['content' => $layout])->assertOk();
        $this->assertSame($layout, $page->fresh()->builder->content);
        $this->get('/strona/stara')->assertOk()->assertSee('font-family:Arial, sans-serif', false);
        foreach (['Arial; background:url(https://bad.test)', 'hf_' . str_repeat('a', 32)] as $font) {
            $response = $this->postJson(route('pages.builder.save', $page), ['content' => $this->layout($font)]);
            $this->assertSame(422, $response->getStatusCode(), substr($response->getContent(), 0, 1200));
            $response->assertJsonValidationErrors('content.sections.0.style.font_family');
            $this->assertSame($layout, $page->fresh()->builder->content);
        }
        // Simulate legacy/tampered stored data bypassing the save endpoint.
        $page->fresh()->builder->update(['content' => $this->layout('Arial; background:url(https://bad.test)')]);
        $this->get('/strona/stara')->assertOk()->assertSee('font-family:Arial, sans-serif', false)->assertDontSee('https://bad.test');
    }

    public function test_header_and_both_builders_share_one_upload_and_one_font_identifier(): void
    {
        $id = $this->uploadFont();
        $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
            'header_logo_font_family' => $id, 'header_subtitle_font_family' => $id,
        ]))->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('font-family:' . $id, false);
        $this->get(route('header-settings.edit'))->assertOk()->assertSee('Wspólna (własna)');

        $page = Page::create(['title' => 'Builder', 'slug' => 'builder']);
        foreach ([route('pages.builder', $page), route('home-builder.edit')] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('js/site-typography.js', false)
                ->assertSee($id)->assertSee('@font-face', false);
            // Check the actual rendered inline JS, including Blade data serialization.
            preg_match_all('/<script(?:\s[^>]*)?>(.*?)<\/script>/s', $response->getContent(), $scripts);
            foreach ($scripts[1] as $script) {
                if (!trim($script)) continue;
                $process = proc_open(['node', '--check'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
                fwrite($pipes[0], $script);
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $output);
            }
        }
        $layout = $this->layout($id);
        $this->postJson(route('home-builder.save'), ['content' => $layout])->assertOk();
        $this->assertSame($layout, PageBuilder::whereNull('page_id')->where('type', 'home')->first()->content);
        $this->get('/')->assertSee('Tekst heading'); // HomeBuilder is integrated with the homepage.
        $this->assertCount(1, Storage::disk('public')->allFiles('fonts'));
    }

    public function test_header_upload_is_immediately_available_in_central_library(): void
    {
        $this->put(route('header-settings.update'), HeaderSettings::DEFAULTS + [
            'font_file' => UploadedFile::fake()->createWithContent('Z nagłówka.ttf', file_get_contents(__DIR__ . '/../Fixtures/header-test.ttf')),
            'font_target' => 'logo',
        ])->assertSessionHasNoErrors();
        $this->get(route('fonts.index'))->assertOk()->assertSee('Z nagłówka (własna)');
        $this->assertCount(1, app(SiteFontLibrary::class)->catalog()['fonts']);
        $this->assertCount(1, Storage::disk('public')->allFiles('fonts'));
    }

    public function test_static_urls_preserve_fallbacks_and_use_existing_nonempty_layouts(): void
    {
        foreach (['o-mnie' => 'O mnie', 'kontakt' => 'Kontakt'] as $slug => $title) {
            $this->get('/' . $slug)->assertOk()->assertSee($title);
            $this->assertDatabaseMissing('pages', ['slug' => $slug]);
            $page = Page::create(['title' => $title, 'slug' => $slug, 'published' => true, 'content' => 'Zachowana treść']);
            $builder = PageBuilder::create(['page_id' => $page->id, 'type' => 'page', 'published' => true,
                'content' => ['version' => 1, 'settings' => ['custom' => 'keep'], 'sections' => []]]);
            $before = $builder->content;
            $this->get(route('content-pages.edit', $slug))->assertOk()->assertSee('Zachowana tre', false)
                ->assertSee('dopiero po zapisaniu buildera');
            $this->assertSame($before, $builder->fresh()->content);
            $this->assertSame('Zachowana treść', $page->fresh()->content);
            $this->postJson(route('pages.builder.save', $page), ['content' => $this->layout('Verdana')])->assertOk();
            $this->get('/' . $slug)->assertOk()->assertSee('Tekst heading')->assertSee('font-family:Verdana, sans-serif', false);
            $before = $builder->fresh()->content;
            $this->get(route('content-pages.edit', $slug))->assertOk()->assertDontSee('To propozycja układu');
            $this->assertSame($before, $builder->fresh()->content);
        }
    }

    public function test_gallery_fonts_use_library_without_changing_photos_order_or_cover(): void
    {
        $font = $this->uploadFont();
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria', 'description' => 'Opis', 'sort_order' => 7]);
        $gallery->forceFill(['cover_image' => 'preserve.jpg'])->save();
        $this->put(route('galleries.update', $gallery), [
            'title' => 'Galeria', 'description' => 'Opis',
            'title_font_family' => $font, 'description_font_family' => 'Georgia', 'caption_font_family' => 'Verdana',
        ])->assertSessionHasNoErrors();
        $this->assertSame(7, $gallery->fresh()->sort_order);
        $this->assertSame('preserve.jpg', $gallery->fresh()->cover_image);
        $this->get(route('galleries.edit', $gallery))->assertOk()->assertSee('Wspólna (własna)');
        $this->get(route('portfolio.gallery', $gallery))->assertOk()
            ->assertSee('font-family:' . $font, false)->assertSee('font-family:Georgia, serif', false)
            ->assertSee('Verdana, sans-serif', false)->assertSee('storage/fonts/' . $font . '.ttf', false);

        PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => [
                'version' => 1,
                'settings' => [],
                'sections' => [[
                    'id' => 'home-gallery',
                    'type' => 'gallery',
                    'gallery_mode' => 'all',
                    'position_x' => 0,
                    'position_y' => 40,
                    'element_width' => 100,
                    'style' => ['font_family' => $font],
                ]],
            ],
        ]);

        $this->get('/')->assertOk()->assertSee('font-family:' . $font, false);
        $before = SiteSetting::where('key', GalleryTypography::key($gallery->id))->value('value');
        $this->put(route('galleries.update', $gallery), ['title' => 'Galeria', 'description' => 'Opis', 'title_font_family' => 'Arial; color:red'])
            ->assertSessionHasErrors('title_font_family');
        $this->assertSame($before, SiteSetting::where('key', GalleryTypography::key($gallery->id))->value('value'));
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_default_fonts_apply_to_static_content_and_central_upload_is_protected(): void
    {
        $id = $this->uploadFont();
        $this->put(route('fonts.update'), ['site_body_font_family' => $id, 'site_heading_font_family' => 'Georgia'])
            ->assertSessionHasNoErrors();
        foreach (['/', '/o-mnie', '/kontakt'] as $url) {
            $this->get($url)->assertOk()->assertSee('font-family: ' . $id, false)->assertSee('font-family: Georgia, serif', false);
        }
        $this->post(route('fonts.store'), ['font_file' => UploadedFile::fake()->createWithContent('bad.ttf', '<?php echo 1;')])
            ->assertSessionHasErrors('font_file');
        $this->assertCount(1, app(SiteFontLibrary::class)->catalog()['fonts']);
        auth()->logout();
        $this->get(route('fonts.index'))->assertRedirect(route('login'));
        $this->post(route('fonts.store'))->assertRedirect(route('login'));
        $this->get(route('content-pages.edit', 'kontakt'))->assertRedirect(route('login'));
    }
}
