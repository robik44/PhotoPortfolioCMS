<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Support\GalleryTypography;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::with('photos')
            ->orderBy('sort_order')
            ->paginate(12);

        return view('admin.galleries.index', compact('galleries'));
    }

    public function create()
    {
        return view('admin.galleries.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ] + GalleryTypography::rules() + \App\Support\Seo::rules() + [
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'not_regex:/^[0-9]+$/', \Illuminate\Validation\Rule::unique('galleries', 'slug')],
        ]);

        $sortOrder = (int) (
            Gallery::max('sort_order') ?? -1
        );

        $slug = $data['slug'] ?? Str::slug($data['title']);
        if ($slug === '' || ctype_digit($slug)) $slug = 'galeria-'.$slug;
        $baseSlug = $slug;
        $counter = 2;

        while (Gallery::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $gallery = Gallery::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sort_order' => $sortOrder + 1,
        ]);

        $gallery->update(\Illuminate\Support\Arr::only($data, array_keys(\App\Support\Seo::rules())));
        GalleryTypography::save($gallery->id, $data);

        return redirect()
            ->route('galleries.index')
            ->with('success', 'Galeria została utworzona.');
    }

    public function show(Gallery $gallery)
    {
        $gallery->load('photos');

        $defaults = [
            'logo' => 'MAGDA GUGAŁA',
            'logo_subtitle' => 'FOTOGRAFIA ŻYWNOŚCI',
            'menu_gallery' => 'GALERIE',
            'menu_about' => 'O MNIE',
            'menu_contact' => 'KONTAKT',
        ];

        $settings = array_merge(
            $defaults,
            SiteSetting::pluck('value', 'key')->toArray()
        );

        return view(
            'admin.galleries.show',
            compact('gallery', 'settings')
        );
    }

    public function publicShow(string $gallery)
    {
        // Numeric URLs existed before slugs. Keep their permanent redirects.
        $legacy = ctype_digit($gallery) ? Gallery::find($gallery) : null;
        if ($legacy && $legacy->slug !== $gallery) {
            abort_unless($legacy->published, 404);
            return redirect()->route('portfolio.gallery', $legacy, 301);
        }
        $gallery = Gallery::where('slug', $gallery)->where('published', true)->firstOrFail();
        $gallery->load('photos');

        $defaults = [
            'site_title' => 'Fotografia',
            'site_subtitle' => 'Fotografia kulinarna i artystyczna',
            'hero_title' => 'Fotografia to sposób patrzenia na świat',
            'hero_subtitle' => 'Obrazy, smaki i historie',
            'hero_button' => 'Zobacz portfolio',
            'about_title' => 'O mnie',
            'about_text' => '',
            'contact_title' => 'Kontakt',
            'contact_text' => '',
            'footer_text' => '',
        ];

        $settings = array_merge(
            $defaults,
            SiteSetting::pluck('value', 'key')->toArray()
        );

        return view(
            'portfolio.gallery',
            compact('gallery', 'settings')
        );
    }

    public function edit(Gallery $gallery)
    {
        $galleryFonts = GalleryTypography::read([
            GalleryTypography::key($gallery->id) => SiteSetting::where(
                'key',
                GalleryTypography::key($gallery->id)
            )->value('value'),
        ], $gallery->id);

        $backLink = \App\Support\GalleryBackLink::read(SiteSetting::pluck('value', 'key')->all(), $gallery->id);

        return view(
            'admin.galleries.edit',
            compact('gallery', 'galleryFonts', 'backLink')
        );
    }

    public function update(Request $request, Gallery $gallery)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ] + \App\Support\GalleryBackLink::rules() + GalleryTypography::rules() + \App\Support\Seo::rules() + [
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', \Illuminate\Validation\Rule::when($request->input('slug') !== $gallery->slug, ['not_regex:/^[0-9]+$/']), \Illuminate\Validation\Rule::unique('galleries', 'slug')->ignore($gallery->id)],
        ]);

        $gallery->update([
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $gallery->slug,
            'description' => $data['description'] ?? null,
        ]);

        $gallery->update(\Illuminate\Support\Arr::only($data, array_keys(\App\Support\Seo::rules())));
        GalleryTypography::save($gallery->id, $data);
        \App\Support\GalleryBackLink::save($gallery->id, $data);

        return redirect()
            ->route('galleries.index')
            ->with('success', 'Galeria została zaktualizowana.');
    }

    public function library(Gallery $gallery)
    {
        $gallery->load('photos');

        $assignedPhotoIds = $gallery->photos
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $photos = Photo::query()
            ->latest()
            ->get();

        return view(
            'admin.galleries.library',
            compact('gallery', 'photos', 'assignedPhotoIds')
        );
    }

    public function attachPhotos(Request $request, Gallery $gallery)
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'integer', 'exists:photos,id'],
        ]);

        $existingPhotoIds = $gallery->photos()
            ->pluck('photos.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $photoIds = array_values(array_unique(array_map(
            'intval',
            $data['photos']
        )));

        $photoIds = array_values(array_diff(
            $photoIds,
            $existingPhotoIds
        ));

        if (empty($photoIds)) {
            return redirect()
                ->route('galleries.show', $gallery)
                ->with('success', 'Wybrane zdjęcia są już w tej galerii.');
        }

        $maxSortOrder = DB::table('gallery_photo')
            ->where('gallery_id', $gallery->id)
            ->max('sort_order');

        $nextSortOrder = $maxSortOrder === null
            ? 0
            : ((int) $maxSortOrder + 1);

        $attachData = [];

        foreach ($photoIds as $photoId) {
            $attachData[$photoId] = [
                'sort_order' => $nextSortOrder++,
                'is_cover' => false,
            ];
        }

        $gallery->photos()->attach($attachData);

        return redirect()
            ->route('galleries.show', $gallery)
            ->with(
                'success',
                'Wybrane zdjęcia zostały dodane do galerii.'
            );
    }

    public function detachPhoto(Gallery $gallery, Photo $photo)
    {
        $isAttached = $gallery->photos()
            ->where('photos.id', $photo->id)
            ->exists();

        if (!$isAttached) {
            abort(404);
        }

        DB::transaction(function () use ($gallery, $photo) {
            $wasCover = DB::table('gallery_photo')
                ->where('gallery_id', $gallery->id)
                ->where('photo_id', $photo->id)
                ->value('is_cover');

            $gallery->photos()->detach($photo->id);

            if ($wasCover) {
                $firstPhotoId = DB::table('gallery_photo')
                    ->where('gallery_id', $gallery->id)
                    ->orderBy('sort_order')
                    ->value('photo_id');

                if ($firstPhotoId) {
                    DB::table('gallery_photo')
                        ->where('gallery_id', $gallery->id)
                        ->where('photo_id', $firstPhotoId)
                        ->update([
                            'is_cover' => true,
                            'updated_at' => now(),
                        ]);
                }
            }
        });

        return redirect()
            ->route('galleries.show', $gallery)
            ->with(
                'success',
                'Zdjęcie zostało usunięte z galerii. Nadal znajduje się w Bibliotece.'
            );
    }

    public function makeCover(Gallery $gallery, Photo $photo)
    {
        $isAttached = $gallery->photos()
            ->where('photos.id', $photo->id)
            ->exists();

        if (!$isAttached) {
            abort(404);
        }

        DB::transaction(function () use ($gallery, $photo) {
            DB::table('gallery_photo')
                ->where('gallery_id', $gallery->id)
                ->update([
                    'is_cover' => false,
                    'updated_at' => now(),
                ]);

            DB::table('gallery_photo')
                ->where('gallery_id', $gallery->id)
                ->where('photo_id', $photo->id)
                ->update([
                    'is_cover' => true,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('galleries.show', $gallery)
            ->with('success', 'Ustawiono zdjęcie okładkowe galerii.');
    }

    public function reorderPhotos(Request $request, Gallery $gallery)
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'integer', 'exists:photos,id'],
        ]);

        $currentPhotoIds = $gallery->photos()
            ->pluck('photos.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $requestedPhotoIds = collect($data['photos'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($currentPhotoIds !== $requestedPhotoIds) {
            return response()->json([
                'success' => false,
                'message' => 'Lista zdjęć nie odpowiada zawartości galerii.',
            ], 422);
        }

        DB::transaction(function () use ($gallery, $data) {
            foreach ($data['photos'] as $position => $photoId) {
                DB::table('gallery_photo')
                    ->where('gallery_id', $gallery->id)
                    ->where('photo_id', $photoId)
                    ->update([
                        'sort_order' => $position,
                        'updated_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'success' => true,
        ]);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'galleries' => ['required', 'array', 'min:1'],
            'galleries.*' => ['required', 'integer', 'exists:galleries,id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['galleries'] as $position => $galleryId) {
                Gallery::where('id', $galleryId)
                    ->update([
                        'sort_order' => $position,
                    ]);
            }
        });

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();

        return redirect()
            ->route('galleries.index')
            ->with('success', 'Galeria została usunięta.');
    }
}
