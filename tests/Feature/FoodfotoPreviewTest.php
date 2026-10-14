<?php

namespace Tests\Feature;

use App\Models\PageBuilder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodfotoPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_requires_login(): void
    {
        $this->get('/admin/foodfoto-preview')->assertRedirect('/login');
    }

    public function test_preview_does_not_modify_home_builder(): void
    {
        $sections = array_fill(0, 32, ['type' => 'text', 'content' => 'Fotografia żywności']);
        $sections[3] = ['type' => 'heading', 'content' => 'Co tworzę'];
        $sections[24] = ['type' => 'gallery', 'gallery_mode' => 'selected', 'gallery_ids' => []];
        $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        $sections[31] = ['type' => 'image', 'photo_id' => 96];
        $builder = PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['version' => 1, 'sections' => $sections, 'settings' => []],
        ]);
        $before = $builder->content;
        $this->actingAs(User::factory()->create())
            ->get('/admin/foodfoto-preview')
            ->assertOk()
            ->assertSee('Apetyt')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertSame($before, $builder->fresh()->content);
    }

    public function test_preview_layout_save_requires_authentication(): void
    {
        $this->post('/admin/foodfoto-preview/layout', [
            'layout' => json_encode(['hero-text' => ['x' => 10, 'y' => 20]]),
        ])->assertRedirect('/login');
    }

    public function test_preview_layout_save_preserves_public_home_builder(): void
    {
        $sections = array_fill(0, 32, ['type' => 'text', 'content' => 'Original']);
        $sections[3]['content'] = 'Co tworzę';
        $sections[24] = ['type' => 'gallery'];
        $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        $sections[31] = ['type' => 'image', 'photo_id' => 96];
        $builder = PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['sections' => $sections],
        ]);
        $before = $builder->content;
        $this->actingAs(User::factory()->create())
            ->post('/admin/foodfoto-preview/layout', [
                'layout' => json_encode(['hero-text' => ['x' => 20, 'y' => -15, 'w' => 550, 'z' => 2]]),
            ])->assertRedirect(route('foodfoto.home.preview'));
        $this->assertSame($before, $builder->fresh()->content);
        $this->assertDatabaseHas('site_settings', ['key' => 'preview_editor_positions']);
    }

    public function test_invalid_layout_is_rejected_without_saving(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/foodfoto-preview/layout', [
                'layout' => json_encode(['hero-text' => ['x' => 9000, 'y' => 0]]),
            ])->assertStatus(422);
        $this->assertDatabaseMissing('site_settings', ['key' => 'preview_editor_positions']);
    }

    public function test_publication_requires_explicit_confirmation_and_can_rollback(): void
    {
        $sections = array_fill(0, 32, ['type' => 'text', 'content' => 'Original']);
        $sections[3]['content'] = 'Co tworzę';
        $sections[24] = ['type' => 'gallery', 'gallery_mode' => 'selected', 'gallery_ids' => []];
        $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        $sections[31] = ['type' => 'image', 'photo_id' => 96];
        $builder = PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['sections' => $sections],
        ]);
        $original = $builder->content;
        $this->artisan('foodfoto:publish-home', ['--publish' => true])->assertFailed();
        $this->get('/')->assertOk()->assertDontSee('Przesuwaj elementy');

        $this->artisan('foodfoto:publish-home', [
            '--publish' => true, '--confirm-production' => true,
        ])->assertSuccessful();

        $this->get('/')->assertOk()
            ->assertSee('Apetyt na dobre kadry')
            ->assertDontSee('Przesuwaj elementy')
            ->assertDontSee('foodfoto-edit-open')
            ->assertDontSee('noindex,nofollow,noarchive');

        $this->assertSame($original, $builder->fresh()->content);

        $this->artisan('foodfoto:publish-home', [
            '--rollback' => true, '--confirm-production' => true,
        ])->assertSuccessful();
        $this->get('/')->assertOk()->assertDontSee('Apetyt na dobre kadry');
        $this->assertSame($original, $builder->fresh()->content);
    }

    public function test_publication_rejects_changed_builder_layout(): void
    {
        PageBuilder::create([
            'page_id' => null, 'type' => 'home', 'published' => true,
            'content' => ['sections' => []],
        ]);
        $this->artisan('foodfoto:publish-home', [
            '--publish' => true, '--confirm-production' => true,
        ])->assertFailed();
        $this->assertDatabaseMissing('site_settings', ['key' => 'foodfoto_new_home_published', 'value' => '1']);
    }
}
