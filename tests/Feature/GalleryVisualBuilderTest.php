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
            ->assertSee('Opublikuj układ')
            ->assertSee('Jeden układ komputerowy dla wszystkich ekranów')
            ->assertDontSee('data-gv-device="tablet"', false)
            ->assertDontSee('data-gv-device="mobile"', false);
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
            ->assertSee('content="width=1400"', false)
            ->assertSee('width:1200px;min-width:1200px')
            ->assertDontSee('@media(max-width:900px)')
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
    public function test_free_photo_positions_are_saved_as_id_keyed_map_without_null_slots(): void
    {
        $gallery = $this->gallery();
        $photo = Photo::query()->forceCreate([
            'id' => 90,
            'filename' => 'photos/test-food.jpg',
            'title' => 'Zdjęcie jedzenia',
            'alt' => 'Zupa na talerzu',
        ]);
        $gallery->photos()->attach($photo->id, ['sort_order' => 0, 'is_cover' => true]);
        $this->actingAs(User::factory()->create());

        $layout = GalleryVisualLayout::defaults();
        $layout['photo_mode'] = 'free';
        // Reproduce older browsers: JSON.stringify emitted [null, ...,
        // {x,y,w,h}] when the array was indexed by a photo ID.
        $layout['individual_photos'] = array_fill(0, 91, null);
        $layout['individual_photos'][90] = ['x' => 18.25, 'y' => 480.5, 'w' => 22, 'h' => 250];

        $this->save($gallery, 'gallery', 'save', $layout)
            ->assertRedirect()->assertSessionHasNoErrors();

        $settings = SiteSetting::pluck('value', 'key')->all();
        $draft = GalleryVisualLayout::draft($settings, 'gallery', $gallery->id);
        $this->assertSame(18.25, $draft['individual_photos'][90]['x']);
        $this->assertSame(480.5, $draft['individual_photos'][90]['y']);
        $this->assertCount(1, $draft['individual_photos']);

        // Saving a draft leaves all live gallery content untouched.
        $this->assertSame('Fotografia żywności', $gallery->fresh()->title);
        $this->assertSame('Zupa na talerzu', $photo->fresh()->alt);
        $this->assertNull(GalleryVisualLayout::published($settings, $gallery->id));
    }


    public function test_shared_draft_is_previewed_in_both_gallery_editors_but_not_public_pages(): void
    {
        $first = $this->gallery();
        $second = Gallery::create([
            'title' => 'Opakowania',
            'slug' => 'opakowania',
            'description' => 'Fotografia produktów na opakowania',
            'seo_title' => 'Fotografia opakowań | Magda Gugała',
        ]);
        $this->actingAs(User::factory()->create());
        $pageOne = $this->get(route('portfolio.gallery', $first))->assertOk()->getContent();
        $pageTwo = $this->get(route('portfolio.gallery', $second))->assertOk()->getContent();

        $shared = GalleryVisualLayout::defaults();
        $shared['blocks']['title']['font'] = 'bodoni';
        $shared['blocks']['title']['size'] = 55;
        $this->save($first, 'global', 'save', $shared)->assertRedirect()->assertSessionHasNoErrors();

        $settings = SiteSetting::pluck('value', 'key')->all();
        $this->assertTrue(GalleryVisualLayout::hasUnpublishedSharedChanges($settings));
        $this->assertSame('bodoni', GalleryVisualLayout::draft($settings, 'gallery', $first->id)['blocks']['title']['font']);
        $this->assertSame('bodoni', GalleryVisualLayout::draft($settings, 'gallery', $second->id)['blocks']['title']['font']);

        $this->get(route('galleries.builder', ['gallery'=>$first->id, 'scope'=>'gallery']))
            ->assertOk()->assertSee('nieopublikowany wspólny szkic');
        $this->get(route('galleries.builder', ['gallery'=>$second->id, 'scope'=>'gallery']))
            ->assertOk()->assertSee('nieopublikowany wspólny szkic')
            ->assertSee('Opakowania')
            ->assertSee('Fotografia produktów na opakowania');

        $this->assertSame($pageOne, $this->get(route('portfolio.gallery', $first))->getContent());
        $this->assertSame($pageTwo, $this->get(route('portfolio.gallery', $second))->getContent());
        $this->assertSame('Fotografia żywności | Magda Gugała', $first->fresh()->seo_title);
        $this->assertSame('Fotografia opakowań | Magda Gugała', $second->fresh()->seo_title);
        $this->assertNull(GalleryVisualLayout::published($settings, $first->id));
        $this->assertNull(GalleryVisualLayout::published($settings, $second->id));
    }

    public function test_gallery_changes_stay_local_and_do_not_freeze_new_shared_draft(): void
    {
        $first = $this->gallery();
        $second = Gallery::create([
            'title'=>'Food Styling','slug'=>'stylizacja','description'=>'Stylizacja potraw',
        ]);
        $this->actingAs(User::factory()->create());

        $shared = GalleryVisualLayout::defaults();
        $shared['blocks']['title']['font'] = 'bodoni';
        $shared['blocks']['title']['size'] = 48;
        $this->save($first, 'global', 'save', $shared)->assertRedirect()->assertSessionHasNoErrors();

        $local = $shared;
        $local['blocks']['title']['size'] = 56;
        $this->save($first, 'gallery', 'save', $local)->assertRedirect()->assertSessionHasNoErrors();

        $shared['blocks']['description']['size'] = 21;
        $shared['blocks']['title']['font'] = 'inter';
        $this->save($first, 'global', 'save', $shared)->assertRedirect()->assertSessionHasNoErrors();

        $settings = SiteSetting::pluck('value', 'key')->all();
        $firstDraft = GalleryVisualLayout::draft($settings, 'gallery', $first->id);
        $secondDraft = GalleryVisualLayout::draft($settings, 'gallery', $second->id);
        $this->assertSame(56.0, (float) $firstDraft['blocks']['title']['size']);
        $this->assertSame(48.0, (float) $secondDraft['blocks']['title']['size']);
        $this->assertSame('inter', $firstDraft['blocks']['title']['font']);
        $this->assertSame('inter', $secondDraft['blocks']['title']['font']);
        $this->assertSame(21.0, (float) $firstDraft['blocks']['description']['size']);

        $localOverride = GalleryVisualLayout::read($settings, GalleryVisualLayout::key('gallery','draft',$first->id));
        $this->assertSame(56.0, (float) $localOverride['blocks']['title']['size']);
        $this->assertArrayNotHasKey('font', $localOverride['blocks']['title']);
        $this->assertNull(GalleryVisualLayout::read($settings, GalleryVisualLayout::key('gallery','draft',$second->id)));
    }

    public function test_local_publication_is_blocked_until_pending_shared_template_is_published(): void
    {
        $gallery = $this->gallery();
        $this->actingAs(User::factory()->create());
        $shared = GalleryVisualLayout::defaults();
        $shared['blocks']['title']['size'] = 58;
        $this->save($gallery,'global','save',$shared)->assertRedirect()->assertSessionHasNoErrors();

        $local = $shared;
        $local['blocks']['description']['color'] = '#ff0000';
        $this->save($gallery,'gallery','publish',$local)->assertSessionHasErrors('layout');
        $this->assertNull(GalleryVisualLayout::published(SiteSetting::pluck('value','key')->all(),$gallery->id));

        // Save is still allowed, and does not affect the public site.
        $this->save($gallery,'gallery','save',$local)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull(GalleryVisualLayout::published(SiteSetting::pluck('value','key')->all(),$gallery->id));
    }


}
