<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBuilder;
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
