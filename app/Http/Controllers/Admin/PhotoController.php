<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index()
    {
        $photos = Photo::with('gallery')
            ->orderBy('gallery_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.photos.index', compact('photos'));
    }

    public function create()
    {
        $galleries = Gallery::orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.photos.create', compact('galleries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gallery_id' => ['required', 'exists:galleries,id'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:20480'],
        ]);

        $gallery = Gallery::findOrFail($data['gallery_id']);

        $sortOrder = (int) (
            Photo::where('gallery_id', $gallery->id)->max('sort_order') ?? -1
        ) + 1;

        foreach ($request->file('images', []) as $image) {
            $filename = $image->store('photos', 'public');

            Photo::create([
                'gallery_id' => $gallery->id,
                'filename' => $filename,
                'sort_order' => $sortOrder++,
            ]);
        }

        return redirect()
            ->route('galleries.show', $gallery)
            ->with('success', 'Zdjęcia zostały dodane.');
    }

    public function show(Photo $photo)
    {
        return redirect()->route('photos.index');
    }

    public function edit(Photo $photo)
    {
        $galleries = Gallery::orderBy('title')->get();

        return view('admin.photos.edit', compact('photo', 'galleries'));
    }

    public function update(Request $request, Photo $photo)
    {
        $data = $request->validate([
            'gallery_id' => ['required', 'exists:galleries,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $photo->update($data);

        return redirect()
            ->route('photos.index')
            ->with('success', 'Zdjęcie zostało zaktualizowane.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'integer', 'exists:photos,id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['photos'] as $position => $photoId) {
                Photo::where('id', $photoId)
                    ->update([
                        'sort_order' => $position,
                    ]);
            }
        });

        return response()->json([
            'success' => true,
        ]);
    }

    public function makeCover(Photo $photo)
    {
        DB::transaction(function () use ($photo) {
            Photo::where('gallery_id', $photo->gallery_id)
                ->update(['is_cover' => false]);

            $photo->update(['is_cover' => true]);
        });

        return back()->with('success', 'Ustawiono zdjęcie okładkowe.');
    }

    public function destroy(Photo $photo)
    {
        $gallery = $photo->gallery;

        if ($photo->filename && Storage::disk('public')->exists($photo->filename)) {
            Storage::disk('public')->delete($photo->filename);
        }

        $photo->delete();

        return redirect()
            ->route('galleries.show', $gallery)
            ->with('success', 'Zdjęcie zostało usunięte.');
    }
}