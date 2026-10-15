<?php

namespace Tests\Feature;

use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodfotoHomeImageAltTest extends TestCase
{
    use RefreshDatabase;

    private function photos(): array
    {
        return [
            Photo::create([
                'id' => 112,
                'filename' => 'photos/original-carrots.jpg',
                'webp' => 'photos/eDKV5eavwa8h830JcyzIaH2eekFta7hScTfofiix-web.webp',
                'title' => null, 'alt' => null,
            ]),
            Photo::create([
                'id' => 117,
                'filename' => 'photos/original-dessert.jpg',
                'webp' => 'photos/C1GXfqAvAklI81MFyYRdlssNFhB74iBF01HNLYvV-web.webp',
                'title' => null, 'alt' => null,
            ]),
            Photo::create([
                'id' => 113,
                'filename' => 'photos/original-basil.jpg',
                'webp' => 'photos/BVHDZh5IWgODm79Lc4dSqU7E5gHlvPwm0TuPaI0p-web.webp',
                'title' => null, 'alt' => null,
            ]),
        ];
    }

    public function test_command_only_updates_alt_after_explicit_apply(): void
    {
        $photos = $this->photos();
        $unrelated = Photo::create(['filename' => 'photos/other.jpg', 'alt' => null]);
        $oldValues = array_map(fn ($photo) => $photo->toArray(), $photos);

        $this->artisan('foodfoto:home-image-alts')->assertSuccessful();
        foreach ($photos as $i => $photo) {
            $this->assertSame($oldValues[$i], $photo->fresh()->toArray());
        }

        $this->artisan('foodfoto:home-image-alts --apply')->assertSuccessful();
        $this->assertStringContainsString('Pieczone marchewki', (string) Photo::find(112)->alt);
        $this->assertStringContainsString('malinami', (string) Photo::find(117)->alt);
        $this->assertStringContainsString('bazylii', (string) Photo::find(113)->alt);

        foreach ($photos as $i => $photo) {
            $new = $photo->fresh()->toArray();
            unset($new['alt'], $new['updated_at']);
            $original = $oldValues[$i];
            unset($original['alt'], $original['updated_at']);
            $this->assertSame($original, $new);
        }
        $this->assertNull($unrelated->fresh()->alt);

        // Safe to run again.
        $alts = Photo::whereIn('id', [112, 117, 113])->pluck('alt', 'id')->all();
        $this->artisan('foodfoto:home-image-alts --apply')->assertSuccessful();
        $this->assertSame($alts, Photo::whereIn('id', [112, 117, 113])->pluck('alt', 'id')->all());
    }

    public function test_command_does_not_replace_existing_photo_alt(): void
    {
        $this->photos();
        Photo::find(117)->update(['alt' => 'Autorski opis deseru.']);
        $this->artisan('foodfoto:home-image-alts --apply')->assertSuccessful();

        $this->assertSame('Autorski opis deseru.', Photo::find(117)->alt);
        $this->assertNotEmpty(Photo::find(112)->alt);
        $this->assertNotEmpty(Photo::find(113)->alt);
    }

    public function test_command_refuses_changed_image_filename_without_writing_any_alts(): void
    {
        $this->photos();
        Photo::find(113)->update(['webp' => 'photos/different-image.webp']);
        $this->artisan('foodfoto:home-image-alts --apply')->assertFailed();
        $this->assertNull(Photo::find(112)->alt);
        $this->assertNull(Photo::find(117)->alt);
        $this->assertNull(Photo::find(113)->alt);
    }
}
