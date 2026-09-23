<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\HeaderSettings;
use Tests\TestCase;

class HeaderSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never run these migrations against the project's persistent database.
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutVite();
    }

    public function test_header_routes_require_authentication(): void
    {
        $this->get(route('header-settings.edit'))->assertRedirect(route('login'));
        $this->put(route('header-settings.update'), HeaderSettings::DEFAULTS)
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_opening_form_uses_defaults_without_writing_settings(): void
    {
        SiteSetting::create(['key' => 'logo', 'value' => 'Obecne logo']);
        $this->actingAs(User::factory()->create())
            ->get(route('header-settings.edit'))->assertOk()
            ->assertSee('Obecne logo')->assertSee('Logo wyśrodkowane / menu poniżej');
        $this->assertDatabaseCount('site_settings', 1);
    }

    public function test_save_changes_only_header_keys_and_old_form_cannot_overwrite_logo(): void
    {
        SiteSetting::create(['key' => 'background_color', 'value' => '#abcdef']);
        SiteSetting::create(['key' => 'hero_text', 'value' => 'Treść hero']);
        $this->actingAs(User::factory()->create())
            ->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
                'logo' => 'Nowe logo', 'logo_subtitle' => '', 'header_layout' => 'center',
                'background_color' => '#000000', 'hero_text' => 'Nie zapisuj',
            ]))->assertSessionHasNoErrors()->assertRedirect(route('header-settings.edit'));

        $this->assertDatabaseCount('site_settings', 15);
        $this->assertDatabaseHas('site_settings', ['key' => 'background_color', 'value' => '#abcdef']);
        $this->assertDatabaseHas('site_settings', ['key' => 'hero_text', 'value' => 'Treść hero']);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_layout', 'value' => 'center']);
        $this->assertDatabaseHas('site_settings', ['key' => 'logo_subtitle', 'value' => '']);

        $this->put(route('site-settings.update'), [
            'logo' => 'Nie nadpisuj', 'logo_subtitle' => 'Nie nadpisuj',
            'background_color' => '#abcdef', 'hero_text' => 'Treść hero',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'logo', 'value' => 'Nowe logo']);
        $this->assertDatabaseHas('site_settings', ['key' => 'logo_subtitle', 'value' => '']);
    }

    public function test_invalid_styles_are_rejected_without_partial_save(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
                'header_logo_color' => 'red;display:none',
                'header_subtitle_font_size' => 999,
                'header_logo_font_weight' => 999,
                'header_logo_letter_spacing' => -5,
                'header_layout' => 'custom',
            ]))->assertSessionHasErrors([
                'header_logo_color', 'header_subtitle_font_size', 'header_logo_font_weight',
                'header_logo_letter_spacing', 'header_layout',
            ]);
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_component_uses_safe_fallbacks_and_escapes_text(): void
    {
        $html = view('components.site-header', ['settings' => [
            'logo' => '<script>alert(1)</script>',
            'header_logo_color' => 'red;display:none',
            'header_layout' => 'unknown',
        ]])->render();
        $this->assertStringContainsString('header-layout-left', $html);
        $this->assertStringContainsString('font-size:28px', $html);
        $this->assertStringContainsString('color:#222222', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('red;display:none', $html);
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_all_public_views_and_future_pages_use_global_header_and_submenus(): void
    {
        foreach (array_replace(HeaderSettings::DEFAULTS, [
            'logo' => 'Globalna nazwa', 'header_layout' => 'center',
            'header_logo_font_size' => 36, 'header_subtitle_color' => '#123456',
            'header_logo_font_family' => 'Georgia', 'header_subtitle_font_family' => 'Verdana',
        ]) as $key => $value) {
            SiteSetting::create(compact('key', 'value'));
        }
        $page = Page::create(['title' => 'Przyszła strona', 'slug' => 'przyszla', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        $parent = MenuItem::create(['title' => 'Menu nadrzędne', 'type' => 'page', 'page_id' => $page->id, 'published' => true]);
        MenuItem::create(['title' => 'Podmenu galerii', 'type' => 'gallery', 'gallery_id' => $gallery->id, 'parent_id' => $parent->id, 'published' => true]);
        MenuItem::create(['title' => 'Ukryte podmenu', 'type' => 'url', 'url' => '/ukryte', 'parent_id' => $parent->id, 'published' => false]);
        MenuItem::create(['title' => 'Link zewnętrzny', 'type' => 'url', 'url' => 'https://example.com/portfolio', 'published' => true]);

        foreach (['/', '/o-mnie', '/kontakt', route('page.public', $page), route('portfolio.gallery', $gallery)] as $url) {
            $this->get($url)->assertOk()->assertSee('Globalna nazwa')
                ->assertSee('header-layout-center')->assertSee('font-size:36px', false)
                ->assertSee('color:#123456', false)->assertSee('Podmenu galerii')
                ->assertSee('font-family:Georgia, serif', false)->assertSee('font-family:Verdana, sans-serif', false)
                ->assertSee(route('page.public', $page))->assertSee(route('portfolio.gallery', $gallery))
                ->assertSee('https://example.com/portfolio')->assertDontSee('Ukryte podmenu');
        }
        $this->assertDatabaseCount('page_builders', 0);
    }
}
