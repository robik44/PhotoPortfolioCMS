<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class PhotoVariantService
{
    private ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(new Driver());
    }

    /**
     * @return array{thumbnail: ?string, webp: ?string}
     */
    public function createFor(string $filename): array
    {
        $disk = Storage::disk('public');
        $sourcePath = Photo::storagePath($filename);

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException('Source photo does not exist: '.$sourcePath);
        }

        $source = $disk->path($sourcePath);
        $base = pathinfo($sourcePath, PATHINFO_FILENAME);
        $directory = trim(pathinfo($sourcePath, PATHINFO_DIRNAME), '.');

        $webp = ($directory ? $directory.'/' : '').$base.'-web.webp';
        $thumbnail = ($directory ? $directory.'/' : '').$base.'-thumb.webp';

        try {
            $large = $this->images->read($source);
            $large->scaleDown(width: 2400, height: 2400);
            if (! $disk->put($webp, (string) $large->toWebp(quality: 86))) {
                throw new RuntimeException('Could not store optimized WebP.');
            }

            $thumb = $this->images->read($source);
            $thumb->scaleDown(width: 900, height: 900);
            if (! $disk->put($thumbnail, (string) $thumb->toWebp(quality: 82))) {
                throw new RuntimeException('Could not store thumbnail WebP.');
            }

            return ['thumbnail' => $thumbnail, 'webp' => $webp];
        } catch (Throwable $exception) {
            $disk->delete([$webp, $thumbnail]);
            throw $exception;
        }
    }

    public function optimize(Photo $photo, bool $force = false): bool
    {
        $disk = Storage::disk('public');
        $needsWebp = $force || blank($photo->webp) || ! $disk->exists(Photo::storagePath($photo->webp));
        $needsThumbnail = $force || blank($photo->thumbnail) || ! $disk->exists(Photo::storagePath($photo->thumbnail));

        if (! $needsWebp && ! $needsThumbnail) {
            return false;
        }

        $oldPaths = collect([$photo->webp, $photo->thumbnail])
            ->filter()
            ->map(fn (string $path) => Photo::storagePath($path))
            ->unique()
            ->values();

        $variants = $this->createFor($photo->filename);

        $photo->update([
            'webp' => $variants['webp'],
            'thumbnail' => $variants['thumbnail'],
        ]);

        foreach ($oldPaths as $path) {
            if (! in_array($path, [Photo::storagePath($variants['webp']), Photo::storagePath($variants['thumbnail'])], true)) {
                $disk->delete($path);
            }
        }

        return true;
    }
}
