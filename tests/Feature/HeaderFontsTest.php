<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\HeaderFonts;
use App\Support\HeaderSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeaderFontsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Storage::fake('public');
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    private function upload(string $target = 'both')
    {
        return $this->put(route('header-settings.update'), HeaderSettings::DEFAULTS + [
            'font_target' => $target,
            'font_file' => UploadedFile::fake()->createWithContent('Moja czcionka.ttf', file_get_contents(__DIR__ . '/../Fixtures/header-test.ttf')),
        ]);
    }

    public function test_independent_font_families_are_saved_without_overwriting_other_settings(): void
    {
        SiteSetting::create(['key' => 'background_color', 'value' => '#aabbcc']);
        SiteSetting::create(['key' => 'unrelated', 'value' => 'zachowaj']);
        $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
            'header_logo_font_family' => 'Georgia',
            'header_subtitle_font_family' => 'Courier New',
            'background_color' => '#000000', 'unrelated' => 'nadpisz',
            'header_custom_fonts' => '{"injection":"not allowed"}',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'header_logo_font_family', 'value' => 'Georgia']);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_subtitle_font_family', 'value' => 'Courier New']);
        $this->assertDatabaseHas('site_settings', ['key' => 'background_color', 'value' => '#aabbcc']);
        $this->assertDatabaseHas('site_settings', ['key' => 'unrelated', 'value' => 'zachowaj']);
        $this->assertDatabaseMissing('site_settings', ['key' => HeaderFonts::SETTING_KEY]);
    }

    public function test_unlisted_font_families_and_css_injection_are_rejected(): void
    {
        foreach (['Arial; color:red', 'hf_' . str_repeat('a', 32), 'unknown', ['Arial']] as $family) {
            $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
                'header_logo_font_family' => $family, 'header_subtitle_font_family' => $family,
            ]))->assertSessionHasErrors(['header_logo_font_family', 'header_subtitle_font_family']);
        }
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_extension_mime_signature_and_size_are_checked_before_storage(): void
    {
        $ttf = file_get_contents(__DIR__ . '/../Fixtures/header-test.ttf');
        $files = [
            UploadedFile::fake()->createWithContent('payload.php', '<?php echo "bad";'),
            UploadedFile::fake()->createWithContent('payload.ttf', '<?php echo "bad";'),
            UploadedFile::fake()->createWithContent('font.php', $ttf),
            UploadedFile::fake()->createWithContent('wrong-format.woff', $ttf),
            UploadedFile::fake()->createWithContent('huge.ttf', $ttf . str_repeat('x', 5 * 1024 * 1024)),
        ];
        foreach ($files as $file) {
            $this->put(route('header-settings.update'), HeaderSettings::DEFAULTS + [
                'font_file' => $file, 'font_target' => 'logo',
            ])->assertSessionHasErrors('font_file');
        }
        $this->assertDatabaseCount('site_settings', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_uploaded_font_is_stored_with_safe_id_and_available_on_all_public_views(): void
    {
        $this->upload()->assertSessionHasNoErrors()->assertRedirect(route('header-settings.edit'));
        $stored = SiteSetting::pluck('value', 'key')->all();
        $fonts = HeaderFonts::custom($stored);
        $this->assertCount(1, $fonts);
        $id = array_key_first($fonts);
        $this->assertMatchesRegularExpression('/^hf_[a-f0-9]{32}$/', $id);
        $this->assertSame('fonts/' . $id . '.ttf', $fonts[$id]['path']);
        Storage::disk('public')->assertExists($fonts[$id]['path']);
        $this->assertSame($id, $stored['header_logo_font_family']);
        $this->assertSame($id, $stored['header_subtitle_font_family']);
        $this->assertSame('Moja czcionka', $fonts[$id]['label']);

        $page = Page::create(['title' => 'Nowa', 'slug' => 'nowa', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        foreach (['/', '/o-mnie', '/kontakt', route('page.public', $page), route('portfolio.gallery', $gallery)] as $url) {
            $this->get($url)->assertOk()->assertSee('@font-face', false)
                ->assertSee(asset('storage/' . $fonts[$id]['path']), false)
                ->assertSee('font-family:' . $id . ', Arial, sans-serif', false);
        }
        $this->get(route('header-settings.edit'))->assertOk()->assertSee('Moja czcionka (własna)')
            ->assertSee('PODGLĄD NAGŁÓWKA')->assertSee('js/header-settings.js', false);

        $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
            'header_logo_font_family' => 'Verdana', 'header_subtitle_font_family' => $id,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'header_subtitle_font_family', 'value' => $id]);
        $this->assertSame($stored[HeaderFonts::SETTING_KEY], SiteSetting::where('key', HeaderFonts::SETTING_KEY)->value('value'));
    }

    public function test_another_upload_preserves_existing_fonts_and_only_changes_requested_target(): void
    {
        $this->upload('logo')->assertSessionHasNoErrors();
        $first = SiteSetting::where('key', 'header_logo_font_family')->value('value');
        $this->assertDatabaseHas('site_settings', ['key' => 'header_subtitle_font_family', 'value' => 'Arial']);
        $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
            'header_logo_font_family' => $first,
            'font_target' => 'subtitle',
            'font_file' => UploadedFile::fake()->createWithContent('Druga.ttf', file_get_contents(__DIR__ . '/../Fixtures/header-test.ttf')),
        ]))->assertSessionHasNoErrors();
        $fonts = HeaderFonts::custom(SiteSetting::pluck('value', 'key')->all());
        $this->assertCount(2, $fonts);
        $this->assertArrayHasKey($first, $fonts);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_logo_font_family', 'value' => $first]);
        $this->assertNotSame($first, SiteSetting::where('key', 'header_subtitle_font_family')->value('value'));
    }

    public function test_invalid_stored_font_metadata_falls_back_without_css_or_path_injection(): void
    {
        $id = 'hf_' . str_repeat('a', 32);
        $settings = ['header_logo_font_family' => $id, HeaderFonts::SETTING_KEY => json_encode([
            $id => ['label' => 'Bad', 'path' => '../secret.ttf'],
            'bad; color:red' => ['label' => 'Bad', 'path' => 'fonts/bad.ttf'],
        ])];
        $html = view('components.site-header', compact('settings'))->render();
        $this->assertStringContainsString('font-family:Arial, sans-serif', $html);
        $this->assertStringNotContainsString('@font-face', $html);
        $this->assertStringNotContainsString('../secret', $html);
    }
}
