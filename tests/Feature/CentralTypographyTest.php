<?php

namespace Tests\Feature;

use App\Models\{Gallery, Page, Photo, SiteSetting, User};
use App\Services\SiteFontLibrary;
use App\Support\{GalleryTypography, TypographySettings};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class CentralTypographyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Always use a fresh disposable SQLite database so tests do not depend on
        // development data committed in database/database.sqlite.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Storage::fake('public');
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_one_upload_is_available_everywhere_and_all_four_text_settings_persist(): void
    {
        $beforeFonts = (new SiteFontLibrary)->catalog()['fonts'];
        $this->post(route('fonts.store'), ['font_file' => UploadedFile::fake()->createWithContent(
            'Central test.ttf', file_get_contents(__DIR__.'/../Fixtures/header-test.ttf')
        )])->assertSessionHasNoErrors();
        $catalog = (new SiteFontLibrary)->catalog();
        $id = array_key_first(array_diff_key($catalog['fonts'], $beforeFonts));
        $this->assertNotNull($id);
        foreach ($beforeFonts as $oldId => $font) $this->assertSame($font, $catalog['fonts'][$oldId]);
        Storage::disk('public')->assertExists($catalog['fonts'][$id]['path']);
        $this->assertCount(1, Storage::disk('public')->allFiles('fonts'));
        // A fresh database connection and service must see the persisted registry.
        DB::purge('sqlite');
        $this->assertSame($catalog, (new SiteFontLibrary)->catalog());

        $gallery = Gallery::create(['title' => 'Typography test', 'slug' => 'typography-test', 'description' => 'Gallery description', 'published' => true]);
        $photo = Photo::create(['filename' => 'test.jpg', 'title' => 'Photo title', 'description' => 'Photo description', 'alt' => 'Photo ALT']);
        $gallery->photos()->attach($photo, ['sort_order' => 5, 'is_cover' => true]);
        $page = Page::create(['title' => 'Builder test', 'slug' => 'typography-builder', 'published' => true]);
        foreach ([route('fonts.index'), route('header-settings.edit'), route('galleries.edit', $gallery), route('photos.edit', $photo), route('pages.builder', $page), route('home-builder.edit')] as $url) {
            $this->get($url)->assertOk()->assertSee($id);
        }
        $baseline = $this->get(route('portfolio.gallery', $gallery))->assertOk()->getContent();
        $galleryData = ['title' => $gallery->title, 'description' => $gallery->description, 'title_font_family' => $id, 'title_font_size' => 41, 'description_font_family' => 'Georgia', 'description_font_size' => 23];
        $photoData = ['title' => $photo->title, 'description' => $photo->description, 'alt' => $photo->alt, 'title_font_family' => 'Verdana', 'title_font_size' => 21, 'description_font_family' => $id, 'description_font_size' => 19];
        $this->put(route('galleries.update', $gallery), $galleryData)->assertSessionHasNoErrors();
        $this->put(route('photos.update', $photo), $photoData)->assertSessionHasNoErrors();
        $this->get(route('galleries.edit', $gallery))->assertOk()->assertSee('value="41"', false)->assertSee('value="23"', false);
        $edit = $this->get(route('photos.edit', $photo))->assertOk()->assertSee('value="21"', false)->assertSee('value="19"', false)->assertDontSee('alt_font_family')->assertDontSee('alt_font_size');
        foreach (['title', 'description'] as $field) {
            $this->assertSame($photoData[$field.'_font_family'], $edit->viewData('photoTypography')[$field.'_font_family']);
        }
        $public = $this->get(route('portfolio.gallery', $gallery))->assertOk();
        $public->assertSee('font-family:'.SiteFontLibrary::css($id, $catalog).';font-size:41px;', false)
            ->assertSee('font-family:Georgia, serif;font-size:23px;', false)
            ->assertSee('font-family:Verdana, sans-serif;font-size:21px;', false)
            ->assertSee('font-family:'.SiteFontLibrary::css($id, $catalog).';font-size:19px;', false)
            ->assertSee('data-title-font-size="21px"', false)->assertSee('data-description-font-size="19px"', false)
            ->assertSee('storage/'.$catalog['fonts'][$id]['path'], false)->assertSee("format('truetype')", false);
        preg_match_all('/<img\b[^>]*>/s', $baseline, $beforeImages);
        preg_match_all('/<img\b[^>]*>/s', $public->getContent(), $afterImages);
        $this->assertSame($beforeImages[0], $afterImages[0]);
        $this->assertSame(5, $gallery->fresh()->photos->first()->pivot->sort_order);
        $this->assertSame('Photo ALT', $photo->fresh()->alt);

        $layout = ['sections' => [['type' => 'text', 'content' => 'Shared font', 'style' => ['font_family' => $id]], ['type' => 'gallery', 'gallery_mode' => 'single', 'gallery_id' => $gallery->id]]];
        $this->postJson(route('pages.builder.save', $page), ['content' => $layout])->assertOk();
        $this->assertSame($layout, $page->fresh()->builder->content);
        $this->get(route('page.public', $page))->assertOk()->assertSee('font-family:'.$id, false)
            ->assertSee('data-title-font-size="21px"', false)->assertSee('data-description-font-size="19px"', false);
        $layout['sections'][1]['caption_font_family'] = 'Georgia';
        $layout['sections'][1]['caption_font_size'] = 28;
        $this->postJson(route('pages.builder.save', $page), ['content' => $layout])->assertOk();
        $this->get(route('page.public', $page))->assertOk()->assertSee('data-description-font-size="28px"', false)->assertSee('data-description-font-family="Georgia, serif"', false);
    }

    public function test_empty_or_omitted_overrides_preserve_legacy_output_and_invalid_values_do_not_save(): void
    {
        $gallery = Gallery::create(['title' => 'Legacy', 'slug' => 'legacy-typography', 'published' => true]);
        $photo = Photo::create(['filename' => 'legacy.jpg', 'title' => 'Old title', 'description' => 'Old description']);
        $gallery->photos()->attach($photo);
        $url = route('portfolio.gallery', $gallery);
        $before = $this->get($url)->assertOk()->getContent();
        $empty = ['title_font_family' => '', 'description_font_family' => '', 'title_font_size' => '', 'description_font_size' => ''];
        $this->put(route('galleries.update', $gallery), ['title' => 'Legacy'] + $empty)->assertSessionHasNoErrors();
        $this->put(route('photos.update', $photo), ['title' => $photo->title, 'description' => $photo->description] + $empty)->assertSessionHasNoErrors();
        $this->assertSame($before, $this->get($url)->assertOk()->getContent());
        $this->assertDatabaseMissing('site_settings', ['key' => 'photo_'.$photo->id.'_typography']);
        $this->assertDatabaseMissing('site_settings', ['key' => GalleryTypography::key($gallery->id)]);
        foreach (['title_font_family' => 'Arial; color:red', 'description_font_family' => 'unknown', 'title_font_size' => -1, 'description_font_size' => 201] as $field => $value) {
            $this->put(route('galleries.update', $gallery), ['title' => 'Legacy', $field => $value])->assertSessionHasErrors($field);
            $this->put(route('photos.update', $photo), ['title' => $photo->title, $field => $value])->assertSessionHasErrors($field);
        }
        $this->post(route('fonts.store'), ['font_file' => UploadedFile::fake()->createWithContent('unsafe.woff2', '<?php echo 1;')])->assertSessionHasErrors('font_file');
        $this->assertSame($before, $this->get($url)->assertOk()->getContent());
    }
    public function test_gallery_caption_size_and_editable_size_fields_save_reopen_and_reset(): void
    {
        $gallery = Gallery::create(['title' => 'Size test', 'slug' => 'size-test', 'description' => 'Description', 'published' => true]);
        $photo = Photo::create(['filename' => 'size.jpg', 'title' => 'Photo title', 'description' => 'Photo description']);
        $gallery->photos()->attach($photo);
        $publicUrl = route('portfolio.gallery', $gallery);
        $baseline = $this->get($publicUrl)->assertOk()->getContent();
        foreach ([16, 20, 24] as $size) {
            $this->put(route('galleries.update', $gallery), [
                'title' => $gallery->title, 'description' => $gallery->description,
                'title_font_size' => (string) $size, 'description_font_size' => (string) $size, 'caption_font_size' => (string) $size,
            ])->assertSessionHasNoErrors();
            $form = $this->get(route('galleries.edit', $gallery))->assertOk();
            $form->assertSee('Podpisy zdjęć — rozmiar czcionki (px)');
            foreach (['title', 'description', 'caption'] as $field) {
                $this->assertMatchesRegularExpression('/<input[^>]*type="number"[^>]*name="'.$field.'_font_size"[^>]*value="'.$size.'"[^>]*>/', $form->getContent());
                $this->assertSame((string) $size, $form->viewData('galleryFonts')[$field.'_font_size']);
            }
            $public = $this->get($publicUrl)->assertOk();
            $public->assertSee('font-size:'.$size.'px;', false);
            $public->assertSee('#lightbox .lightbox-title, #lightbox .lightbox-description { font-size: '.$size.'px; }', false);
            $this->put(route('photos.update', $photo), [
                'title' => $photo->title, 'description' => $photo->description,
                'title_font_size' => (string) $size, 'description_font_size' => (string) $size,
            ])->assertSessionHasNoErrors();
            $form = $this->get(route('photos.edit', $photo))->assertOk();
            foreach (['title', 'description'] as $field) {
                $this->assertMatchesRegularExpression('/<input[^>]*type="number"[^>]*name="'.$field.'_font_size"[^>]*value="'.$size.'"[^>]*>/', $form->getContent());
            }
            $this->get($publicUrl)->assertOk()->assertSee('data-title-font-size="'.$size.'px"', false)->assertSee('data-description-font-size="'.$size.'px"', false);
        }
        $this->put(route('galleries.update', $gallery), ['title' => $gallery->title, 'description' => $gallery->description, 'title_font_size' => '', 'description_font_size' => '', 'caption_font_size' => ''])->assertSessionHasNoErrors();
        $this->put(route('photos.update', $photo), ['title' => $photo->title, 'description' => $photo->description, 'title_font_size' => '', 'description_font_size' => ''])->assertSessionHasNoErrors();
        $this->assertSame($baseline, $this->get($publicUrl)->assertOk()->getContent());
        $this->get(route('galleries.edit', $gallery))->assertOk()->assertSee('placeholder="Domyślny (bez zmiany)"', false);
        $this->put(route('galleries.update', $gallery), ['title' => $gallery->title, 'caption_font_size' => 'invalid'])->assertSessionHasErrors('caption_font_size');
    }
}
