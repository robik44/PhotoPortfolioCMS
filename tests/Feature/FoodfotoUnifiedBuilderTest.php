<?php

namespace Tests\Feature;

use App\Models\PageBuilder;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodfotoUnifiedBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function homepage(): PageBuilder
    {
        return PageBuilder::create([
            'page_id' => null,
            'type' => 'home',
            'published' => true,
            'content' => ['version' => 1, 'settings' => [], 'sections' => [
                ['id' => 'hero-heading', 'type' => 'heading', 'content' => 'Poprzedni nagłówek'],
                ['id' => 'hero-text', 'type' => 'text', 'content' => 'Poprzedni opis'],
            ]],
        ]);
    }

    public function test_draft_editor_is_private_and_read_only_until_save(): void
    {
        $original = $this->homepage();
        SiteSetting::updateOrCreate(['key' => 'preview_hero_title'], ['value' => 'Nowy nagłówek']);
        $this->get(route('foodfoto.builder.edit'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->withoutVite()
            ->get(route('foodfoto.builder.edit'))
            ->assertOk()
            ->assertSee('Roboczy builder FoodFoto')
            ->assertSee('Nowy nagłówek');
        $this->assertDatabaseMissing('page_builders', ['type' => 'foodfoto_draft']);
        $this->assertSame('Poprzedni nagłówek', $original->fresh()->content['sections'][0]['content']);
    }

    public function test_draft_save_never_overwrites_published_homepage(): void
    {
        $original = $this->homepage();
        $before = $original->content;
        $this->actingAs(User::factory()->create())
            ->postJson(route('foodfoto.builder.save'), ['content' => [
                'version' => 1, 'settings' => [], 'sections' => [
                    ['id' => 'test-heading', 'type' => 'heading', 'content' => 'Wersja robocza'],
                ],
            ]])->assertOk();
        $this->assertDatabaseHas('page_builders', ['type' => 'foodfoto_draft', 'published' => false]);
        $this->assertSame($before, $original->fresh()->content);
        $this->get('/')->assertOk()->assertDontSee('Wersja robocza');
    }
    public function test_imports_process_cards_and_selected_photographs_as_editable_blocks(): void
    {
        $home = $this->homepage();
        SiteSetting::updateOrCreate(['key' => 'preview_process_1_photo_id'], ['value' => '42']);
        SiteSetting::updateOrCreate(['key' => 'preview_editorial_title'], ['value' => 'Więcej niż zwykłe zdjęcia']);

        $this->actingAs(User::factory()->create())->withoutVite()
            ->get(route('foodfoto.builder.edit'))
            ->assertOk()
            ->assertSee('Więcej niż zwykłe zdjęcia');

        // The import remains transient until explicitly saved in the draft.
        $this->assertDatabaseMissing('page_builders', ['type' => 'foodfoto_draft']);
        $this->assertSame($home->content, $home->fresh()->content);
    }

    public function test_saved_draft_reopens_with_same_drag_positions_and_typography(): void
    {
        $home = $this->homepage();
        $original = $home->content;
        $user = User::factory()->create();
        $this->actingAs($user)->withoutVite();
        $draft = [
            'version' => 1,
            'settings' => [],
            'sections' => [[
                'id' => 'moveable-headline',
                'type' => 'heading',
                'content' => 'Mój tekst po przesunięciu',
                'position_x' => 24.5,
                'position_y' => 37.25,
                'element_width' => 64.5,
                'style' => ['font_size' => 42.1, 'line_height' => 1.15],
            ]],
        ];
        $this->postJson(route('foodfoto.builder.save'), ['content' => $draft])->assertOk();
        $this->get(route('foodfoto.builder.edit'))->assertOk()
            ->assertSee('moveable-headline')
            ->assertSee('24.5')
            ->assertSee('42.1');
        $saved = PageBuilder::where('type', 'foodfoto_draft')->firstOrFail();
        $this->assertSame(24.5, $saved->content['sections'][0]['position_x']);
        $this->assertSame(37.25, $saved->content['sections'][0]['position_y']);
        $this->assertSame(64.5, $saved->content['sections'][0]['element_width']);
        $this->assertSame(42.1, $saved->content['sections'][0]['style']['font_size']);
        $this->assertSame($original, $home->fresh()->content);
    }

    public function test_draft_save_requires_login(): void
    {
        $this->homepage();
        $this->postJson(route('foodfoto.builder.save'), ['content' => [
            'version' => 1, 'settings' => [], 'sections' => [],
        ]])->assertRedirect('/login');
        $this->assertDatabaseMissing('page_builders', ['type' => 'foodfoto_draft']);
    }

    public function test_real_homepage_canvas_is_private_and_keeps_its_positions_in_draft_only(): void
    {
        $sections = array_fill(0, 32, ['type' => 'text', 'content' => 'Test']);
        $sections[3]['content'] = 'Co tworzę';
        $sections[24] = ['type' => 'gallery', 'gallery_mode' => 'selected', 'gallery_ids' => []];
        $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        $sections[31] = ['type' => 'image', 'photo_id' => 96];
        PageBuilder::create(['page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['version' => 1, 'settings' => [], 'sections' => $sections]]);
        $this->get(route('foodfoto.canvas'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->withoutVite()
            ->get(route('foodfoto.canvas'))->assertOk()
            ->assertSee('EDYTOR NA PRAWDZIWYM UKŁADZIE')
            ->assertSee('data-move-id="services-intro"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $payload = json_encode(['services-intro' => ['x' => 25, 'y' => -10, 'w' => 0, 'z' => 1]]);
        $this->post(route('foodfoto.canvas.layout'), ['layout' => $payload])
            ->assertRedirect(route('foodfoto.canvas'));
        $this->assertDatabaseHas('site_settings', ['key' => 'foodfoto_canvas_draft_positions', 'value' => $payload]);
        $this->assertDatabaseMissing('site_settings', ['key' => 'preview_editor_positions']);
        $this->get(route('foodfoto.canvas'))->assertOk()->assertSee('translate: 25px -10px', false);
    }

    public function test_canvas_text_edits_are_private_and_reopen_without_publication(): void
    {
        $sections = array_fill(0, 32, ['type' => 'text', 'content' => 'Test']);
        $sections[3]['content'] = 'Co tworzę';
        $sections[24] = ['type' => 'gallery', 'gallery_mode' => 'selected', 'gallery_ids' => []];
        $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        $sections[31] = ['type' => 'image', 'photo_id' => 96];
        PageBuilder::create(['page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['version' => 1, 'settings' => [], 'sections' => $sections]]);
        $this->post(route('foodfoto.canvas.texts'), ['texts' => '{}'])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->withoutVite();
        $this->post(route('foodfoto.canvas.texts'), [
            'texts' => json_encode(['preview_hero_title' => 'Nowy tytuł roboczy', 'section-4' => 'Opis roboczy']),
        ])->assertRedirect(route('foodfoto.canvas'));
        $this->get(route('foodfoto.canvas'))->assertOk()->assertSee('Nowy tytuł roboczy')->assertSee('Opis roboczy');
        $this->get('/')->assertOk()->assertDontSee('Nowy tytuł roboczy');
        $this->assertDatabaseMissing('site_settings', ['key' => 'preview_hero_title']);
        $this->post(route('foodfoto.canvas.texts'), [
            'texts' => json_encode(['preview_hero_title' => '<script>bad()</script>Safe']),
        ])->assertRedirect(route('foodfoto.canvas'));
        $this->get(route('foodfoto.canvas'))->assertOk()->assertDontSee('<script>bad()</script>', false);
    }

    public function test_canvas_typography_remains_private_and_uses_precise_values(): void
    {
        $this->post(route('foodfoto.canvas.typography'), ['typography' => '{}'])->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $value = json_encode(['preview_hero_title' => [
            'font_size' => 62.1, 'line_height' => 1.15, 'letter_spacing' => -0.12,
        ]]);
        $this->post(route('foodfoto.canvas.typography'), ['typography' => $value])
            ->assertRedirect(route('foodfoto.canvas'));
        $stored = json_decode(SiteSetting::where('key', 'foodfoto_canvas_draft_typography')->value('value'), true);
        $this->assertSame(62.1, $stored['preview_hero_title']['font_size']);
        $this->assertSame(1.15, $stored['preview_hero_title']['line_height']);
        $this->assertSame(-0.12, $stored['preview_hero_title']['letter_spacing']);
        $this->assertDatabaseMissing('site_settings', ['key' => 'preview_hero_heading_size']);
        $this->post(route('foodfoto.canvas.typography'), ['typography' => json_encode([
            'preview_hero_title' => ['font_size' => 999, 'line_height' => 1, 'letter_spacing' => 0],
        ])])->assertStatus(422);
    }

    public function test_canvas_photo_picker_only_saves_valid_private_choices(): void
    {
        $this->post(route('foodfoto.canvas.photos'), ['photos' => '{}'])->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->post(route('foodfoto.canvas.photos'), ['photos' => json_encode([
            'hero-photo' => 9999999,
        ])])->assertStatus(422);
        $this->post(route('foodfoto.canvas.photos'), ['photos' => json_encode([
            'invalid_slot' => 1,
        ])])->assertStatus(422);
        $this->post(route('foodfoto.canvas.photos'), ['photos' => '{}'])
            ->assertRedirect(route('foodfoto.canvas'));
        $this->assertDatabaseHas('site_settings', ['key' => 'foodfoto_canvas_draft_photos', 'value' => '[]']);
        $this->assertDatabaseMissing('site_settings', ['key' => 'preview_hero_image']);
    }

    public function test_canvas_shows_single_contextual_builder_sidebar(): void
    {
        $sections = array_fill(0, 32, ['type' => 'text', 'content' => 'Test']);
        $sections[3]['content'] = 'Co tworzę';
        $sections[24] = ['type' => 'gallery', 'gallery_mode' => 'selected', 'gallery_ids' => []];
        $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        $sections[31] = ['type' => 'image', 'photo_id' => 96];
        PageBuilder::create(['page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['version' => 1, 'settings' => [], 'sections' => $sections]]);
        $this->actingAs(User::factory()->create())->withoutVite()
            ->get(route('foodfoto.canvas'))->assertOk()
            ->assertSee('id="ff-unified-panel"', false)
            ->assertSee('action="/admin/foodfoto-canvas/save-all"', false)
            ->assertSee('id="ff-unified-tools"', false)
            ->assertSee('id="ff-type-panel"', false)
            ->assertSee('id="ff-photo-panel"', false)
            ->assertSee('id="ff-text-toolbar"', false);
        $this->get('/')->assertOk()->assertDontSee('ff-unified-panel');
    }

    public function test_unified_draft_save_is_atomic_and_does_not_publish(): void
    {
        $home = $this->homepage();
        $before = $home->content;
        $payload = [
            'layout' => json_encode(['hero-text' => ['x' => 12, 'y' => 8, 'w' => 0, 'z' => 0]]),
            'texts' => json_encode(['preview_hero_title' => 'Wspólny zapis']),
            'typography' => json_encode(['preview_hero_title' => [
                'font_size' => 47.1, 'line_height' => 1.15, 'letter_spacing' => -.12,
            ]]),
            'photos' => '{}',
        ];
        $this->post(route('foodfoto.canvas.save-all'), $payload)->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $invalid = $payload;
        $invalid['typography'] = json_encode(['preview_hero_title' => [
            'font_size' => 999, 'line_height' => 1.15, 'letter_spacing' => 0,
        ]]);
        $this->post(route('foodfoto.canvas.save-all'), $invalid)->assertStatus(422);
        $this->assertDatabaseMissing('site_settings', ['key' => 'foodfoto_canvas_draft_positions']);
        $this->post(route('foodfoto.canvas.save-all'), $payload)
            ->assertRedirect(route('foodfoto.canvas', [], false));
        foreach ([
            'foodfoto_canvas_draft_positions',
            'foodfoto_canvas_draft_texts',
            'foodfoto_canvas_draft_typography',
            'foodfoto_canvas_draft_photos',
        ] as $key) {
            $this->assertDatabaseHas('site_settings', ['key' => $key]);
        }
        $this->assertSame($before, $home->fresh()->content);
        $this->assertDatabaseMissing('site_settings', ['key' => 'preview_hero_title']);
    }

    public function test_older_codespaces_home_uses_private_fixture_without_modifying_live_layout(): void
    {
        $live = $this->homepage();
        $before = $live->content;
        $this->actingAs(User::factory()->create())->withoutVite();
        $this->get(route('foodfoto.canvas'))->assertOk()
            ->assertSee('Tryb testowy Codespaces')
            ->assertSee('ff-unified-panel');
        $this->assertSame($before, $live->fresh()->content);
        $this->get(route('foodfoto.home.preview'))->assertStatus(409);
    }

    public function test_combined_save_serializers_do_not_submit_their_individual_forms(): void
    {
        // Guard against accidental browser navigation to old absolute APP_URL forms.
        $template = file_get_contents(resource_path('views/admin/foodfoto-home-preview.blade.php'));
        $this->assertStringContainsString('collectOnly.preventDefault()', $template);
        $this->assertStringContainsString('child.dispatchEvent(collectOnly)', $template);
    }

    public function test_save_all_uses_same_origin_fetch_without_browser_navigation(): void
    {
        $template = file_get_contents(resource_path('views/admin/foodfoto-home-preview.blade.php'));
        $this->assertStringContainsString("event.preventDefault();", $template);
        $this->assertStringContainsString("fetch('/admin/foodfoto-canvas/save-all'", $template);
        $this->assertStringContainsString("credentials:'same-origin'", $template);
        $this->assertStringContainsString('ff-save-status', $template);
    }

    public function test_save_all_returns_json_confirmation_for_ajax_clients(): void
    {
        $this->homepage();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('foodfoto.canvas.save-all'), [
            'layout' => '{}',
            'texts' => '{}',
            'typography' => '{}',
            'photos' => '{}',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_typography_ajax_save_persists_and_is_visible_after_refresh(): void
    {
        $this->homepage();
        $this->actingAs(User::factory()->create())->withoutVite();
        $styling = ['preview_process_title' => [
            'font_size' => 60, 'line_height' => 1.1, 'letter_spacing' => -1,
        ]];
        $this->postJson(route('foodfoto.canvas.typography'), [
            'typography' => json_encode($styling),
        ])->assertOk()->assertJsonPath('typography.preview_process_title.font_size', 60);
        $actual = json_decode(SiteSetting::where('key', 'foodfoto_canvas_draft_typography')->value('value'), true);
        foreach ($styling as $id => $expected) {
            foreach ($expected as $field => $value) {
                $this->assertEquals($value, $actual[$id][$field]);
            }
        }
        $this->get(route('foodfoto.canvas'))->assertOk()
            ->assertSee('font-size:60px!important', false);
    }

    public function test_typography_editor_serializes_empty_initial_state_as_object(): void
    {
        $template = file_get_contents(resource_path('views/admin/foodfoto-home-preview.blade.php'));
        $this->assertStringContainsString('const values={...@json($canvasTypography)};', $template);
        $this->assertStringNotContainsString('const values=@json($canvasTypography);', $template);
    }

    public function test_photo_click_actions_persist_as_private_draft_and_validate_internal_links(): void
    {
        $this->homepage();
        $this->actingAs(User::factory()->create())->withoutVite();
        $actions = [
            'hero-photo' => ['mode' => 'lightbox', 'url' => ''],
            'editorial-photo' => ['mode' => 'link', 'url' => '/portfolio/zywnosc'],
        ];
        $this->postJson(route('foodfoto.canvas.photo-actions'), [
            'actions' => json_encode($actions),
        ])->assertOk()->assertJsonPath('actions.editorial-photo.url', '/portfolio/zywnosc');
        $this->assertSame($actions, json_decode(
            SiteSetting::where('key', 'foodfoto_canvas_draft_photo_actions')->value('value'), true
        ));
        $this->get(route('foodfoto.canvas'))->assertOk()->assertSee('Powiększ zdjęcie');
        $this->postJson(route('foodfoto.canvas.photo-actions'), [
            'actions' => json_encode(['hero-photo'=>['mode'=>'link','url'=>'https://example.com']]),
        ])->assertStatus(422);
    }

    public function test_layout_ajax_save_returns_confirmation_and_persists_positions(): void
    {
        $this->homepage();
        $this->actingAs(User::factory()->create());
        $layout=['hero-text'=>['x'=>32,'y'=>5,'w'=>0,'z'=>0]];
        $this->postJson(route('foodfoto.canvas.layout'),[
            'layout'=>json_encode($layout),
        ])->assertOk()->assertJsonPath('positions.hero-text.x',32);
        $this->assertSame($layout,json_decode(
            SiteSetting::where('key','foodfoto_canvas_draft_positions')->value('value'),true
        ));
    }

    public function test_builder_has_one_collapsible_tool_sidebar(): void
    {
        $template=file_get_contents(resource_path('views/admin/foodfoto-home-preview.blade.php'));
        $this->assertStringContainsString("details.className='ff-tool-section'", $template);
        $this->assertStringContainsString("panel.appendChild(holder)", $template);
        $this->assertStringContainsString("ff-sidebar-toggle", $template);
    }

    public function test_advanced_typography_persists_and_renders_all_new_options(): void
    {
        $this->homepage();
        $this->actingAs(User::factory()->create())->withoutVite();
        $style = [
            'font_size'=>55.5,'line_height'=>1.25,'letter_spacing'=>-1.25,
            'font_family'=>'Georgia','font_weight'=>700,'text_align'=>'center',
            'text_transform'=>'uppercase','word_spacing'=>2.5,
            'margin_top'=>14,'margin_bottom'=>18,'max_width'=>450,
        ];
        $this->postJson(route('foodfoto.canvas.typography'), [
            'typography'=>json_encode(['preview_process_title'=>$style]),
        ])->assertOk()->assertJsonPath('typography.preview_process_title.max_width',450);
        $this->get(route('foodfoto.canvas'))->assertOk()
            ->assertSee('max-width:450px!important',false)
            ->assertSee('font-weight:700!important',false)
            ->assertSee('word-spacing:2.5px!important',false);
        $template=file_get_contents(resource_path('views/admin/foodfoto-home-preview.blade.php'));
        $this->assertStringContainsString('ff-type-select-box',$template);
    }

    public function test_legacy_typography_without_new_keys_does_not_crash_canvas(): void
    {
        $this->homepage();
        $this->actingAs(User::factory()->create())->withoutVite();
        SiteSetting::updateOrCreate(['key'=>'foodfoto_canvas_draft_typography'],[
            'value'=>json_encode(['preview_process_title'=>[
                'font_size'=>60,'line_height'=>1.1,'letter_spacing'=>-1,
            ]]),
        ]);
        $this->get(route('foodfoto.canvas'))->assertOk()
            ->assertSee('text-align:inherit!important',false)
            ->assertSee('text-transform:none!important',false);
    }

    public function test_custom_font_upload_is_private_and_accepted_only_for_supported_signatures(): void
    {
        \Illuminate\\Support\\Facades\\Storage::fake('public');
        $this->homepage();
        $valid = \Illuminate\\Http\\UploadedFile::fake()->createWithContent('example.woff2', 'wOF2'.str_repeat("x", 60));
        $this->post(route('foodfoto.canvas.fonts'), ['font' => $valid])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->withoutVite();
        $valid = \Illuminate\\Http\\UploadedFile::fake()->createWithContent('example.woff2', 'wOF2'.str_repeat("x", 60));
        $this->postJson(route('foodfoto.canvas.fonts'), ['font' => $valid])
            ->assertOk()->assertJsonPath('success', true);
        $this->get(route('foodfoto.canvas'))->assertOk()->assertSee('example (własna)');
        $bad = \Illuminate\\Http\\UploadedFile::fake()->createWithContent('malicious.woff2', 'NOT-A-FONT');
        $this->postJson(route('foodfoto.canvas.fonts'), ['font' => $bad])->assertStatus(422);
    }

}
