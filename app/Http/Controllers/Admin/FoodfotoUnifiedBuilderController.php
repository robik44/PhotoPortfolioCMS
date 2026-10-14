<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBuilder;
use App\Models\Photo;
use App\Models\SiteSetting;
use App\Support\BuilderContent;
use App\Services\SiteFontLibrary;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FoodfotoUnifiedBuilderController extends Controller
{
    public function edit()
    {
        $draft = PageBuilder::whereNull('page_id')->where('type', 'foodfoto_draft')->first();

        if (!$draft) {
            $live = PageBuilder::whereNull('page_id')->where('type', 'home')->firstOrFail();
            $content = $live->content ?? ['version' => 1, 'settings' => [], 'sections' => []];
            $settings = SiteSetting::pluck('value', 'key');
            $sections = array_values($content['sections'] ?? []);

            foreach ($sections as &$section) {
                if (($section['id'] ?? '') === 'hero-heading') {
                    $section['content'] = $settings['preview_hero_title'] ?? $section['content'] ?? '';
                } elseif (($section['id'] ?? '') === 'hero-text') {
                    $section['content'] = $settings['preview_hero_description'] ?? $section['content'] ?? '';
                }
            }
            unset($section);

            foreach ([
                'preview_editorial_title' => 'heading',
                'preview_editorial_description' => 'text',
                'preview_process_title' => 'heading',
                'preview_process_description' => 'text',
                'preview_experience_title' => 'heading',
                'preview_experience_description' => 'text',
            ] as $key => $type) {
                if (!empty($settings[$key])) {
                    $sections[] = [
                        'id' => 'foodfoto-' . str_replace('preview_', '', $key),
                        'type' => $type,
                        'content' => $settings[$key],
                        'position_x' => 8,
                        'position_y' => 10 + (count($sections) * 6),
                        'element_width' => $type === 'heading' ? 60 : 50,
                        'style' => ['font_size' => $type === 'heading' ? 48 : 18, 'line_height' => 1.3],
                    ];
                }
            }
            // Import the editorial photo choices and process cards into selectable
            // elements of the existing builder without changing the live homepage.
            $photoMap = [
                'preview_editorial_photo_id' => 25,
                'preview_experience_photo_id' => 6,
                'preview_process_1_photo_id' => 5,
                'preview_process_2_photo_id' => 14,
                'preview_process_3_photo_id' => 16,
            ];
            foreach ($photoMap as $settingKey => $fallbackIndex) {
                $id = (int) ($settings[$settingKey] ?? ($sections[$fallbackIndex]['photo_id'] ?? 0));
                if ($id <= 0) continue;
                $sections[] = [
                    'id' => 'foodfoto-' . str_replace('preview_', '', $settingKey),
                    'type' => 'image',
                    'photo_id' => $id,
                    'position_x' => 10,
                    'position_y' => 20 + count($sections) * 5,
                    'element_width' => 42,
                    'style' => [],
                ];
            }
            foreach ([
                ['process-1-title', 'Koncepcja'],
                ['process-1-description', 'Pomysł, kompozycja i kierunek wizualny.'],
                ['process-2-title', 'Realizacja'],
                ['process-2-description', 'Stylizacja produktu i praca ze światłem.'],
                ['process-3-title', 'Rezultaty'],
                ['process-3-description', 'Zdjęcia przygotowane do publikacji i kampanii.'],
            ] as [$id, $label]) {
                $heading = str_ends_with($id, '-title');
                $sections[] = [
                    'id' => 'foodfoto-' . $id,
                    'type' => $heading ? 'heading' : 'text',
                    'content' => $label,
                    'position_x' => 10,
                    'position_y' => 20 + count($sections) * 5,
                    'element_width' => 40,
                    'style' => ['font_size' => $heading ? 23 : 15],
                ];
            }

            foreach (json_decode($settings['foodfoto_custom_blocks'] ?? '[]', true) ?: [] as $i => $block) {
                $type = $block['type'] ?? null;
                if (!in_array($type, ['heading', 'text', 'button', 'image'], true)) continue;
                $section = [
                    'id' => 'foodfoto-added-'.$i,
                    'type' => $type,
                    'content' => $block['content'] ?? '',
                    'position_x' => 10,
                    'position_y' => 20 + count($sections) * 5,
                    'element_width' => 50,
                    'style' => [],
                ];
                if ($type === 'image') $section['photo_id'] = $block['photo_id'] ?? null;
                if ($type === 'button') $section['url'] = $block['url'] ?? '#kontakt';
                $sections[] = $section;
            }
            // The older visual canvas uses photo_url for its inline preview.
            // Resolve it from the same photo IDs used by the public website.
            $photoIds = collect($sections)->where('type', 'image')
                ->pluck('photo_id')->filter()->unique()->values();
            $photoLookup = Photo::whereIn('id', $photoIds)->get()->keyBy('id');
            foreach ($sections as &$section) {
                if (($section['type'] ?? '') !== 'image') continue;
                $photo = $photoLookup->get((int) ($section['photo_id'] ?? 0));
                if ($photo) {
                    $section['photo_url'] = $photo->imageUrl();
                    $section['photo_title'] = $photo->alt ?: $photo->title ?: 'Fotografia żywności';
                }
            }
            unset($section);
            $content['sections'] = $sections;
            $draft = new PageBuilder([
                'page_id' => null,
                'type' => 'foodfoto_draft',
                'published' => false,
                'content' => $content,
            ]);
        }

        return view('admin.pages.builder', [
            'page' => (object)['title' => 'FoodFoto — builder roboczy'],
            'builder' => $draft,
            'publicPageUrl' => url('/'),
            'editPageUrl' => route('foodfoto.home.preview'),
            'builderSaveUrl' => route('foodfoto.builder.save'),
            'isHomeBuilder' => true,
            'isFoodfotoDraft' => true,
        ]);
    }

    public function save(Request $request, SiteFontLibrary $library)
    {
        $data = $request->validate(['content' => ['required', 'array']]);
        BuilderContent::validate($data['content']);
        $library->validateContent($data['content']);

        PageBuilder::updateOrCreate(
            ['page_id' => null, 'type' => 'foodfoto_draft'],
            ['content' => $data['content'], 'published' => false]
        );
        return response()->json([
            'success' => true,
            'message' => 'Zapisano roboczy projekt; publiczna strona pozostała bez zmian.',
        ]);
    }
}
