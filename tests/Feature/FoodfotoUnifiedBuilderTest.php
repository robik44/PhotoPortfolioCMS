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

}
