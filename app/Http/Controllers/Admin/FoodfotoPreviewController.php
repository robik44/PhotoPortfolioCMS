<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Support\Seo;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class FoodfotoPreviewController extends Controller
{
    public function updateContent(Request $request)
    {
        $validated = $request->validate([
            'preview_hero_title' => ['required', 'string', 'max:160'],
            'preview_hero_description' => ['required', 'string', 'max:500'],
            'preview_hero_button_label' => ['required', 'string', 'max:80'],
            'preview_editorial_title' => ['required', 'string', 'max:600'],
            'preview_editorial_description' => ['required', 'string', 'max:600'],
            'preview_experience_title' => ['required', 'string', 'max:600'],
            'preview_experience_description' => ['required', 'string', 'max:600'],
        ]);

        foreach ($validated as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => trim($value)]);
        }

        return redirect()->route('foodfoto.home.preview')->with('foodfoto_content_saved', true);
    }

    public function __invoke(): Response
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')->firstOrFail();
        $sections = array_values($builder->content['sections'] ?? []);
        abort_unless(count($sections) === 32
            && ($sections[3]['content'] ?? null) === 'Co tworzę'
            && ($sections[24]['type'] ?? null) === 'gallery'
            && ($sections[30]['type'] ?? null) === 'thumbnail_gallery'
            && (int) ($sections[31]['photo_id'] ?? 0) === 96, 409, 'The current layout differs from the inspected 32-element homepage.');

        $photoIds = collect($sections)->pluck('photo_id')
            ->merge($sections[30]['photo_ids'] ?? [])
            ->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique();
        $photos = Photo::whereIn('id', $photoIds)->get()->keyBy('id');

        $galleryElement = $sections[24];
        $mode = $galleryElement['gallery_mode'] ?? 'all';
        $galleryQuery = Gallery::with('photos')->where('published', true);
        if (!empty($galleryElement['gallery_collection_id'])) {
            $galleryQuery->where('gallery_collection_id', $galleryElement['gallery_collection_id']);
        }
        if ($mode === 'single') {
            $galleryQuery->whereKey($galleryElement['gallery_id'] ?? 0);
        } elseif ($mode === 'selected') {
            $galleryQuery->whereIn('id', $galleryElement['gallery_ids'] ?? []);
        }
        $galleries = $galleryQuery->orderBy('sort_order')->get();
        if ($mode === 'selected') {
            $order = array_flip(array_map('intval', $galleryElement['gallery_ids'] ?? []));
            $galleries = $galleries->sortBy(fn ($gallery) => $order[$gallery->id] ?? PHP_INT_MAX)->values();
        }

        $pages = Page::where('published', true)->get();
        $seoLinks = [];
        foreach ($pages as $page) {
            $seoLinks[mb_strtolower(trim($page->title))] = Seo::pageUrl($page);
        }

        $thumbs = collect($sections[30]['photo_ids'] ?? [])
            ->map(fn ($id) => $photos->get((int) $id))->filter()->values();

        $previewDecorations = SiteSetting::whereIn('key', ['preview_basil_hero', 'preview_basil_editorial', 'preview_basil_process', 'preview_hero_image', 'preview_hero_title', 'preview_hero_description', 'preview_hero_button_label', 'preview_hero_button_url', 'preview_editorial_title', 'preview_editorial_description', 'preview_editorial_photo_id', 'preview_experience_title', 'preview_experience_description', 'preview_experience_photo_id'])->pluck('value', 'key')->all();

        $extraPhotoIds = collect(['preview_editorial_photo_id', 'preview_experience_photo_id'])
            ->map(fn ($key) => $previewDecorations[$key] ?? null)
            ->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique();
        $photos = $photos->merge(Photo::whereIn('id', $extraPhotoIds)->get()->keyBy('id'));

        $response = response()->view('admin.foodfoto-home-preview', compact(
            'sections', 'photos', 'galleries', 'seoLinks', 'thumbs', 'previewDecorations'
        ));
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
