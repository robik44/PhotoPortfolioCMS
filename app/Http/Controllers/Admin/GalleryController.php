<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\SiteSetting;
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
        ]);

        $sortOrder = (int) (
            Gallery::max('sort_order') ?? -1
        );

        $slug = Str::slug($data['title']);
        $baseSlug = $slug;
        $counter = 2;

        while (Gallery::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        Gallery::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sort_order' => $sortOrder + 1,
        ]);

        return redirect()
            ->route('galleries.index')
            ->with('success', 'Galeria została utworzona.');
    }

    public function show(Gallery $gallery)
    {
        $gallery->load([
            'photos' => function ($query) {
                $query->orderBy('sort_order');
            }
        ]);

        $defaults = [
            'logo' => 'ROBERT WOŹNIAK',
            'logo_subtitle' => 'FOTOGRAFIA',
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

    public function publicShow(Gallery $gallery)
    {
        $gallery->load([
            'photos' => function ($query) {
                $query->orderBy('sort_order');
            }
        ]);

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
        return view('admin.galleries.edit', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $gallery->update([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('galleries.index')
            ->with('success', 'Galeria została zaktualizowana.');
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
