<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PhotoLibraryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        Storage::fake('public');
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    private function gallery(string $slug): Gallery
    {
        return Gallery::create(['title' => 'Galeria '.$slug, 'slug' => $slug]);
    }

    private function upload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }

    public function test_library_lists_unassigned_and_shared_photos_with_both_filename_formats(): void
    {
        $unused = Photo::create(['filename' => 'legacy.jpg', 'title' => 'Bez galerii', 'is_cover' => true]);
        $shared = Photo::create(['filename' => 'photos/shared.jpg', 'title' => 'Wspólne zdjęcie']);
        $first = $this->gallery('pierwsza');
        $second = $this->gallery('druga');
        $shared->galleries()->attach([$first->id, $second->id]);

        $this->get(route('photos.index'))->assertOk()
            ->assertSee('Bez galerii')->assertSee('Wspólne zdjęcie')
            ->assertSee('Nie jest używane w żadnej galerii.')
            ->assertSee($first->title)->assertSee($second->title)
            ->assertSee(route('galleries.show', $first))->assertSee(route('galleries.show', $second))
            ->assertSee(asset('storage/photos/legacy.jpg'))->assertSee(asset('storage/photos/shared.jpg'))
            ->assertDontSee('photos/photos/')->assertDontSee('Ustaw jako okładkę')->assertDontSee('Okładka')
            ->assertSee('trwale usunięte z Biblioteki i wszystkich galerii')
            ->assertSee(route('photos.edit', $unused));
    }

    public function test_edit_form_works_for_both_filename_formats_and_shows_every_gallery(): void
    {
        $first = $this->gallery('a');
        $second = $this->gallery('b');

        foreach (['legacy.jpg', 'photos/current.jpg'] as $filename) {
            $photo = Photo::create(['filename' => $filename, 'title' => 'Tytuł', 'alt' => 'Opis alt', 'description' => 'Opis zdjęcia']);
            $photo->galleries()->attach([$first->id, $second->id]);
            $this->get(route('photos.edit', $photo))->assertOk()
                ->assertSee(asset('storage/photos/'.basename($filename)))
                ->assertSee('Tytuł')->assertSee('Opis alt')->assertSee('Opis zdjęcia')
                ->assertSee($first->title)->assertSee($second->title)
                ->assertSee('wspólne dla wszystkich galerii')
                ->assertDontSee('name="gallery_id"', false)->assertDontSee('Ustaw jako okładkę');
        }
    }

    public function test_update_only_changes_shared_metadata_and_preserves_pivot_and_legacy_fields(): void
    {
        $first = $this->gallery('a');
        $second = $this->gallery('b');
        $photo = Photo::create(['filename' => 'legacy.jpg', 'gallery_id' => $first->id, 'sort_order' => 91, 'is_cover' => true]);
        $photo->galleries()->attach([$first->id => ['sort_order' => 4, 'is_cover' => true], $second->id => ['sort_order' => 8, 'is_cover' => false]]);
        $before = DB::table('gallery_photo')->orderBy('id')->get()->toJson();

        $this->put(route('photos.update', $photo), [
            'title' => 'Nowy tytuł', 'alt' => 'Nowy alt', 'description' => 'Nowy opis',
            'filename' => 'other.jpg', 'gallery_id' => $second->id, 'is_cover' => false, 'sort_order' => 0,
        ])->assertRedirect(route('photos.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id, 'filename' => 'legacy.jpg', 'gallery_id' => $first->id,
            'sort_order' => 91, 'is_cover' => true, 'title' => 'Nowy tytuł', 'alt' => 'Nowy alt', 'description' => 'Nowy opis',
        ]);
        $this->assertSame($before, DB::table('gallery_photo')->orderBy('id')->get()->toJson());
        $this->assertSame('Nowy tytuł', $first->photos()->first()->title);
        $this->assertSame('Nowy tytuł', $second->photos()->first()->title);
    }

    public function test_metadata_validation_preserves_input_and_accepts_empty_fields(): void
    {
        $photo = Photo::create(['filename' => 'photo.jpg', 'title' => 'Poprzedni tytuł']);
        $this->from(route('photos.edit', $photo))->put(route('photos.update', $photo), [
            'title' => str_repeat('a', 256), 'alt' => str_repeat('b', 256), 'description' => 'Zachowaj ten opis',
        ])->assertRedirect(route('photos.edit', $photo))->assertSessionHasErrors(['title', 'alt']);
        $this->get(route('photos.edit', $photo))->assertOk()->assertSee('Zachowaj ten opis');
        $this->assertSame('Poprzedni tytuł', $photo->fresh()->title);

        $this->put(route('photos.update', $photo), ['title' => '', 'alt' => '', 'description' => ''])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('photos', ['id' => $photo->id, 'title' => null, 'alt' => null, 'description' => null]);
    }

    public function test_multiple_uploads_create_library_photos_without_any_gallery(): void
    {
        $gallery = $this->gallery('a');
        $this->get(route('photos.create'))->assertOk()->assertSee('multiple')->assertDontSee('name="gallery_id"', false);
        $this->post(route('photos.store'), [
            'images' => [$this->upload('first.png'), $this->upload('second.png')], 'gallery_id' => $gallery->id,
        ])->assertRedirect(route('photos.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('photos', 2);
        $this->assertDatabaseCount('gallery_photo', 0);
        foreach (Photo::all() as $photo) {
            $this->assertNull($photo->gallery_id);
            $this->assertStringStartsWith('photos/', $photo->filename);
            Storage::disk('public')->assertExists($photo->filename);
        }
    }

    public function test_invalid_upload_batch_saves_no_files_or_records(): void
    {
        $this->post(route('photos.store'), ['images' => [
            $this->upload('valid.png'), UploadedFile::fake()->createWithContent('bad.txt', 'not an image'),
        ]])->assertSessionHasErrors('images.1');
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_database_write_cleans_up_entire_upload_batch(): void
    {
        $created = 0;
        Photo::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new RuntimeException('Simulated database write failure.');
            }
        });

        try {
            $this->from(route('photos.create'))->post(route('photos.store'), [
                'images' => [$this->upload('first.png'), $this->upload('second.png')],
            ])->assertRedirect(route('photos.create'))->assertSessionHasErrors('images');
        } finally {
            Event::forget('eloquent.creating: '.Photo::class);
        }

        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_storage_write_cleans_up_previously_uploaded_files(): void
    {
        $disk = Storage::disk('public');
        $mock = Mockery::mock($disk);
        $writes = 0;
        $mock->shouldReceive('putFileAs')->andReturnUsing(function (...$arguments) use ($disk, &$writes) {
            return ++$writes === 2 ? false : $disk->putFileAs(...$arguments);
        });
        Storage::set('public', $mock);

        $this->from(route('photos.create'))->post(route('photos.store'), [
            'images' => [$this->upload('first.png'), $this->upload('second.png')],
        ])->assertRedirect(route('photos.create'))->assertSessionHasErrors('images');

        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], $disk->allFiles());
    }

    public function test_global_delete_removes_both_filename_formats_variants_and_all_links(): void
    {
        $first = $this->gallery('a');
        $second = $this->gallery('b');
        $other = Photo::create(['filename' => 'photos/keep.jpg']);
        $first->photos()->attach($other);
        Storage::disk('public')->put('photos/keep.jpg', 'keep');

        foreach (['legacy.jpg', 'photos/current.jpg'] as $filename) {
            $photo = Photo::create(['filename' => $filename, 'thumbnail' => 'thumb-'.basename($filename), 'webp' => 'photos/'.basename($filename).'.webp']);
            $photo->galleries()->attach([$first->id => ['is_cover' => true], $second->id => ['is_cover' => true]]);
            foreach ($photo->filePaths() as $path) {
                Storage::disk('public')->put($path, 'photo');
            }

            $this->delete(route('photos.destroy', $photo))->assertRedirect(route('photos.index'))->assertSessionHas('success');
            $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
            $this->assertDatabaseMissing('gallery_photo', ['photo_id' => $photo->id]);
            Storage::disk('public')->assertMissing($photo->filePaths());
        }

        $this->assertDatabaseCount('photos', 1);
        $this->assertDatabaseCount('galleries', 2);
        $this->assertDatabaseHas('gallery_photo', ['photo_id' => $other->id, 'gallery_id' => $first->id]);
        Storage::disk('public')->assertExists('photos/keep.jpg');
        $this->get('/')->assertOk()->assertSee(asset('storage/photos/keep.jpg'));
    }

    public function test_global_delete_allows_missing_files_and_duplicate_variant_paths(): void
    {
        $photo = Photo::create(['filename' => 'missing.jpg', 'thumbnail' => 'photos/missing.jpg']);
        $this->delete(route('photos.destroy', $photo))->assertSessionHas('success');
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function test_file_delete_failure_keeps_record_and_gallery_links_and_reports_error(): void
    {
        $photo = Photo::create(['filename' => 'photo.jpg']);
        $gallery = $this->gallery('a');
        $gallery->photos()->attach($photo);
        $disk = Storage::disk('public');
        $disk->put('photos/photo.jpg', 'photo');
        $mock = Mockery::mock($disk);
        $mock->shouldReceive('delete')->with('photos/photo.jpg')->once()->andReturn(false);
        Storage::set('public', $mock);

        $this->delete(route('photos.destroy', $photo))->assertRedirect(route('photos.index'))
            ->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $gallery->id, 'photo_id' => $photo->id]);
        $disk->assertExists('photos/photo.jpg');
        $this->get(route('photos.index'))->assertOk()->assertSee('Nie udało się dokończyć usuwania zdjęcia');
    }

    public function test_failure_deleting_a_variant_restores_previously_deleted_original(): void
    {
        $photo = Photo::create(['filename' => 'photo.jpg', 'thumbnail' => 'thumb.jpg']);
        $gallery = $this->gallery('a');
        $gallery->photos()->attach($photo);
        $disk = Storage::disk('public');
        $disk->put('photos/photo.jpg', 'original bytes');
        $disk->put('photos/thumb.jpg', 'thumbnail bytes');
        $mock = Mockery::mock($disk);
        $mock->shouldReceive('delete')->with('photos/photo.jpg')->once()->andReturnUsing(fn ($path) => $disk->delete($path));
        $mock->shouldReceive('delete')->with('photos/thumb.jpg')->once()->andReturn(false);
        Storage::set('public', $mock);

        $this->delete(route('photos.destroy', $photo))->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertSame('original bytes', $disk->get('photos/photo.jpg'));
        $this->assertSame('thumbnail bytes', $disk->get('photos/thumb.jpg'));
        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $gallery->id, 'photo_id' => $photo->id]);
    }

    public function test_database_delete_failure_restores_files_and_pivot_links(): void
    {
        $photo = Photo::create(['filename' => 'photo.jpg']);
        $gallery = $this->gallery('a');
        $gallery->photos()->attach($photo);
        Storage::disk('public')->put('photos/photo.jpg', 'original bytes');
        Photo::deleting(fn () => throw new RuntimeException('Simulated database deletion failure.'));

        try {
            $this->delete(route('photos.destroy', $photo))->assertSessionHas('error')->assertSessionMissing('success');
        } finally {
            Event::forget('eloquent.deleting: '.Photo::class);
        }

        $this->assertSame('original bytes', Storage::disk('public')->get('photos/photo.jpg'));
        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $gallery->id, 'photo_id' => $photo->id]);
    }

    public function test_detaching_from_one_gallery_preserves_library_file_and_other_gallery(): void
    {
        $first = $this->gallery('a');
        $second = $this->gallery('b');
        $photo = Photo::create(['filename' => 'legacy.jpg', 'gallery_id' => $first->id]);
        $photo->galleries()->attach([$first->id => ['sort_order' => 0, 'is_cover' => true], $second->id => ['sort_order' => 7, 'is_cover' => true]]);
        Storage::disk('public')->put('photos/legacy.jpg', 'photo');

        $this->delete(route('galleries.photos.detach', [$first, $photo]))->assertRedirect(route('galleries.show', $first));
        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        $this->assertDatabaseMissing('gallery_photo', ['gallery_id' => $first->id, 'photo_id' => $photo->id]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $second->id, 'photo_id' => $photo->id, 'sort_order' => 7, 'is_cover' => true]);
        Storage::disk('public')->assertExists('photos/legacy.jpg');
    }

    public function test_deleting_gallery_preserves_legacy_shared_unlinked_and_new_library_photos(): void
    {
        $first = $this->gallery('a');
        $second = $this->gallery('b');
        $legacy = Photo::create(['filename' => 'legacy.jpg', 'gallery_id' => $first->id, 'is_cover' => true, 'sort_order' => 22]);
        $unlinked = Photo::create(['filename' => 'unlinked.jpg', 'gallery_id' => $first->id]);
        $new = Photo::create(['filename' => 'photos/new.jpg']);
        $first->photos()->attach([$legacy->id, $new->id]);
        $second->photos()->attach($legacy, ['is_cover' => true, 'sort_order' => 8]);
        foreach ([$legacy, $unlinked, $new] as $photo) {
            Storage::disk('public')->put(Photo::storagePath($photo->filename), 'photo');
        }

        $this->delete(route('galleries.destroy', $first))->assertRedirect(route('galleries.index'));
        $this->assertDatabaseMissing('galleries', ['id' => $first->id]);
        $this->assertDatabaseCount('photos', 3);
        $this->assertNull($legacy->fresh()->gallery_id);
        $this->assertNull($unlinked->fresh()->gallery_id);
        $this->assertDatabaseHas('photos', ['id' => $legacy->id, 'is_cover' => true, 'sort_order' => 22]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $second->id, 'photo_id' => $legacy->id, 'is_cover' => true, 'sort_order' => 8]);
        Storage::disk('public')->assertExists(['photos/legacy.jpg', 'photos/unlinked.jpg', 'photos/new.jpg']);
    }

    public function test_covers_and_order_remain_specific_to_gallery_and_do_not_write_legacy_fields(): void
    {
        $first = $this->gallery('a');
        $second = $this->gallery('b');
        $one = Photo::create(['filename' => 'one.jpg', 'is_cover' => false, 'sort_order' => 90]);
        $two = Photo::create(['filename' => 'photos/two.jpg', 'is_cover' => true, 'sort_order' => 91]);
        $first->photos()->attach([$one->id => ['sort_order' => 0, 'is_cover' => false], $two->id => ['sort_order' => 1, 'is_cover' => true]]);
        $second->photos()->attach([$one->id => ['sort_order' => 7, 'is_cover' => false], $two->id => ['sort_order' => 8, 'is_cover' => true]]);
        $otherPivot = DB::table('gallery_photo')->where('gallery_id', $second->id)->orderBy('id')->get()->toJson();

        $this->post(route('galleries.photos.cover', [$first, $one]))->assertRedirect(route('galleries.show', $first));
        $this->postJson(route('galleries.photos.reorder', $first), ['photos' => [$two->id, $one->id]])->assertOk();
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $first->id, 'photo_id' => $one->id, 'is_cover' => true, 'sort_order' => 1]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $first->id, 'photo_id' => $two->id, 'is_cover' => false, 'sort_order' => 0]);
        $this->assertSame($otherPivot, DB::table('gallery_photo')->where('gallery_id', $second->id)->orderBy('id')->get()->toJson());
        $this->assertDatabaseHas('photos', ['id' => $one->id, 'is_cover' => false, 'sort_order' => 90]);
        $this->assertDatabaseHas('photos', ['id' => $two->id, 'is_cover' => true, 'sort_order' => 91]);
        $this->get(route('portfolio.gallery', $first))->assertOk()
            ->assertSeeInOrder([asset('storage/photos/two.jpg'), asset('storage/photos/one.jpg')]);
    }

    public function test_complete_cms_library_workflow(): void
    {
        $this->post(route('photos.store'), ['images' => [$this->upload('first.png'), $this->upload('second.png')]])
            ->assertRedirect(route('photos.index'))->assertSessionHasNoErrors();
        [$one, $two] = Photo::orderBy('id')->get()->all();
        $paths = [$one->filename, $two->filename];

        foreach (['Pierwsza testowa', 'Druga testowa'] as $title) {
            $this->post(route('galleries.store'), ['title' => $title])
                ->assertRedirect(route('galleries.index'))->assertSessionHasNoErrors();
        }
        [$first, $second] = Gallery::orderBy('id')->get()->all();

        foreach ([$first, $second] as $gallery) {
            $this->get(route('galleries.library', $gallery))->assertOk()
                ->assertSee($one->imageUrl())->assertSee($two->imageUrl());
            $this->post(route('galleries.photos.attach', $gallery), ['photos' => [$one->id, $two->id]])
                ->assertRedirect(route('galleries.show', $gallery))->assertSessionHasNoErrors();
            $this->get(route('galleries.show', $gallery))->assertOk()
                ->assertSee($one->imageUrl())->assertSee($two->imageUrl());
            $this->get(route('galleries.library', $gallery))->assertOk()->assertSee('Już w galerii');
        }

        $this->post(route('galleries.photos.attach', $first), ['photos' => [$one->id, $one->id, $two->id]])
            ->assertRedirect(route('galleries.show', $first))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('photos', 2);
        $this->assertDatabaseCount('gallery_photo', 4);
        $this->get(route('photos.index'))->assertOk()->assertSee($first->title)->assertSee($second->title);

        $this->put(route('photos.update', $one), ['title' => 'Zdjęcie testowe', 'alt' => 'Test alt', 'description' => 'Test opisu'])
            ->assertRedirect(route('photos.index'))->assertSessionHasNoErrors();
        foreach ([$first, $second] as $gallery) {
            $this->get(route('galleries.show', $gallery))->assertOk()->assertSee('Zdjęcie testowe');
        }

        $this->post(route('galleries.photos.cover', [$first, $one]))->assertRedirect();
        $this->post(route('galleries.photos.cover', [$second, $two]))->assertRedirect();
        $this->postJson(route('galleries.photos.reorder', $first), ['photos' => [$two->id, $one->id]])->assertOk();
        $this->assertSame([$two->id, $one->id], $first->photos()->pluck('photos.id')->all());
        $this->assertSame([$one->id, $two->id], $second->photos()->pluck('photos.id')->all());

        $this->delete(route('galleries.photos.detach', [$first, $one]))->assertRedirect();
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $first->id, 'photo_id' => $two->id, 'is_cover' => true]);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $second->id, 'photo_id' => $one->id]);
        Storage::disk('public')->assertExists($paths);

        $this->delete(route('galleries.destroy', $first))->assertRedirect();
        $this->assertDatabaseCount('photos', 2);
        $this->get(route('photos.index'))->assertOk()->assertSee($second->title)->assertDontSee($first->title);
        Storage::disk('public')->assertExists($paths);

        $this->delete(route('photos.destroy', $one))->assertRedirect(route('photos.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('gallery_photo', ['photo_id' => $one->id]);
        $this->get(route('photos.index'))->assertOk()->assertDontSee('Zdjęcie testowe');
        $this->get(route('galleries.show', $second))->assertOk()->assertDontSee('Zdjęcie testowe')->assertSee($two->imageUrl());
        Storage::disk('public')->assertMissing($one->filename);
        Storage::disk('public')->assertExists($two->filename);
    }

    public function test_removed_global_endpoints_are_unavailable(): void
    {
        $photo = Photo::create(['filename' => 'photo.jpg']);
        $this->assertFalse(Route::has('photos.make-cover'));
        $this->assertFalse(Route::has('photos.reorder'));
        $this->post('/photos/'.$photo->id.'/make-cover')->assertNotFound();
        $this->post('/photos/reorder', ['photos' => [$photo->id]])->assertStatus(405);
    }

    public function test_library_routes_require_authentication(): void
    {
        $photo = Photo::create(['filename' => 'photo.jpg']);
        auth()->logout();
        $this->get(route('photos.index'))->assertRedirect(route('login'));
        $this->get(route('photos.create'))->assertRedirect(route('login'));
        $this->get(route('photos.edit', $photo))->assertRedirect(route('login'));
        $this->post(route('photos.store'))->assertRedirect(route('login'));
        $this->put(route('photos.update', $photo), ['title' => 'Changed'])->assertRedirect(route('login'));
        $this->delete(route('photos.destroy', $photo))->assertRedirect(route('login'));
        $this->assertDatabaseHas('photos', ['id' => $photo->id, 'title' => null]);
    }
    public function test_existing_photo_optimization_command_backfills_missing_variants_and_skips_complete_records(): void
    {
        $legacy = Photo::create(['filename' => 'legacy.png']);
        $complete = Photo::create([
            'filename' => 'photos/complete.png',
            'webp' => 'photos/complete-web.webp',
            'thumbnail' => 'photos/complete-thumb.webp',
        ]);

        Storage::disk('public')->put('photos/legacy.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        Storage::disk('public')->put('photos/complete.png', 'original');
        Storage::disk('public')->put('photos/complete-web.webp', 'web');
        Storage::disk('public')->put('photos/complete-thumb.webp', 'thumb');

        $this->artisan('photos:optimize-existing')
            ->expectsOutputToContain('Zoptymalizowano: 1; pominięto: 1; błędy: 0.')
            ->assertExitCode(0);

        $legacy = $legacy->fresh();
        $complete = $complete->fresh();

        $this->assertNotNull($legacy->webp);
        $this->assertNotNull($legacy->thumbnail);
        Storage::disk('public')->assertExists($legacy->webp);
        Storage::disk('public')->assertExists($legacy->thumbnail);

        $this->assertSame('photos/complete-web.webp', $complete->webp);
        $this->assertSame('photos/complete-thumb.webp', $complete->thumbnail);
    }

    public function test_existing_photo_optimization_command_rejects_invalid_limit(): void
    {
        $this->artisan('photos:optimize-existing', ['--limit' => '0'])
            ->expectsOutput('--limit must be a positive integer.')
            ->assertExitCode(2);
    }


    public function test_photo_urls_prefer_optimized_variants_with_safe_fallbacks(): void
    {
        $photo = Photo::create([
            'filename' => 'photos/original.jpg',
            'webp' => 'photos/web/photo.webp',
            'thumbnail' => 'photos/thumb/photo.webp',
        ]);

        $this->assertSame(asset('storage/photos/web/photo.webp'), $photo->imageUrl());
        $this->assertSame(asset('storage/photos/original.jpg'), $photo->originalUrl());
        $this->assertSame(asset('storage/photos/thumb/photo.webp'), $photo->thumbnailUrl());

        $legacy = Photo::create(['filename' => 'legacy.jpg']);
        $this->assertSame(asset('storage/photos/legacy.jpg'), $legacy->imageUrl());
        $this->assertSame(asset('storage/photos/legacy.jpg'), $legacy->thumbnailUrl());
    }

    public function test_public_photo_views_use_lazy_optimized_thumbnails(): void
    {
        $shared = file_get_contents(resource_path('views/components/public-builder-canvas.blade.php'));
        $gallery = file_get_contents(resource_path('views/portfolio/gallery.blade.php'));
        $thumbnails = file_get_contents(resource_path('views/components/builder-thumbnail-gallery.blade.php'));

        $this->assertStringContainsString('loading="lazy"', $shared);
        $this->assertStringContainsString('$photoRecord?->imageUrl()', $shared);
        $this->assertStringContainsString('$photo->thumbnailUrl()', $gallery);
        $this->assertStringContainsString('decoding="async"', $gallery);
        $this->assertStringContainsString('decoding="async"', $thumbnails);
    }


    public function test_public_pages_include_social_and_structured_seo_metadata(): void
    {
        $view = file_get_contents(resource_path('views/components/seo-meta.blade.php'));

        $this->assertStringContainsString('og:site_name', $view);
        $this->assertStringContainsString('twitter:card', $view);
        $this->assertStringContainsString('application/ld+json', $view);
        $this->assertStringContainsString("'@type' => 'WebPage'", $view);
        $this->assertStringContainsString("'@type' => 'WebSite'", $view);
        $this->assertStringContainsString("'@type' => 'ImageObject'", $view);
    }


}
