<?php

namespace Tests\Feature;

use App\Models\{Gallery, Page, Photo, SiteSetting, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
    }

    private function photo(string $name = 'one'): Photo
    {
        Storage::disk('public')->put('photos/'.$name.'.jpg', 'original-'.$name);
        Storage::disk('public')->put('photos/'.$name.'-thumb.jpg', 'thumbnail-'.$name);

        return Photo::create([
            'filename' => 'photos/'.$name.'.jpg', 'thumbnail' => $name.'-thumb.jpg',
            'title' => 'Tytuł '.$name, 'alt' => 'ALT '.$name, 'description' => 'Opis '.$name,
        ]);
    }

    private function page(array $attributes = []): Page
    {
        return Page::create($attributes + ['title' => 'Klienci', 'slug' => 'klienci', 'published' => true]);
    }

    private function savePage(Page $page, array $data = [])
    {
        return $this->put(route('pages.update', $page), $data + [
            'title' => $page->title, 'slug' => $page->slug, 'published' => '1',
        ]);
    }

    public function test_required_forms_share_one_visual_library_with_correct_labels_and_thumbnails(): void
    {
        $photo = $this->photo();
        $page = $this->page(['social_photo_id' => $photo->id, 'featured_photo_id' => $photo->id]);
        $gallery = Gallery::create(['title' => 'Żywność', 'slug' => 'zywnosc', 'social_photo_id' => $photo->id]);
        SiteSetting::create(['key' => 'seo_social_photo_id', 'value' => $photo->id]);

        foreach ([
            [route('seo.edit'), 'Domyślne zdjęcie social', 'seo_social_photo_id', 1],
            [route('pages.edit', $page), 'Zdjęcie social', 'social_photo_id', 2],
            [route('galleries.edit', $gallery), 'Zdjęcie social', 'social_photo_id', 1],
            [route('pages.create'), 'Zdjęcie social', 'social_photo_id', 2],
            [route('galleries.create'), 'Zdjęcie social', 'social_photo_id', 1],
        ] as [$url, $label, $name, $count]) {
            $html = $this->get($url)->assertOk()->assertSee($label)->assertSee($photo->thumbnailUrl())
                ->assertSee('data-library-photo="'.$photo->id.'"', false)
                ->assertSee('data-search="Tytuł one ALT one Opis one"', false)
                ->assertSee('type="hidden" name="'.$name.'"', false)
                ->assertDontSee('<select name="'.$name.'"', false)->getContent();
            $this->assertSame($count, substr_count($html, 'data-photo-picker '));
            $this->assertSame(1, substr_count($html, 'id="photo-library-dialog"'));
            $this->assertSame(1, substr_count($html, 'src="'.asset('js/photo-picker.js').'"'));
            $this->assertStringNotContainsString('>photos/one.jpg<', $html);
        }
        $this->get(route('pages.edit', $page))->assertSee('Zdjęcie wyróżniające')->assertDontSee('type="file"', false);
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_featured_photo_can_be_created_changed_and_detached_without_mutating_library_or_files(): void
    {
        $one = $this->photo();
        $two = $this->photo('two');
        $before = Photo::all()->toJson();
        $files = Storage::disk('public')->allFiles();
        $this->post(route('pages.store'), ['title' => 'Nowa', 'slug' => 'nowa', 'featured_photo_id' => $one->id])
            ->assertSessionHasNoErrors()->assertRedirect();
        $page = Page::where('slug', 'nowa')->firstOrFail();
        $this->assertSame($one->id, $page->featured_photo_id);
        $this->assertSame($one->imageUrl(), $page->featuredImageUrl());
        $this->savePage($page, ['featured_photo_id' => $two->id])->assertSessionHasNoErrors();
        $this->assertSame($two->id, $page->fresh()->featured_photo_id);
        $this->assertSame($two->imageUrl(), $page->fresh()->featuredImageUrl());
        $this->savePage($page, ['featured_photo_id' => '', 'remove_featured_image' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($page->fresh()->featured_photo_id);
        $this->assertNull($page->fresh()->featuredImageUrl());
        $this->assertSame($before, Photo::all()->toJson());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertSame('original-one', Storage::disk('public')->get('photos/one.jpg'));
        $this->assertSame('original-two', Storage::disk('public')->get('photos/two.jpg'));
    }

    public function test_legacy_image_survives_ordinary_save_and_its_file_survives_explicit_replacement_or_removal(): void
    {
        Storage::disk('public')->put('pages/legacy.jpg', 'legacy contents');
        $page = $this->page(['featured_image' => 'pages/legacy.jpg']);
        $this->get(route('pages.edit', $page))->assertOk()->assertSee(asset('storage/pages/legacy.jpg'));
        $this->savePage($page, ['featured_photo_id' => '', 'remove_featured_image' => '0'])->assertSessionHasNoErrors();
        $this->assertSame('pages/legacy.jpg', $page->fresh()->featured_image);
        $this->assertSame(asset('storage/pages/legacy.jpg'), $page->fresh()->featuredImageUrl());
        $photo = $this->photo();
        $this->savePage($page, ['featured_photo_id' => $photo->id, 'remove_featured_image' => '0'])->assertSessionHasNoErrors();
        $this->assertNull($page->fresh()->featured_image);
        $this->assertSame($photo->imageUrl(), $page->fresh()->featuredImageUrl());
        $page->update(['featured_image' => 'pages/legacy.jpg', 'featured_photo_id' => null]);
        $this->savePage($page, ['featured_photo_id' => '', 'remove_featured_image' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($page->fresh()->featuredImageUrl());
        $this->assertSame('legacy contents', Storage::disk('public')->get('pages/legacy.jpg'));
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_validation_errors_retain_submitted_selection_and_explicit_legacy_removal(): void
    {
        $photo = $this->photo();
        $page = $this->page(['featured_image' => 'pages/legacy.jpg']);
        $this->from(route('pages.edit', $page))->savePage($page, ['title' => '', 'featured_photo_id' => $photo->id])
            ->assertSessionHasErrors('title');
        $this->get(route('pages.edit', $page))->assertSee('name="featured_photo_id" value="'.$photo->id.'"', false);
        $this->from(route('pages.edit', $page))->savePage($page, ['title' => '', 'featured_photo_id' => '', 'remove_featured_image' => '1'])
            ->assertSessionHasErrors('title');
        $this->get(route('pages.edit', $page))->assertDontSee(asset('storage/pages/legacy.jpg'))
            ->assertSee('name="remove_featured_image" value="1"', false);
        $this->assertSame('pages/legacy.jpg', $page->fresh()->featured_image);
    }

    public function test_featured_photo_rejects_missing_ids_and_direct_uploads_and_page_deletion_keeps_library(): void
    {
        $photo = $this->photo();
        $page = $this->page(['featured_photo_id' => $photo->id]);
        $this->savePage($page, ['featured_photo_id' => 999999])->assertSessionHasErrors('featured_photo_id');
        $this->savePage($page, ['featured_image' => UploadedFile::fake()->create('upload.jpg', 1, 'image/jpeg')])->assertSessionHasErrors('featured_image');
        $this->assertSame($photo->id, $page->fresh()->featured_photo_id);
        $this->delete(route('pages.destroy', $page))->assertRedirect();
        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        Storage::disk('public')->assertExists('photos/one.jpg');
    }

    public function test_all_social_fields_save_change_and_clear_only_photo_references(): void
    {
        $one = $this->photo(); $two = $this->photo('two');
        $page = $this->page();
        $gallery = Gallery::create(['title' => 'Żywność', 'slug' => 'zywnosc']);
        $before = Photo::all()->toJson();
        $files = Storage::disk('public')->allFiles();
        foreach ([$one->id, $two->id, ''] as $id) {
            $this->put(route('seo.update'), ['seo_social_photo_id' => $id, 'seo_indexable' => '0'])->assertSessionHasNoErrors();
            $this->savePage($page, ['social_photo_id' => $id])->assertSessionHasNoErrors();
            $this->put(route('galleries.update', $gallery), ['title' => $gallery->title, 'social_photo_id' => $id])->assertSessionHasNoErrors();
            $this->assertEquals($id ?: null, SiteSetting::where('key', 'seo_social_photo_id')->value('value'));
            $this->assertSame($id ?: null, $page->fresh()->social_photo_id);
            $this->assertSame($id ?: null, $gallery->fresh()->social_photo_id);
        }
        $this->assertSame($before, Photo::all()->toJson());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('site_settings', ['key' => 'seo_indexable', 'value' => '0']);
    }

    public function test_gallery_view_site_links_use_canonical_slug_url(): void
    {
        $gallery = Gallery::create(['title' => 'Żywność', 'slug' => 'zywnosc']);
        $html = $this->get(route('galleries.edit', $gallery))->assertOk()->getContent();
        preg_match_all('/<a\b[^>]*href="([^"]+)"[^>]*>\s*(?:<span>↗<\/span>\s*)?(?:<span>)?Zobacz stronę/u', $html, $matches);
        $this->assertCount(2, $matches[1]);
        foreach ($matches[1] as $url) $this->assertSame(url('/portfolio/zywnosc'), $url);
    }
}
