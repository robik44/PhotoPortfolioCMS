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
    public function updateLayout(Request $request)
    {
        $data = $request->validate([
            'layout' => ['required', 'string', 'max:16000'],
        ]);
        $layout = json_decode($data['layout'], true);
        abort_unless(is_array($layout) && count($layout) <= 24, 422);
        $valid = ['hero-text', 'hero-photo', 'hero-basil', 'editorial-photo', 'editorial-copy', 'editorial-basil', 'process-intro', 'process-steps', 'process-basil', 'experience-photo', 'experience-copy'];
        $clean = [];
        foreach ($layout as $key => $position) {
            abort_unless(in_array($key, $valid, true) && is_array($position), 422);
            abort_unless(isset($position['x'], $position['y']) && is_numeric($position['x']) && is_numeric($position['y']), 422);
            $x = (int) $position['x'];
            $y = (int) $position['y'];
            abort_unless(abs($x) <= 350 && abs($y) <= 350, 422);
            $width = isset($position['w']) && is_numeric($position['w']) ? (int) $position['w'] : 0;
            $layer = isset($position['z']) && is_numeric($position['z']) ? (int) $position['z'] : 0;
            abort_unless($width >= 0 && $width <= 1200 && $layer >= -5 && $layer <= 25, 422);
            $clean[$key] = ['x' => $x, 'y' => $y, 'w' => $width, 'z' => $layer];
        }
        SiteSetting::updateOrCreate(['key' => 'preview_editor_positions'], ['value' => json_encode($clean)]);
        return redirect()->route('foodfoto.home.preview')->with('foodfoto_layout_saved', true);
    }

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
            'preview_process_title' => ['required', 'string', 'max:160'],
            'preview_process_description' => ['required', 'string', 'max:600'],
            'preview_process_1_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_process_2_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_process_3_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_editorial_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_experience_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_hero_heading_size' => ['required', 'integer', 'between:44,120'],
            'preview_hero_copy_x' => ['required', 'integer', 'between:30,220'],
        ]);

        foreach ($validated as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => trim($value)]);
        }

        return redirect()->route('foodfoto.home.preview')->with('foodfoto_content_saved', true);
    }

    public function updateBlocks(Request $request)
    {
        $data = $request->validate([
            'blocks' => ['required', 'array', 'max:30'],
            'blocks.*.type' => ['required', 'in:heading,text,image,button'],
            'blocks.*.content' => ['nullable', 'string', 'max:2000'],
            'blocks.*.photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'blocks.*.url' => ['nullable', 'string', 'max:500'],
        ]);
        $clean = [];
        foreach ($data['blocks'] as $block) {
            $type = $block['type'];
            if ($type === 'image' && empty($block['photo_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'blocks' => 'Wybierz zdjęcie dla każdego elementu typu Zdjęcie.',
                ]);
            }
            $clean[] = [
                'type' => $type,
                'content' => trim((string) ($block['content'] ?? '')),
                'photo_id' => $type === 'image' ? (int) ($block['photo_id'] ?? 0) : null,
                'url' => $type === 'button' ? trim((string) ($block['url'] ?? '#kontakt')) : null,
            ];
        }
        SiteSetting::updateOrCreate(['key' => 'foodfoto_custom_blocks'], ['value' => json_encode($clean, JSON_UNESCAPED_UNICODE)]);
        return redirect()->route('foodfoto.home.preview')->with('foodfoto_blocks_saved', true);
    }

    public function __invoke(): Response
    {
        return $this->renderPage(false);
    }

    public function publicHome(): Response
    {
        abort_unless(SiteSetting::where('key', 'foodfoto_new_home_published')->value('value') === '1', 404);
        return $this->renderPage(true);
    }

    private function renderPage(bool $public): Response
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

        $previewDecorations = SiteSetting::whereIn('key', ['preview_basil_hero', 'preview_basil_editorial', 'preview_basil_process', 'preview_hero_image', 'preview_hero_title', 'preview_hero_description', 'preview_hero_button_label', 'preview_hero_button_url', 'preview_editorial_title', 'preview_editorial_description', 'preview_editorial_photo_id', 'preview_experience_title', 'preview_experience_description', 'preview_experience_photo_id', 'preview_process_title', 'preview_process_description', 'preview_process_1_photo_id', 'preview_process_2_photo_id', 'preview_process_3_photo_id', 'preview_hero_heading_size', 'preview_hero_copy_x'])->pluck('value', 'key')->all();

        $extraPhotoIds = collect(['preview_editorial_photo_id', 'preview_experience_photo_id', 'preview_process_1_photo_id', 'preview_process_2_photo_id', 'preview_process_3_photo_id'])
            ->map(fn ($key) => $previewDecorations[$key] ?? null)
            ->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique();
        // Preserve numeric photo IDs: merge() renumbers numeric collection keys.
        $photos = $photos->union(Photo::whereIn('id', $extraPhotoIds)->get()->keyBy('id'));

        $customBlocks = json_decode(SiteSetting::where('key', 'foodfoto_custom_blocks')->value('value') ?: '[]', true);
        if (!is_array($customBlocks)) $customBlocks = [];
        $customPhotoIds = collect($customBlocks)->pluck('photo_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $photos = $photos->union(Photo::whereIn('id', $customPhotoIds)->get()->keyBy('id'));

        $editorPositions = json_decode(SiteSetting::where('key', 'preview_editor_positions')->value('value') ?: '{}', true);
        if (!is_array($editorPositions)) $editorPositions = [];

        $photoOptions = Photo::query()->orderBy('sort_order')->orderBy('id')->get(['id','title','alt','filename']);

        $response = response()->view('admin.foodfoto-home-preview', compact(
            'sections', 'photos', 'galleries', 'seoLinks', 'thumbs', 'previewDecorations', 'photoOptions', 'editorPositions', 'public', 'customBlocks'
        ));
        if (!$public) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        return $response;
    }
}
