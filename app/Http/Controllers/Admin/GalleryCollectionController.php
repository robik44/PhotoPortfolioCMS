<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GalleryCollectionController extends Controller
{
    public function create()
    {
        return view('admin.gallery-collections.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('gallery_collections', 'slug')],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);
        if ($slug === '') $slug = 'galeria';
        $base = $slug;
        $counter = 2;
        while (GalleryCollection::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        $collection = GalleryCollection::create([
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => ((int) GalleryCollection::max('sort_order')) + 1,
        ]);

        return redirect()->route('galleries.index', ['collection' => $collection->id])
            ->with('success', 'Nowa galeria została utworzona. Teraz możesz dodać do niej podgalerie.');
    }

    public function edit(GalleryCollection $galleryCollection)
    {
        return view('admin.gallery-collections.edit', compact('galleryCollection'));
    }

    public function update(Request $request, GalleryCollection $galleryCollection)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('gallery_collections', 'slug')->ignore($galleryCollection->id)],
        ]);

        $galleryCollection->update($data);

        return redirect()->route('galleries.index', ['collection' => $galleryCollection->id])
            ->with('success', 'Nazwa galerii została zmieniona.');
    }

    public function destroy(GalleryCollection $galleryCollection)
    {
        if ($galleryCollection->galleries()->exists()) {
            return back()->with('error', 'Najpierw przenieś lub usuń podgalerie z tej galerii.');
        }

        if (GalleryCollection::count() <= 1) {
            return back()->with('error', 'Musi pozostać przynajmniej jedna galeria.');
        }

        $galleryCollection->delete();

        return redirect()->route('galleries.index')->with('success', 'Galeria została usunięta.');
    }
}
