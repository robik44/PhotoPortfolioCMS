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
}
