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
        $this->assertDatabaseHas('site_settings', ['key' => 'foodfoto_canvas_draft_typography', 'value' => $value]);
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
            ->assertSee('id="ff-unified-tools"', false)
            ->assertSee('id="ff-type-panel"', false)
            ->assertSee('id="ff-photo-panel"', false)
            ->assertSee('id="ff-text-toolbar"', false);
        $this->get('/')->assertOk()->assertDontSee('ff-unified-panel');
    }

}
