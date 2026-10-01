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

        $this->assertDatabaseCount('site_settings', count(HeaderSettings::DEFAULTS) + 2);
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

    public function test_padding_defaults_and_legacy_values_are_resolved_independently(): void
    {
        foreach ([null, -1, 161, '48px;display:none', ['48']] as $padding) {
            $settings = ['header_padding_y' => $padding];
            $resolved = HeaderSettings::resolve($settings);
            $this->assertSame(48, $resolved['header_padding_top']);
            $this->assertSame(48, $resolved['header_padding_bottom']);
            $html = view('components.site-header', compact('settings'))->render();
            $this->assertStringContainsString('--header-padding-top: 48px;', $html);
            $this->assertStringContainsString('--header-padding-bottom: 48px;', $html);
            $this->assertStringNotContainsString('48px;display:none', $html);
        }
        foreach ([0, 48, 100, 160] as $legacy) {
            $settings = HeaderSettings::resolve(['header_padding_y' => (string) $legacy]);
            $this->assertSame($legacy, $settings['header_padding_top']);
            $this->assertSame($legacy, $settings['header_padding_bottom']);
        }
        $settings = HeaderSettings::resolve(['header_padding_y' => 100, 'header_padding_top' => 0]);
        $this->assertSame(0, $settings['header_padding_top']);
        $this->assertSame(100, $settings['header_padding_bottom']);
        $settings = HeaderSettings::resolve(['header_padding_y' => 100, 'header_padding_bottom' => 12]);
        $this->assertSame(100, $settings['header_padding_top']);
        $this->assertSame(12, $settings['header_padding_bottom']);
        $this->actingAs(User::factory()->create())->get(route('header-settings.edit'))->assertOk()
            ->assertSee('Odstęp nad logo i menu (px)')->assertSee('Odstęp pod logo i menu (px)')
            ->assertSee('Menu po lewej / logo po prawej')
            ->assertDontSee('name="header_padding_y"', false);
        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_logo_keeps_entered_case_and_gap_is_editable_everywhere(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
                'logo' => 'Magda Gugała',
                'logo_subtitle' => 'Fotografia i stylizacja żywności',
                'header_logo_subtitle_gap' => 18,
            ]))->assertSessionHasNoErrors();

        $this->get(route('header-settings.edit'))->assertOk()
            ->assertSee('Odstęp między logo a podtytułem (px)')
            ->assertSee('value="18"', false);

        foreach (['/', '/o-mnie', '/kontakt'] as $url) {
            $response = $this->get($url)->assertOk()
                ->assertSee('Magda Gugała')
                ->assertSee('Fotografia i stylizacja żywności')
                ->assertSee('--header-logo-subtitle-gap: 18px;', false)
                ->assertSee('text-transform: none !important;', false);
            $this->assertStringNotContainsString('MAGDA GUGAŁA', $response->getContent());
        }
    }

    public function test_independent_padding_and_all_three_layouts_apply_to_every_public_page(): void
    {
        $this->actingAs(User::factory()->create());
        $page = Page::create(['title' => 'Nowa strona', 'slug' => 'nowa', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        $menu = MenuItem::create(['title' => 'Portfolio', 'type' => 'url', 'url' => '/#portfolio', 'published' => true]);
        MenuItem::create(['title' => 'Moja galeria', 'type' => 'gallery', 'gallery_id' => $gallery->id, 'parent_id' => $menu->id, 'published' => true]);
        $urls = ['/', '/o-mnie', '/kontakt', route('page.public', $page), route('portfolio.gallery', $gallery)];

        foreach (['left', 'center', 'right'] as $layout) {
            $previousHeaders = [];
            foreach ([[72, 24], [0, 24], [0, 80]] as [$top, $bottom]) {
                $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
                    'header_layout' => $layout, 'header_padding_top' => $top, 'header_padding_bottom' => $bottom,
                    'header_logo_font_size' => 34, 'header_subtitle_font_size' => 12,
                ]))->assertRedirect(route('header-settings.edit'))->assertSessionHasNoErrors();
                $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_top', 'value' => (string) $top]);
                $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_bottom', 'value' => (string) $bottom]);
                $this->get(route('header-settings.edit'))->assertOk()
                    ->assertSee('value="'.$top.'" aria-describedby="header-padding-help"', false)
                    ->assertSee('value="'.$bottom.'" aria-describedby="header-padding-help"', false);

                foreach ($urls as $url) {
                    $response = $this->get($url)->assertOk()
                        ->assertSee('--header-padding-top: '.$top.'px;', false)
                        ->assertSee('--header-padding-bottom: '.$bottom.'px;', false)
                        ->assertSee('header-layout-'.$layout)
                        ->assertSee('font-size:34px;', false)->assertSee('font-size:12px;', false)
                        ->assertSee('Moja galeria')->assertSee('main-submenu');
                    preg_match('/<header\b.*?<\/header>/s', $response->getContent(), $header);
                    $this->assertNotEmpty($header);
                    $normalized = str_replace([
                        '--header-padding-top: '.$top.'px;', '--header-padding-bottom: '.$bottom.'px;',
                    ], ['--header-padding-top: value;', '--header-padding-bottom: value;'], $header[0]);
                    if (isset($previousHeaders[$url])) {
                        $this->assertSame($previousHeaders[$url], $normalized);
                    }
                    $previousHeaders[$url] = $normalized;
                }
            }
        }
    }

    public function test_legacy_padding_is_preserved_until_each_side_is_saved_independently(): void
    {
        $this->actingAs(User::factory()->create());
        SiteSetting::create(['key' => 'header_padding_y', 'value' => '100']);
        $response = $this->get(route('header-settings.edit'))->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'value="100" aria-describedby="header-padding-help"'));
        $this->assertDatabaseCount('site_settings', 1);
        $this->get('/')->assertOk()->assertSee('--header-padding-top: 100px;', false)
            ->assertSee('--header-padding-bottom: 100px;', false);

        $oldForm = HeaderSettings::DEFAULTS;
        unset($oldForm['header_padding_top'], $oldForm['header_padding_bottom']);
        $this->put(route('header-settings.update'), $oldForm)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_top', 'value' => '100']);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_bottom', 'value' => '100']);

        $this->put(route('header-settings.update'), $oldForm + ['header_padding_top' => 20])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_top', 'value' => '20']);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_bottom', 'value' => '100']);
        $this->put(route('header-settings.update'), $oldForm + ['header_padding_bottom' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_top', 'value' => '20']);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_bottom', 'value' => '0']);
        $this->put(route('header-settings.update'), $oldForm + ['header_padding_y' => 48])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('--header-padding-top: 20px;', false)
            ->assertSee('--header-padding-bottom: 0px;', false);
        $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_y', 'value' => '100']);
    }

    public function test_invalid_padding_is_rejected_without_partial_changes(): void
    {
        $this->actingAs(User::factory()->create());
        SiteSetting::create(['key' => 'header_padding_top', 'value' => '60']);
        SiteSetting::create(['key' => 'header_padding_bottom', 'value' => '25']);
        SiteSetting::create(['key' => 'logo', 'value' => 'Zachowaj logo']);
        foreach (['header_padding_top', 'header_padding_bottom'] as $key) {
            foreach ([-1, 161, 12.5, '', 'auto', '48px;display:none', ['48']] as $padding) {
                $this->put(route('header-settings.update'), array_replace(HeaderSettings::DEFAULTS, [
                    $key => $padding,
                ]))->assertSessionHasErrors($key);
                $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_top', 'value' => '60']);
                $this->assertDatabaseHas('site_settings', ['key' => 'header_padding_bottom', 'value' => '25']);
                $this->assertDatabaseHas('site_settings', ['key' => 'logo', 'value' => 'Zachowaj logo']);
            }
        }
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
