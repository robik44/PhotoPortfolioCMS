<?php

namespace Tests\Feature;

use App\Models\{Gallery, Page, Photo, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuilderGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_and_legacy_modes_show_gallery_covers_and_links(): void
    {
        $this->withoutVite();
        $page = Page::create(['title' => 'Klienci', 'slug' => 'klienci', 'published' => true]);
        $galleries = collect(['food', 'packaging'])->map(function ($slug) {
            $gallery = Gallery::create(['title' => $slug, 'slug' => $slug]);
            $photo = Photo::create(['filename' => "$slug.jpg"]);
            $gallery->photos()->attach($photo, ['is_cover' => true]);
            return $gallery;
        });
        foreach ([[], ['gallery_mode' => 'all']] as $config) {
            $page->builder()->updateOrCreate([], ['type' => 'page', 'published' => true, 'content' => ['sections' => [['type' => 'gallery'] + $config]]]);
            $response = $this->get(route('page.public', $page))->assertOk();
            foreach ($galleries as $gallery) {
                $response->assertSee(route('portfolio.gallery', $gallery))->assertSee($gallery->slug.'.jpg');
            }
        }
    }

    public function test_selection_saves_reference_renders_live_pivot_order_and_removal_preserves_library(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $page = Page::create(['title' => 'Klienci', 'slug' => 'klienci', 'published' => true]);
        $gallery = Gallery::create(['title' => 'Food', 'slug' => 'food']);
        $other = Gallery::create(['title' => 'Other', 'slug' => 'other']);
        $first = Photo::create(['filename' => 'first.jpg', 'sort_order' => 99]);
        $second = Photo::create(['filename' => 'second.jpg', 'sort_order' => 0]);
        $foreign = Photo::create(['filename' => 'foreign.jpg']);
        $gallery->photos()->attach([$first->id => ['sort_order' => 0], $second->id => ['sort_order' => 1]]);
        $other->photos()->attach($foreign);
        $element = ['type' => 'gallery', 'gallery_mode' => 'single', 'gallery_id' => $gallery->id];
        $this->postJson(route('pages.builder.save', $page), ['content' => ['sections' => [$element]]])->assertOk();
        $this->assertSame($element, $page->builder->content['sections'][0]);
        $this->get(route('pages.builder', $page))->assertOk()->assertSee('Tryb galerii')->assertSee('Wybierz galerię')->assertSee('first.jpg');
        $this->get(route('page.public', $page))->assertOk()->assertSeeInOrder(['first.jpg', 'second.jpg'])->assertDontSee('foreign.jpg')->assertSee('id="lightbox"', false);
        $gallery->photos()->updateExistingPivot($second->id, ['sort_order' => -1]);
        $this->get(route('page.public', $page))->assertSeeInOrder(['second.jpg', 'first.jpg']);
        $pivot = DB::table('gallery_photo')->get()->toJson();
        $this->postJson(route('pages.builder.save', $page), ['content' => ['sections' => []]])->assertOk();
        $this->assertDatabaseCount('galleries', 2);
        $this->assertDatabaseCount('photos', 3);
        $this->assertSame($pivot, DB::table('gallery_photo')->get()->toJson());
    }

    public function test_missing_selection_does_not_fall_back_to_other_galleries(): void
    {
        $this->withoutVite();
        $gallery = Gallery::create(['title' => 'Private selection', 'slug' => 'selection']);
        $page = Page::create(['title' => 'Page', 'slug' => 'page', 'published' => true]);
        $page->builder()->create(['type' => 'page', 'content' => ['sections' => [['type' => 'gallery', 'gallery_mode' => 'single', 'gallery_id' => $gallery->id]]]]);
        $gallery->delete();
        $this->get(route('page.public', $page))->assertOk()->assertDontSee('Private selection');
    }
}
