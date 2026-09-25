<?php

namespace Tests\Feature;

use App\Models\{Gallery, Page, Photo, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThumbnailGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
    }

    private function page(): Page
    {
        return Page::create(['title' => 'Klienci', 'slug' => 'klienci', 'published' => true]);
    }

    private function photo(string $name): Photo
    {
        Storage::disk('public')->put('photos/'.$name.'.png', 'unchanged file '.$name);
        return Photo::create(['filename' => 'photos/'.$name.'.png', 'title' => 'Firma '.$name, 'alt' => 'Logo '.$name]);
    }

    private function save(Page $page, array $elements)
    {
        return $this->postJson(route('pages.builder.save', $page), ['content' => ['version' => 1, 'sections' => $elements]]);
    }

    private function grid(string $html): \DOMElement
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return (new \DOMXPath($dom))->query('//div[@class="thumbnail-gallery-grid"]')->item(0);
    }

    public function test_new_block_saves_ordered_ids_and_reopens_with_live_library_without_changing_old_gallery(): void
    {
        $page = $this->page();
        $one = $this->photo('one'); $two = $this->photo('two');
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        $gallery->photos()->attach($one, ['sort_order' => 4]);
        $old = ['id' => 'old', 'type' => 'gallery', 'gallery_mode' => 'single', 'gallery_id' => $gallery->id];
        $element = ['id' => 'logos', 'type' => 'thumbnail_gallery', 'photo_ids' => [$two->id, $one->id],
            'columns_desktop' => 6, 'columns_tablet' => 4, 'columns_mobile' => 2,
            'gap' => 16];
        $before = Photo::all()->toJson(); $pivot = DB::table('gallery_photo')->get()->toJson();
        $this->save($page, [$old, $element])->assertOk();
        $this->assertSame([$old, $element], $page->fresh()->builder->content['sections']);
        $this->get(route('pages.builder', $page))->assertOk()
            ->assertSee('data-fve-add="thumbnail_gallery"', false)->assertSee('Galeria miniaturek')
            ->assertSee('"photo_ids":['.$two->id.','.$one->id.']', false)
            ->assertSee('js/photo-picker.js')->assertSee('id="photo-library-dialog"', false);
        $this->assertSame($before, Photo::all()->toJson());
        $this->assertSame($pivot, DB::table('gallery_photo')->get()->toJson());
        $this->assertSame(['photos/one.png', 'photos/two.png'], Storage::disk('public')->allFiles());
    }

    public function test_removing_or_reordering_references_does_not_change_library_files_or_other_blocks(): void
    {
        $page = $this->page();
        $one = $this->photo('one'); $two = $this->photo('two');
        $block = ['type' => 'thumbnail_gallery', 'photo_ids' => [$one->id, $two->id]];
        $other = ['id' => 'other', 'type' => 'thumbnail_gallery', 'photo_ids' => [$one->id]];
        $this->save($page, [$block, $other])->assertOk();
        $block['photo_ids'] = [$two->id, $one->id];
        $this->save($page, [$block, $other])->assertOk();
        $this->get(route('page.public', $page))->assertSeeInOrder(['photos/two.png', 'photos/one.png']);
        $block['photo_ids'] = [$two->id];
        $this->save($page, [$block, $other])->assertOk();
        $this->assertSame([$block, $other], $page->fresh()->builder->content['sections']);
        $this->assertDatabaseCount('photos', 2);
        $this->assertSame('unchanged file one', Storage::disk('public')->get('photos/one.png'));
        $this->assertSame('unchanged file two', Storage::disk('public')->get('photos/two.png'));
    }

    public function test_public_grid_is_noninteractive_uses_live_alt_and_preserves_id_order(): void
    {
        $page = $this->page();
        $one = $this->photo('one'); $two = $this->photo('two');
        $two->update(['thumbnail' => 'two-small.png', 'alt' => 'Nowy ALT "logo"']);
        $one->update(['alt' => null]);
        $this->save($page, [['type' => 'thumbnail_gallery', 'photo_ids' => [$two->id, $one->id]]])->assertOk();
        $html = $this->get(route('page.public', $page))->assertOk()->assertDontSee('id="lightbox"', false)->getContent();
        $grid = $this->grid($html);
        $images = $grid->getElementsByTagName('img');
        $this->assertCount(2, $images);
        $this->assertSame($two->thumbnailUrl(), $images[0]->getAttribute('src'));
        $this->assertSame('Nowy ALT "logo"', $images[0]->getAttribute('alt'));
        $this->assertSame('Firma one', $images[1]->getAttribute('alt'));
        $this->assertCount(0, $grid->getElementsByTagName('a'));
        $this->assertCount(0, $grid->getElementsByTagName('button'));
        $markup = $grid->ownerDocument->saveHTML($grid);
        foreach (['onclick', 'tabindex', 'data-photo-url', 'gallery-item', 'slider', 'carousel', 'lightbox'] as $attribute) {
            $this->assertStringNotContainsString($attribute, $markup);
        }
        $this->assertStringContainsString('--tg-desktop:5;--tg-tablet:3;--tg-mobile:2;', $markup);
        $this->assertStringContainsString('--tg-gap:20px;', $markup);
        $this->assertStringNotContainsString('--tg-height', $markup);
        $this->assertStringNotContainsString('--tg-fit', $markup);
    }

    public function test_settings_render_and_invalid_settings_cannot_overwrite_saved_content(): void
    {
        $page = $this->page(); $photo = $this->photo('one');
        $element = ['type' => 'thumbnail_gallery', 'photo_ids' => [$photo->id], 'columns_desktop' => 7,
            'columns_tablet' => 4, 'columns_mobile' => 1, 'gap' => 0, 'thumbnail_height' => 180, 'image_fit' => 'cover'];
        $this->save($page, [$element])->assertOk();
        $html = $this->get(route('page.public', $page))->assertOk()->getContent();
        // Legacy sizing values stay saved, but no longer affect the public view.
        $this->assertSame('--tg-desktop:7;--tg-tablet:4;--tg-mobile:1;--tg-gap:0px;', $this->grid($html)->getAttribute('style'));
        foreach (['columns_desktop' => 0, 'columns_tablet' => 13, 'columns_mobile' => 1.5, 'gap' => -1,
            'photo_ids' => [$photo->id, $photo->id]] as $key => $value) {
            $this->save($page, [array_replace($element, [$key => $value])])->assertUnprocessable();
        }
        foreach ([['photos/one.png'], [0], [-1], ['invalid'], 'invalid', ['x' => $photo->id]] as $ids) {
            $this->save($page, [array_replace($element, ['photo_ids' => $ids])])->assertUnprocessable();
        }
        $this->assertSame([$element], $page->fresh()->builder->content['sections']);
    }

    public function test_deleted_photos_and_empty_grids_render_safely_and_allow_resaving(): void
    {
        $page = $this->page(); $one = $this->photo('one'); $two = $this->photo('two');
        $element = ['type' => 'thumbnail_gallery', 'photo_ids' => [$one->id, $two->id]];
        $this->save($page, [$element])->assertOk();
        $this->delete(route('photos.destroy', $one))->assertRedirect();
        $this->get(route('page.public', $page))->assertOk()->assertSee('photos/two.png')->assertDontSee('photos/one.png');
        $this->save($page, [$element])->assertOk();
        $this->delete(route('photos.destroy', $two))->assertRedirect();
        $this->assertCount(0, $this->grid($this->get(route('page.public', $page))->assertOk()->getContent())->getElementsByTagName('img'));
        $this->save($page, [['type' => 'thumbnail_gallery', 'photo_ids' => []]])->assertOk();
        $this->get(route('page.public', $page))->assertOk();
    }

    public function test_new_grid_and_existing_gallery_coexist_with_lightbox_only_on_existing_gallery(): void
    {
        $page = $this->page(); $logo = $this->photo('logo'); $full = $this->photo('full');
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        $gallery->photos()->attach($full);
        $this->save($page, [
            ['type' => 'thumbnail_gallery', 'photo_ids' => [$logo->id]],
            ['type' => 'gallery', 'gallery_mode' => 'single', 'gallery_id' => $gallery->id],
        ])->assertOk();
        $html = $this->get(route('page.public', $page))->assertOk()->assertSee('id="lightbox"', false)
            ->assertSee('data-photo-url="'.$full->imageUrl().'"', false)->getContent();
        $grid = $this->grid($html);
        $this->assertStringNotContainsString('data-photo-url', $grid->ownerDocument->saveHTML($grid));
        $this->assertStringNotContainsString('data-photo-url="'.$logo->imageUrl().'"', $html);
    }

    public function test_home_builder_uses_the_same_validation_and_preserves_new_block_data(): void
    {
        $photo = $this->photo('one');
        $element = ['type' => 'thumbnail_gallery', 'photo_ids' => [$photo->id]];
        $this->postJson(route('home-builder.save'), ['content' => ['sections' => [$element]]])->assertOk();
        $this->assertSame([$element], \App\Models\PageBuilder::where('type', 'home')->first()->content['sections']);
        $this->postJson(route('home-builder.save'), ['content' => ['sections' => [array_replace($element, ['columns_mobile' => 0])]]])->assertUnprocessable();
    }
}
