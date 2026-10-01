<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use App\Services\PhotoVariantService;

class PhotoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['seo_filter' => ['nullable', 'in:missing_alt,missing_title,missing_description']]);
        $filter = $request->query('seo_filter');
        $fields = ['missing_alt' => 'alt', 'missing_title' => 'title', 'missing_description' => 'description'];
        $photos = Photo::with('galleries')
            ->when(isset($fields[$filter]), fn ($query) => $query->missingMetadata($fields[$filter]))
            ->latest()
            ->get();

        return view('admin.photos.index', compact('photos'));
    }

    public function create()
    {
        return view('admin.photos.create');
    }

    public function store(Request $request, PhotoVariantService $variantService)
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:51200'],
        ]);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, &$storedPaths, $variantService) {
                foreach ($request->file('images', []) as $image) {
                    $filename = $image->store('photos', 'public');

                    if ($filename === false) {
                        throw new RuntimeException('Could not store an uploaded photo.');
                    }

                    $storedPaths[] = $filename;

                    $variants = $variantService->createFor($filename);
                    $storedPaths = array_merge($storedPaths, array_values(array_filter($variants)));

                    Photo::create([
                        'filename' => $filename,
                        'thumbnail' => $variants['thumbnail'],
                        'webp' => $variants['webp'],
                    ]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            foreach ($storedPaths as $path) {
                try {
                    if (! Storage::disk('public')->delete($path)) {
                        throw new RuntimeException('Could not clean up uploaded photo: '.$path);
                    }
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Nie udało się zapisać fotografii. Spróbuj ponownie.'], 500);
            }

            return back()->withErrors([
                'images' => 'Nie udało się zapisać fotografii. Spróbuj ponownie.',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fotografie zostały dodane do biblioteki.'], 201);
        }

        return redirect()
            ->route('photos.index')
            ->with('success', 'Fotografie zostały dodane do biblioteki.');
    }

    public function show(Photo $photo)
    {
        return redirect()->route('photos.index');
    }

    public function edit(Photo $photo)
    {
        $photo->load('galleries');
        $key = 'photo_'.$photo->id.'_typography';
        $photoTypography = \App\Support\TypographySettings::read([
            $key => \App\Models\SiteSetting::where('key', $key)->value('value'),
        ], $key);

        return view('admin.photos.edit', compact('photo', 'photoTypography'));
    }

    public function update(Request $request, Photo $photo)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ] + \App\Support\TypographySettings::rules());

        DB::transaction(function () use ($photo, $data) {
            $photo->update(\Illuminate\Support\Arr::only($data, ['title', 'alt', 'description']));
            \App\Support\TypographySettings::save('photo_'.$photo->id.'_typography', $data);
        });

        return redirect()
            ->route('photos.index')
            ->with('success', 'Zdjęcie zostało zaktualizowane.');
    }

    public function destroy(Photo $photo)
    {
        $disk = Storage::disk('public');
        $backups = [];
        $attemptedPaths = [];

        try {
            DB::transaction(function () use ($photo, $disk, &$backups, &$attemptedPaths) {
                // Database transactions cannot restore files. Keep temporary streams
                // until both the file deletions and the database commit succeed.
                foreach ($photo->filePaths() as $path) {
                    if (! $disk->exists($path)) {
                        continue;
                    }

                    $backup = tmpfile();
                    if ($backup === false) {
                        throw new RuntimeException('Could not back up photo before deletion.');
                    }

                    $source = null;
                    try {
                        $source = $disk->readStream($path);
                        if (! is_resource($source) || stream_copy_to_stream($source, $backup) === false) {
                            throw new RuntimeException('Could not read photo before deletion: '.$path);
                        }
                        $backups[$path] = $backup;
                    } finally {
                        if (is_resource($source)) {
                            fclose($source);
                        }
                        if (! isset($backups[$path])) {
                            fclose($backup);
                        }
                    }
                }

                foreach ($backups as $path => $backup) {
                    $attemptedPaths[] = $path;
                    if (! $disk->delete($path)) {
                        throw new RuntimeException('Could not delete photo file: '.$path);
                    }
                }

                $photo->galleries()->detach();
                if (! $photo->delete()) {
                    throw new RuntimeException('Could not delete photo record.');
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            $restored = true;
            foreach ($attemptedPaths as $path) {
                try {
                    rewind($backups[$path]);
                    if (! $disk->put($path, $backups[$path])) {
                        throw new RuntimeException('Could not restore photo after failed deletion: '.$path);
                    }
                } catch (Throwable $restoreException) {
                    $restored = false;
                    report($restoreException);
                }
            }

            return redirect()->route('photos.index')->with(
                'error',
                'Nie udało się dokończyć usuwania zdjęcia. Rekord i powiązania zachowano. '
                    .($restored ? 'Spróbuj ponownie.' : 'Nie udało się przywrócić wszystkich plików. Skontaktuj się z administratorem.')
            );
        } finally {
            foreach ($backups as $backup) {
                fclose($backup);
            }
        }

        return redirect()
            ->route('photos.index')
            ->with('success', 'Zdjęcie zostało usunięte z Biblioteki i wszystkich galerii.');
    }
}
