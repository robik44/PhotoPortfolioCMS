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
        if ($request->input('blocks') === '[]') $request->merge(['blocks' => []]);
        $data = $request->validate([
            'blocks' => ['present', 'array', 'max:30'],
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

    public function canvas(): Response
    {
        return $this->renderPage(false, true);
    }

    public function saveCanvasAll(Request $request)
    {
        $data = $request->validate([
            'layout' => ['required', 'string', 'max:60000'],
            'texts' => ['required', 'string', 'max:40000'],
            'typography' => ['required', 'string', 'max:25000'],
            'photos' => ['required', 'string', 'max:20000'],
        ]);
        $sets = [];
        foreach ($data as $key => $json) {
            $decoded = json_decode($json, true);
            abort_unless(json_last_error() === JSON_ERROR_NONE && is_array($decoded), 422);
            $sets[$key] = $decoded;
        }
        abort_unless(count($sets['layout']) <= 120 && count($sets['texts']) <= 100
            && count($sets['typography']) <= 60 && count($sets['photos']) <= 80, 422);

        $positions = [];
        foreach ($sets['layout'] as $id => $position) {
            abort_unless(is_string($id) && preg_match('/^[a-z0-9_-]{1,80}$/', $id) && is_array($position), 422);
            abort_unless(isset($position['x'], $position['y']) && is_numeric($position['x']) && is_numeric($position['y']), 422);
            $x = (int) $position['x']; $y = (int) $position['y'];
            $w = (int) ($position['w'] ?? 0); $z = (int) ($position['z'] ?? 0);
            abort_unless(abs($x) <= 350 && abs($y) <= 350 && $w >= 0 && $w <= 1200 && $z >= -5 && $z <= 25, 422);
            $positions[$id] = compact('x', 'y', 'w', 'z');
        }

        $texts = [];
        foreach ($sets['texts'] as $id => $value) {
            abort_unless(is_string($id) && preg_match('/^(preview_[a-z0-9_]+|section-[0-9]{1,2})$/', $id)
                && is_string($value) && mb_strlen($value) <= 2000, 422);
            $texts[$id] = trim(strip_tags($value));
        }

        $typography = [];
        foreach ($sets['typography'] as $id => $style) {
            abort_unless(is_string($id) && preg_match('/^(preview_[a-z0-9_]+|section-[0-9]{1,2})$/', $id) && is_array($style), 422);
            $size = $style['font_size'] ?? null;
            $height = $style['line_height'] ?? null;
            $spacing = $style['letter_spacing'] ?? null;
            abort_unless(is_numeric($size) && $size >= 8 && $size <= 160
                && is_numeric($height) && $height >= .5 && $height <= 3
                && is_numeric($spacing) && $spacing >= -5 && $spacing <= 20, 422);
            $typography[$id] = [
                'font_size' => round((float)$size, 1),
                'line_height' => round((float)$height, 2),
                'letter_spacing' => round((float)$spacing, 2),
            ];
        }

        $photos = [];
        foreach ($sets['photos'] as $slot => $photoId) {
            abort_unless(is_string($slot) && preg_match('/^(hero-photo|editorial-photo|experience-photo|service-[0-9]{1,2}|process-photo-[0-9]|custom-[0-9]{1,2})$/', $slot)
                && is_numeric($photoId) && (int)$photoId > 0, 422);
            $photos[$slot] = (int)$photoId;
        }
        abort_unless(Photo::whereIn('id', array_unique(array_values($photos)))->count()
            === count(array_unique(array_values($photos))), 422);

        \Illuminate\Support\Facades\DB::transaction(function () use ($positions, $texts, $typography, $photos) {
            foreach ([
                'foodfoto_canvas_draft_positions' => $positions,
                'foodfoto_canvas_draft_texts' => $texts,
                'foodfoto_canvas_draft_typography' => $typography,
                'foodfoto_canvas_draft_photos' => $photos,
            ] as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
            }
        });
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Zapisano wszystkie zmiany w wersji roboczej.']);
        }
        return redirect()->to(route('foodfoto.canvas', [], false))->with('foodfoto_all_saved', true);
    }

    public function saveCanvasLayout(Request $request)
    {
        $data = $request->validate(['layout' => ['required', 'string', 'max:60000']]);
        $positions = json_decode($data['layout'], true);
        abort_unless(is_array($positions) && count($positions) <= 120, 422);
        $clean = [];
        foreach ($positions as $id => $position) {
            abort_unless(is_string($id) && preg_match('/^[a-z0-9_-]{1,80}$/', $id), 422);
            abort_unless(is_array($position), 422);
            foreach (['x', 'y'] as $axis) abort_unless(isset($position[$axis]) && is_numeric($position[$axis]), 422);
            $x = (int) $position['x']; $y = (int) $position['y'];
            $w = (int) ($position['w'] ?? 0); $z = (int) ($position['z'] ?? 0);
            abort_unless(abs($x) <= 350 && abs($y) <= 350 && $w >= 0 && $w <= 1200 && $z >= -5 && $z <= 25, 422);
            $clean[$id] = compact('x', 'y', 'w', 'z');
        }
        SiteSetting::updateOrCreate(['key' => 'foodfoto_canvas_draft_positions'], ['value' => json_encode($clean)]);
        return redirect()->route('foodfoto.canvas')->with('foodfoto_layout_saved', true);
    }

    public function saveCanvasTexts(Request $request)
    {
        $data = $request->validate(['texts' => ['required', 'string', 'max:40000']]);
        $texts = json_decode($data['texts'], true);
        abort_unless(is_array($texts) && count($texts) <= 100, 422);
        $clean = [];
        foreach ($texts as $id => $value) {
            abort_unless(is_string($id) && preg_match('/^(preview_[a-z0-9_]+|section-[0-9]{1,2})$/', $id), 422);
            abort_unless(is_string($value) && mb_strlen($value) <= 2000, 422);
            $clean[$id] = trim(strip_tags($value));
        }
        SiteSetting::updateOrCreate(['key' => 'foodfoto_canvas_draft_texts'], [
            'value' => json_encode($clean, JSON_UNESCAPED_UNICODE),
        ]);
        return redirect()->route('foodfoto.canvas')->with('foodfoto_texts_saved', true);
    }

    public function saveCanvasTypography(Request $request)
    {
        $data = $request->validate(['typography' => ['required', 'string', 'max:25000']]);
        $items = json_decode($data['typography'], true);
        abort_unless(is_array($items) && count($items) <= 60, 422);
        $clean = [];
        foreach ($items as $id => $style) {
            abort_unless(is_string($id) && preg_match('/^(preview_[a-z0-9_]+|section-[0-9]{1,2})$/', $id) && is_array($style), 422);
            $size = $style['font_size'] ?? null;
            $height = $style['line_height'] ?? null;
            $spacing = $style['letter_spacing'] ?? null;
            abort_unless(is_numeric($size) && $size >= 8 && $size <= 160, 422);
            abort_unless(is_numeric($height) && $height >= 0.5 && $height <= 3, 422);
            abort_unless(is_numeric($spacing) && $spacing >= -5 && $spacing <= 20, 422);
            $clean[$id] = ['font_size' => round((float)$size, 1), 'line_height' => round((float)$height, 2), 'letter_spacing' => round((float)$spacing, 2)];
        }
        SiteSetting::updateOrCreate(['key' => 'foodfoto_canvas_draft_typography'], ['value' => json_encode($clean)]);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'typography' => $clean]);
        }
        return redirect()->to(route('foodfoto.canvas', [], false))->with('foodfoto_typography_saved', true);
    }

    public function saveCanvasPhotoActions(Request $request)
    {
        $data = $request->validate([
            'actions' => ['required', 'string', 'max:16000'],
        ]);
        $items = json_decode($data['actions'], true);
        abort_unless(is_array($items) && count($items) <= 80, 422);
        $clean = [];
        foreach ($items as $slot => $entry) {
            abort_unless(is_string($slot)
                && preg_match('/^(hero-photo|editorial-photo|experience-photo|service-[0-9]{1,2}|process-photo-[0-9]|custom-[0-9]{1,2})$/', $slot)
                && is_array($entry), 422);
            $mode = $entry['mode'] ?? '';
            abort_unless(in_array($mode, ['none', 'lightbox', 'link'], true), 422);
            $target = trim((string) ($entry['url'] ?? ''));
            if ($mode === 'link') {
                abort_unless(strlen($target) <= 500
                    && preg_match('~^/(?!/)[a-zA-Z0-9/_?=&%.#-]*$~', $target), 422);
            } else {
                $target = '';
            }
            $clean[$slot] = ['mode' => $mode, 'url' => $target];
        }
        SiteSetting::updateOrCreate(['key' => 'foodfoto_canvas_draft_photo_actions'], [
            'value' => json_encode($clean, JSON_UNESCAPED_UNICODE),
        ]);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'actions' => $clean]);
        }
        return redirect()->to(route('foodfoto.canvas', [], false));
    }

    public function saveCanvasPhotos(Request $request)
    {
        $data = $request->validate(['photos' => ['required', 'string', 'max:20000']]);
        $choices = json_decode($data['photos'], true);
        abort_unless(is_array($choices) && count($choices) <= 80, 422);
        $clean = [];
        foreach ($choices as $slot => $photoId) {
            abort_unless(is_string($slot) && preg_match('/^(hero-photo|editorial-photo|experience-photo|service-[0-9]{1,2}|process-photo-[0-9]|custom-[0-9]{1,2})$/', $slot), 422);
            abort_unless(is_numeric($photoId) && (int)$photoId > 0, 422);
            $clean[$slot] = (int) $photoId;
        }
        abort_unless(Photo::whereIn('id', array_values($clean))->count() === count(array_unique(array_values($clean))), 422);
        SiteSetting::updateOrCreate(['key' => 'foodfoto_canvas_draft_photos'], ['value' => json_encode($clean)]);
        return redirect()->route('foodfoto.canvas')->with('foodfoto_photos_saved', true);
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

    private function renderPage(bool $public, bool $canvas = false): Response
    {
        $builder = PageBuilder::query()->whereNull('page_id')->where('type', 'home')->firstOrFail();
        $sections = array_values($builder->content['sections'] ?? []);
        $approvedLayout = count($sections) === 32
            && ($sections[3]['content'] ?? null) === 'Co tworzę'
            && ($sections[24]['type'] ?? null) === 'gallery'
            && ($sections[30]['type'] ?? null) === 'thumbnail_gallery'
            && (int) ($sections[31]['photo_id'] ?? 0) === 96;

        // The published homepage must match the approved production structure.
        // Codespaces can have an older database. Build a read-only visual fixture
        // for the private canvas only, without modifying the real PageBuilder.
        abort_unless($approvedLayout || $canvas, 409, 'The current layout differs from the inspected 32-element homepage.');
        $canvasUsingFixture = $canvas && !$approvedLayout;
        if ($canvasUsingFixture) {
            $source = $sections;
            $images = collect($source)->filter(fn ($section) => ($section['type'] ?? '') === 'image'
                && (int) ($section['photo_id'] ?? 0) > 0)->values();
            $sections = array_fill(0, 32, ['type' => 'text', 'content' => '']);
            $sections[3] = ['type' => 'heading', 'content' => 'Co tworzę'];
            $sections[4] = ['type' => 'text', 'content' => 'Fotografia i stylizacja żywności — podgląd roboczy'];
            foreach ([5, 6, 7, 14, 15, 16, 25, 27, 28, 29, 31] as $index) {
                $candidate = $images->shift();
                $sections[$index] = ['type' => 'image', 'photo_id' => $candidate['photo_id'] ?? 0];
            }
            foreach ([8, 9, 10, 17, 18, 19] as $index) {
                $sections[$index]['content'] = 'Fotografia żywności';
            }
            $sections[24] = ['type' => 'gallery', 'gallery_mode' => 'all'];
            $sections[30] = ['type' => 'thumbnail_gallery', 'photo_ids' => []];
        }

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

        $canvasPhotoActions = $canvas ? json_decode(SiteSetting::where('key', 'foodfoto_canvas_draft_photo_actions')->value('value') ?: '{}', true) : [];
        if (!is_array($canvasPhotoActions)) $canvasPhotoActions = [];

        $canvasPhotos = $canvas ? json_decode(SiteSetting::where('key', 'foodfoto_canvas_draft_photos')->value('value') ?: '{}', true) : [];
        if (!is_array($canvasPhotos)) $canvasPhotos = [];
        $canvasTypography = $canvas ? json_decode(SiteSetting::where('key', 'foodfoto_canvas_draft_typography')->value('value') ?: '{}', true) : [];
        if (!is_array($canvasTypography)) $canvasTypography = [];
        $canvasTexts = $canvas ? json_decode(SiteSetting::where('key', 'foodfoto_canvas_draft_texts')->value('value') ?: '{}', true) : [];
        if (!is_array($canvasTexts)) $canvasTexts = [];

        $editorPositions = json_decode(SiteSetting::where('key', $canvas ? 'foodfoto_canvas_draft_positions' : 'preview_editor_positions')->value('value') ?: '{}', true);
        if (!is_array($editorPositions)) $editorPositions = [];

        $photoOptions = Photo::query()->orderBy('sort_order')->orderBy('id')->get(['id','title','alt','filename']);

        $photoPickerOptions = $canvas ? $photoOptions->map(fn ($photo) => [
            'id' => $photo->id,
            'url' => $photo->imageUrl(),
            'title' => $photo->title ?: $photo->alt ?: 'Fotografia',
        ])->values()->all() : [];

        $response = response()->view('admin.foodfoto-home-preview', compact(
            'sections', 'photos', 'galleries', 'seoLinks', 'thumbs', 'previewDecorations', 'photoOptions', 'editorPositions', 'public', 'customBlocks', 'canvas', 'canvasTexts', 'canvasTypography', 'canvasPhotos', 'canvasPhotoActions', 'photoPickerOptions', 'canvasUsingFixture'
        ));
        if (!$public) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        return $response;
    }
}
