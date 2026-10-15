<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\GalleryVisualLayout;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GalleryVisualBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->withoutVite();
    }

    private function gallery(string $slug = 'zywnosc'): Gallery
    {
        return Gallery::create([
            'title' => 'Fotografia żywności',
            'slug' => $slug,
            'description' => 'Profesjonalne zdjęcia potraw',
            'seo_title' => 'Fotografia żywności | Magda Gugała',
        ]);
    }

    private function save(Gallery $gallery, string $scope, string $action, array $data)
    {
        return $this->post(route('galleries.builder.save', $gallery), [
            'scope' => $scope, 'action' => $action,
            'layout' => json_encode($data, JSON_THROW_ON_ERROR),
        ]);
    }

    public function test_builder_requires_login_and_shows_live_gallery_data(): void
    {
        $gallery = $this->gallery();
        $this->get(route('galleries.builder', $gallery))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->get(route('galleries.builder', $gallery))
            ->assertOk()
            ->assertSee('Wizualny builder galerii')
            ->assertSee('Fotografia żywności')
            ->assertSee('Zapisz szkic')
            ->assertSee('Opublikuj układ');
    }

    public function test_saving_draft_does_not_change_public_gallery_or_seo(): void
    {
        $gallery = $this->gallery();
        $this->actingAs(User::factory()->create());
        $original = $this->get(route('portfolio.gallery', $gallery))->assertOk()->getContent();
        $draft = GalleryVisualLayout::defaults();
        $draft['blocks']['title']['size'] = 62;
        $this->save($gallery, 'global', 'save', $draft)->assertRedirect();
        $this->assertSame($original, $this->get(route('portfolio.gallery', $gallery))->getContent());
        $this->assertDatabaseHas('site_settings', ['key' => GalleryVisualLayout::key('global', 'draft')]);
        $this->assertDatabaseMissing('site_settings', ['key' => GalleryVisualLayout::key('global', 'published')]);
        $this->assertSame('Fotografia żywności | Magda Gugała', $gallery->fresh()->seo_title);
    }

    public function test_publishing_shared_canvas_preserves_photo_metadata_lightbox_and_seo(): void
    {
        $gallery = $this->gallery();
        $photo = Photo::create(['filename' => 'photos/soup.jpg', 'title' => 'Zupa', 'alt' => 'Zupa pomidorowa', 'description' => 'Opis potrawy']);
        $gallery->photos()->attach($photo->id, ['sort_order' => 0, 'is_cover' => true]);
        $pivotBefore = DB::table('gallery_photo')->get()->toJson();
        $this->actingAs(User::factory()->create());
        $draft = GalleryVisualLayout::defaults();
        $draft['blocks']['title']['size'] = 54;
        $this->save($gallery, 'global', 'publish', $draft)->assertRedirect();
        $public = $this->get(route('portfolio.gallery', $gallery))->assertOk()
            ->assertSee('gv-public-canvas')
            ->assertSee('data-gv-id="title"', false)
            ->assertSee('Zupa pomidorowa')
            ->assertSee('data-photo-title="Zupa"', false)
            ->assertSee('gallery-item')
            ->assertSee('id="lightbox"', false)
            ->assertSee('Fotografia żywności | Magda Gugała');
        $this->assertSame($pivotBefore, DB::table('gallery_photo')->get()->toJson());
        $this->assertSame('Zupa pomidorowa', $photo->fresh()->alt);
    }

    public function test_individual_override_preserves_global_inheritance(): void
    {
        $gallery = $this->gallery();
        $this->actingAs(User::factory()->create());
        $global = GalleryVisualLayout::defaults();
        $this->save($gallery, 'global', 'publish', $global)->assertRedirect();
        $local = $global;
        $local['blocks']['title']['size'] = 59;
        $this->save($gallery, 'gallery', 'publish', $local)->assertRedirect();
        $global['blocks']['description']['color'] = '#ff0000';
        $this->save($gallery, 'global', 'publish', $global)->assertRedirect();
        $settings = SiteSetting::pluck('value', 'key')->all();
        $layout = GalleryVisualLayout::published($settings, $gallery->id);
        $this->assertSame(59, $layout['blocks']['title']['size']);
        $this->assertSame('#ff0000', $layout['blocks']['description']['color']);
    }

    public function test_title_and_description_remain_drafts_until_publish(): void
    {
        $gallery = $this->gallery();
        $this->actingAs(User::factory()->create());
        $data = GalleryVisualLayout::defaults();
        $data['content'] = [
            'title' => 'Autorska fotografia potraw',
            'description' => 'Zmieniony, starannie przygotowany opis.',
        ];
        $this->save($gallery, 'gallery', 'save', $data)->assertRedirect();
        $this->assertSame('Fotografia żywności', $gallery->fresh()->title);
        $this->save($gallery, 'gallery', 'publish', $data)->assertRedirect();
        $this->assertSame('Autorska fotografia potraw', $gallery->fresh()->title);
        $this->assertSame('Zmieniony, starannie przygotowany opis.', $gallery->fresh()->description);
        $this->assertSame('Fotografia żywności | Magda Gugała', $gallery->fresh()->seo_title);
        $this->assertSame('zywnosc', $gallery->fresh()->slug);
    }

    public function test_editor_cannot_use_unassigned_photos_and_rejects_invalid_layout(): void
    {
        $gallery = $this->gallery();
        $otherPhoto = Photo::create(['filename' => 'photos/not-assigned.jpg']);
        $this->actingAs(User::factory()->create());
        $bad = GalleryVisualLayout::defaults();
        $bad['extras'][] = [
            'id' => 'extra-image-12345', 'type' => 'image', 'x' => 5, 'y' => 800, 'w' => 30,
            'h' => 220, 'photo_id' => $otherPhoto->id, 'text' => '', 'url' => '',
            'font' => 'sans', 'size' => 18, 'tablet' => 18, 'mobile' => 16,
            'weight' => 400, 'leading' => 1.5, 'spacing' => 0, 'color' => '#222222',
        ];
        $this->save($gallery, 'gallery', 'publish', $bad)->assertSessionHasErrors('layout');
        $this->assertNull(GalleryVisualLayout::published(SiteSetting::pluck('value', 'key')->all(), $gallery->id));
        $bad = GalleryVisualLayout::defaults();
        $bad['blocks']['title']['x'] = -700;
        $this->save($gallery, 'global', 'publish', $bad)->assertSessionHasErrors('layout');
    }
}
