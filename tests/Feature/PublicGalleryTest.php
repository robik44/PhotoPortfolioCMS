<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\MenuItem;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicGalleryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->withoutVite();
    }

    public function test_public_gallery_has_no_admin_layout_for_guests_or_signed_in_users(): void
    {
        $gallery = Gallery::create(['title' => 'Publiczna galeria', 'slug' => 'publiczna', 'description' => 'Opis publiczny']);

        foreach ([false, true] as $signedIn) {
            if ($signedIn) {
                $this->actingAs(User::factory()->create());
            }

            $response = $this->get(route('portfolio.gallery', $gallery))->assertOk()
                ->assertSee('Publiczna galeria')->assertSee('Opis publiczny')
                ->assertSee('← Powrót do galerii')->assertSee(url('/').'#portfolio')
                ->assertSee('Ta galeria nie zawiera jeszcze zdjęć.')
                ->assertSee('class="site-typography"', false)
                ->assertDontSee('cms-')->assertDontSee('Kokpit')->assertDontSee('Wyloguj')
                ->assertDontSee(route('photos.index'))->assertDontSee(route('galleries.index'));
            $this->assertSame(1, substr_count($response->getContent(), '<!DOCTYPE html>'));
            $this->assertSame(1, substr_count($response->getContent(), '<header '));
        }
    }

    public function test_gallery_uses_same_global_header_menu_submenu_and_typography_as_homepage(): void
    {
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        foreach ([
            'logo' => 'Moje fotografie', 'logo_subtitle' => 'Publiczny nagłówek',
            'header_layout' => 'center', 'header_logo_color' => '#123456',
            'site_body_font_family' => 'Verdana', 'site_heading_font_family' => 'Georgia',
            'background_color' => '#fafafa',
        ] as $key => $value) {
            SiteSetting::create(compact('key', 'value'));
        }
        $parent = MenuItem::create(['title' => 'Fotografie', 'type' => 'url', 'url' => '/#portfolio', 'published' => true]);
        MenuItem::create(['title' => 'Podmenu galerii', 'type' => 'gallery', 'gallery_id' => $gallery->id, 'parent_id' => $parent->id, 'published' => true]);
        MenuItem::create(['title' => 'Ukryte podmenu', 'type' => 'url', 'url' => '/hidden', 'parent_id' => $parent->id, 'published' => false]);

        $home = $this->get('/')->assertOk()->assertSee(route('portfolio.gallery', $gallery));
        $public = $this->get(route('portfolio.gallery', $gallery))->assertOk()
            ->assertSee('Moje fotografie')->assertSee('Publiczny nagłówek')
            ->assertSee('header-layout-center')->assertSee('#123456')->assertSee('#fafafa')
            ->assertSee('Podmenu galerii')->assertDontSee('Ukryte podmenu')
            ->assertSee('Verdana')->assertSee('Georgia');
        preg_match('/<header\b.*?<\/header>/s', $home->getContent(), $homeHeader);
        preg_match('/<header\b.*?<\/header>/s', $public->getContent(), $galleryHeader);
        $this->assertNotEmpty($homeHeader);
        $this->assertSame($homeHeader[0], $galleryHeader[0]);
    }

    public function test_all_photos_follow_pivot_order_and_lightbox_preserves_urls_and_metadata(): void
    {
        $gallery = Gallery::create(['title' => 'Galeria', 'slug' => 'galeria']);
        $expectedUrls = [];
        for ($position = 0; $position < 14; $position++) {
            $photo = Photo::create([
                'filename' => ($position % 2 ? 'photos/' : '')."photo-$position.jpg",
                'title' => 'Tytuł "A" & B', 'alt' => 'Alternatywny opis', 'description' => 'Opis <test> & szczegóły',
                'sort_order' => $position, 'is_cover' => $position === 0,
            ]);
            $gallery->photos()->attach($photo, ['sort_order' => 13 - $position, 'is_cover' => $position === 3]);
            array_unshift($expectedUrls, $photo->imageUrl());
        }
        Photo::create(['filename' => 'unassigned.jpg']);
        $pivotBefore = DB::table('gallery_photo')->orderBy('id')->get()->toJson();

        $response = $this->get(route('portfolio.gallery', $gallery))->assertOk()
            ->assertSeeInOrder($expectedUrls)->assertDontSee('unassigned.jpg')
            ->assertDontSee('photos/photos/')
            ->assertSee('data-photo-title="Tytuł &quot;A&quot; &amp; B"', false)
            ->assertSee('data-photo-description="Opis &lt;test&gt; &amp; szczegóły"', false)
            ->assertSee('data-photo-alt="Alternatywny opis"', false);
        $html = $response->getContent();
        $this->assertSame(14, substr_count($html, 'class="gallery-item"'));
        foreach (['lightbox', 'lightboxImage', 'lightboxClose', 'lightboxPrev', 'lightboxNext'] as $id) {
            $this->assertSame(1, substr_count($html, 'id="'.$id.'"'));
        }
        $this->assertSame($pivotBefore, DB::table('gallery_photo')->orderBy('id')->get()->toJson());
    }
}
