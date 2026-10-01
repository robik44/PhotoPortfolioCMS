<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Gallery, MenuItem, Page, Photo, SiteSetting};
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoController extends Controller
{
    public function edit()
    {
        $settings = Seo::settings();
        $pages = Page::with(['socialPhoto', 'builder'])->orderBy('title')->get();
        $galleries = Gallery::with(['socialPhoto', 'photos'])->orderBy('title')->get();

        $indexedEntities = collect([
            ['label' => 'Strona główna', 'meta' => Seo::meta(null), 'active' => Seo::homeIndexing($settings)],
        ])->concat(
            $pages->map(fn ($page) => [
                'label' => 'Strona: '.$page->title,
                'meta' => Seo::meta($page),
                'active' => $page->published && $page->indexable && Seo::indexing($settings),
            ])
        )->concat(
            $galleries->map(fn ($gallery) => [
                'label' => 'Galeria: '.$gallery->title,
                'meta' => Seo::meta($gallery),
                'active' => $gallery->published && $gallery->indexable && Seo::indexing($settings),
            ])
        )->filter(fn ($entity) => (bool) $entity['active'])->values();

        $duplicateTitles = $this->duplicates($indexedEntities, 'title');
        $duplicateDescriptions = $this->duplicates($indexedEntities, 'description');

        $menuIssues = MenuItem::with(['page', 'gallery'])->where('published', true)->get()
            ->map(function ($item) {
                if ($item->type === 'page' && (!$item->page || !$item->page->published)) {
                    return 'Menu „'.$item->title.'” prowadzi do brakującej lub nieopublikowanej strony.';
                }
                if ($item->type === 'gallery' && (!$item->gallery || !$item->gallery->published)) {
                    return 'Menu „'.$item->title.'” prowadzi do brakującej lub nieopublikowanej galerii.';
                }
                if ($item->type === 'url' && trim((string) $item->url) === '') {
                    return 'Menu „'.$item->title.'” nie ma adresu URL.';
                }
                return null;
            })->filter()->values()->all();

        return view('admin.seo.edit', [
            'settings' => $settings,
            'pages' => $pages,
            'galleries' => $galleries,
            'photoCount' => Photo::count(),
            'missingAlt' => Photo::missingMetadata('alt')->count(),
            'missingTitle' => Photo::missingMetadata('title')->count(),
            'missingDescription' => Photo::missingMetadata('description')->count(),
            'duplicateTitles' => $duplicateTitles,
            'duplicateDescriptions' => $duplicateDescriptions,
            'menuIssues' => $menuIssues,
        ]);
    }

    private function duplicates($entities, string $field): array
    {
        return $entities
            ->filter(fn ($entity) => trim((string) ($entity['meta'][$field] ?? '')) !== '')
            ->groupBy(fn ($entity) => mb_strtolower(trim((string) $entity['meta'][$field])))
            ->filter(fn ($group) => $group->count() > 1)
            ->map(fn ($group) => $group->pluck('label')->values()->all())
            ->values()
            ->all();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'seo_site_name' => ['nullable', 'string', 'max:255'],
            'seo_default_title' => ['nullable', 'string', 'max:255'],
            'seo_default_description' => ['nullable', 'string', 'max:2000'],
            'seo_social_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'seo_indexable' => ['required', 'boolean'],
            'home_seo_title' => ['nullable', 'string', 'max:255'],
            'home_seo_description' => ['nullable', 'string', 'max:2000'],
            'home_seo_social_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'home_seo_indexable' => ['sometimes', 'boolean'],
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        });
        return back()->with('success', 'Ustawienia SEO zostały zapisane.');
    }
}
