<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\PageBuilder;
use Illuminate\Http\Request;

class DraftHeaderController extends Controller
{
    private const DEFAULTS = [
        'draft_header_gap' => 80,
        'draft_header_radius' => 36,
        'draft_header_height' => 84,
        'draft_header_hero_height' => 650,
        'draft_header_logo_background' => '#ffffff',
        'draft_header_logo_opacity' => 90,
        'draft_header_menu_background' => '#ffffff',
        'draft_header_hero_x' => 50,
        'draft_header_preview_image' => '',
        'draft_header_builder_positions' => '{}',
        'draft_header_eyebrow' => 'APETYT NA PIĘKNE HISTORIE',
        'draft_header_cta_text' => 'ZOBACZ PORTFOLIO',
        'draft_header_contact_text' => 'POROZMAWIAJMY',
        'draft_header_hero_title' => "Fotografia\ni stylizacja żywności",
        'draft_header_hero_description' => 'Tworzę apetyczne obrazy, które budują marki, opowiadają historie i inspirują do dobrego jedzenia.',
    ];

    private function settings(): array
    {
        $stored = SiteSetting::whereIn('key', array_keys(self::DEFAULTS))->pluck('value', 'key')->all();
        return array_merge(self::DEFAULTS, $stored);
    }

    public function edit()
    {
        return view('admin.draft-header.edit', ['settings' => $this->settings()]);
    }

    public function publicationReview()
    {
        // Read-only publication checklist. No public templates or flags are changed.
        $saved = SiteSetting::pluck('value', 'key')->all();
        $homeDraft = PageBuilder::query()->whereNull('page_id')
            ->where('type', 'home_hero_draft')->first();
        $pages = \App\Models\Page::query()->orderBy('sort_order')
            ->orderBy('title')->get()->map(function ($page) use ($saved) {
                $variant = $saved['draft_page_header_variant_'.$page->id] ?? 'simple';
                $raw = $saved['draft_page_banner_'.$page->id] ?? null;
                $draft = is_string($raw) ? json_decode($raw, true) : null;
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'variant' => $variant === 'full' ? 'full' : 'simple',
                    'has_own_banner' => is_array($draft) && count($draft) > 0,
                    'published' => (bool) $page->published,
                ];
            });
        return view('admin.draft-header.publication-review', [
            'pages' => $pages,
            'home_variant' => ($saved['draft_page_header_variant_home'] ?? 'full') === 'simple' ? 'simple' : 'full',
            'header_live' => ($saved['foodfoto_draft_header_live'] ?? '0') === '1',
            'new_home_system_live' => ($saved['foodfoto_new_home_published'] ?? '0') === '1',
            'home_draft_ready' => $homeDraft !== null && count($homeDraft->content['sections'] ?? []) > 0,
        ]);
    }

    /**
     * Explicit opt-in publication. The old header and all existing builder
     * content remain untouched. Rollback only flips the same independent flag.
     */
    public function publishDraftHeader(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'in:PUBLIKUJ'],
        ], ['confirmation.in' => 'Wpisz dokładnie PUBLIKUJ, aby potwierdzić publikację.']);

        $settings = SiteSetting::pluck('value', 'key')->all();
        if (($settings['foodfoto_new_home_published'] ?? '0') === '1') {
            return back()->withErrors([
                'publication' => 'Aktywny jest inny system nowej strony głównej. Najpierw trzeba uzgodnić, który z nich ma być publikowany.',
            ]);
        }

        $homeVariant = ($settings['draft_page_header_variant_home'] ?? 'full') === 'simple' ? 'simple' : 'full';
        $homeDraft = PageBuilder::whereNull('page_id')->where('type', 'home_hero_draft')->first();
        if ($homeVariant === 'full' && (!$homeDraft || count($homeDraft->content['sections'] ?? []) < 4)) {
            return back()->withErrors([
                'publication' => 'Pełny baner strony głównej nie ma zapisanej kompletnej wersji roboczej.',
            ]);
        }

        $photo = $homeDraft?->content['preview_image']
            ?: ($settings['draft_header_preview_image'] ?? null);
        if ($homeVariant === 'full' && (!$photo || !\Illuminate\Support\Facades\Storage::disk('public')->exists($photo))) {
            return back()->withErrors([
                'publication' => 'Brak zdjęcia pełnego banera strony głównej na dysku publicznym.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            SiteSetting::updateOrCreate(
                ['key' => 'foodfoto_draft_header_live'],
                ['value' => '1']
            );
        });

        return redirect()->route('draft-header.review')
            ->with('success', 'Nowy nagłówek został opublikowany. Możesz go cofnąć jednym działaniem.');
    }

    public function rollbackDraftHeader(Request $request)
    {
        $request->validate([
            'confirmation' => ['required', 'in:COFNIJ'],
        ], ['confirmation.in' => 'Wpisz dokładnie COFNIJ, aby przywrócić poprzedni nagłówek.']);

        \Illuminate\Support\Facades\DB::transaction(function () {
            SiteSetting::updateOrCreate(
                ['key' => 'foodfoto_draft_header_live'],
                ['value' => '0']
            );
        });

        return redirect()->route('draft-header.review')
            ->with('success', 'Przywrócono poprzedni wygląd. Nowe ustawienia pozostały zapisane jako robocze.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'draft_header_gap' => ['required', 'integer', 'between:0,240'],
            'draft_header_radius' => ['required', 'integer', 'between:0,100'],
            'draft_header_height' => ['required', 'integer', 'between:60,180'],
            'draft_header_logo_background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'draft_header_logo_opacity' => ['required', 'integer', 'between:0,100'],
            'draft_header_menu_background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'draft_header_hero_x' => ['required', 'integer', 'between:0,100'],
            'draft_header_eyebrow' => ['required', 'string', 'max:120'],
            'draft_header_cta_text' => ['required', 'string', 'max:80'],
            'draft_header_contact_text' => ['required', 'string', 'max:80'],
            'draft_header_hero_title' => ['required', 'string', 'max:180'],
            'draft_header_hero_description' => ['required', 'string', 'max:500'],
            'draft_header_preview_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'draft_header_remove_preview_image' => ['sometimes', 'boolean'],
        ]);
        unset($data['draft_header_preview_image_file'], $data['draft_header_remove_preview_image']);
        if ($request->boolean('draft_header_remove_preview_image')) {
            $data['draft_header_preview_image'] = '';
        }
        if ($request->hasFile('draft_header_preview_image_file')) {
            $data['draft_header_preview_image'] = $request->file('draft_header_preview_image_file')->store('draft-header', 'public');
        }
        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        return redirect()->route('draft-header.edit')->with('success', 'Zapisano testowy nagłówek. Strona publiczna nie została zmieniona.');
    }

    /**
     * One shared compact layout for all simple page headers and the
     * scrolled-down sticky header. Does not change the full hero settings.
     */
    public function compactEditor()
    {
        $settings = $this->settings();
        $saved = SiteSetting::where('key', 'draft_compact_header_layout')->value('value');
        $compactLayout = json_decode($saved ?: '{}', true) ?: [];
        $builderHeaderSettings = SiteSetting::pluck('value', 'key')->all();
        $builderHeaderMenuItems = \App\Models\MenuItem::whereNull('parent_id')
            ->where('published', true)->orderBy('sort_order')->get();
        $headerVariantValue = 'simple';
        $draftPreviewImage = null;
        $pageBannerDraft = [];
        $isPublicDraftHeader = false;
        return view('admin.draft-header.compact-editor', compact(
            'settings', 'compactLayout', 'builderHeaderSettings',
            'builderHeaderMenuItems', 'headerVariantValue', 'draftPreviewImage',
            'pageBannerDraft', 'isPublicDraftHeader'
        ));
    }

    public function saveCompactLayout(Request $request)
    {
        $data = $request->validate([
            'layout' => ['required','array'],
            'layout.logo' => ['required','array'],
            'layout.divider' => ['required','array'],
            'layout.title' => ['required','array'],
            'layout.subtitle' => ['required','array'],
            'layout.menu' => ['required','array'],
            'layout.*.x' => ['required','integer','between:-350,350'],
            'layout.*.y' => ['required','integer','between:-150,150'],
            'layout.logo.size' => ['required','integer','between:25,180'],
            'layout.divider.size' => ['required','integer','between:20,150'],
            'layout.divider.width' => ['required','integer','between:1,8'],
            'layout.title.font' => ['required','numeric','between:12,80'],
            'layout.subtitle.font' => ['required','numeric','between:6,32'],
            'layout.title.spacing' => ['required','numeric','between:-2,20'],
            'layout.subtitle.spacing' => ['required','numeric','between:-2,20'],
            'layout.title.word_spacing' => ['required','numeric','between:-2,30'],
            'layout.subtitle.word_spacing' => ['required','numeric','between:-2,30'],
            'layout.title.color' => ['required','regex:/^#[0-9a-fA-F]{6}$/'],
            'layout.subtitle.color' => ['required','regex:/^#[0-9a-fA-F]{6}$/'],
            'layout.title.weight' => ['required','integer','in:300,400,500,600,700,800'],
            'layout.menu.scale' => ['required','numeric','between:0.7,1.3'],
        ]);
        $allowed = ['logo','divider','title','subtitle','menu'];
        $layout = array_intersect_key($data['layout'], array_flip($allowed));
        SiteSetting::updateOrCreate(
            ['key' => 'draft_compact_header_layout'],
            ['value' => json_encode($layout, JSON_UNESCAPED_UNICODE)]
        );
        return response()->json(['ok' => true, 'layout' => $layout]);
    }

    public function savePositions(Request $request)
    {
        $data = $request->validate([
            'positions' => ['required', 'array'],
            'hero_height' => ['sometimes', 'integer', 'between:480,900'],
            'positions.*.x' => ['required', 'integer', 'between:-400,400'],
            'positions.*.y' => ['required', 'integer', 'between:-300,300'],
            'positions.*.w' => ['sometimes', 'integer', 'between:1,1400'],
            'positions.*.h' => ['sometimes', 'integer', 'between:15,180'],
            'positions.*.rtl' => ['sometimes', 'integer', 'between:0,120'],
            'positions.*.rtr' => ['sometimes', 'integer', 'between:0,120'],
            'positions.*.rbr' => ['sometimes', 'integer', 'between:0,120'],
            'positions.*.rbl' => ['sometimes', 'integer', 'between:0,120'],
            'positions.*.font' => ['sometimes', 'numeric', 'between:8,120'],
            'positions.*.spacing' => ['sometimes', 'numeric', 'between:-2,20'],
            'positions.*.line' => ['sometimes', 'numeric', 'between:0.7,3'],
            'positions.*.subtitle_font' => ['sometimes', 'numeric', 'between:7,48'],
            'positions.*.subtitle_spacing' => ['sometimes', 'numeric', 'between:-2,20'],
            'positions.*.word_spacing' => ['sometimes', 'numeric', 'between:-2,30'],
            'positions.*.subtitle_word_spacing' => ['sometimes', 'numeric', 'between:-2,30'],
            'positions.*.color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'positions.*.subtitle_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'positions.*.weight' => ['sometimes', 'integer', 'in:300,400,500,600,700,800'],
            'positions.*.scene_x_percent' => ['sometimes', 'numeric', 'between:-100,200'],
            'positions.*.scene_y_percent' => ['sometimes', 'numeric', 'between:-100,200'],
        ]);
        $allowed = ['brand', 'brand-image', 'brand-divider', 'brand-text', 'menu-panel', 'navlinks', 'contact', 'eyebrow', 'headline', 'description', 'cta'];
        $positions = array_intersect_key($data['positions'], array_flip($allowed));
        \Illuminate\Support\Facades\DB::transaction(static function () use ($positions, $data) {
            SiteSetting::updateOrCreate(
                ['key' => 'draft_header_builder_positions'],
                ['value' => json_encode($positions)]
            );
            if (array_key_exists('hero_height', $data)) {
                SiteSetting::updateOrCreate(
                    ['key' => 'draft_header_hero_height'],
                    ['value' => (string) $data['hero_height']]
                );
            }
        });
        return response()->json([
            'ok' => true,
            'positions' => $positions,
            'hero_height' => (int) ($data['hero_height'] ?? 650),
        ]);
    }

    public function exportHeroDraft()
    {
        $settings = $this->settings();
        $positions = json_decode($settings['draft_header_builder_positions'] ?? '{}', true) ?: [];
        $get = static fn (string $id) => is_array($positions[$id] ?? null) ? $positions[$id] : [];
        $blocks = [
            ['id'=>'hero-eyebrow','type'=>'text','content'=>$settings['draft_header_eyebrow'],'semantic_tag'=>'p','draft_geometry'=>$get('eyebrow')],
            ['id'=>'hero-heading','type'=>'heading','heading_level'=>'h1','semantic_tag'=>'h1','content'=>$settings['draft_header_hero_title'],'draft_geometry'=>$get('headline')],
            ['id'=>'hero-text','type'=>'text','semantic_tag'=>'p','content'=>$settings['draft_header_hero_description'],'draft_geometry'=>$get('description')],
            ['id'=>'hero-portfolio-cta','type'=>'button','content'=>$settings['draft_header_cta_text'],'button_link'=>'#portfolio','button_background'=>'#597649','draft_geometry'=>$get('cta')],
        ];
        // Separate unpublished staging record. Never mutate the live home PageBuilder.
        PageBuilder::updateOrCreate(
            ['page_id'=>null, 'type'=>'home_hero_draft'],
            ['content'=>['version'=>1,'source'=>'draft-header','sections'=>$blocks,
                'preview_image'=>$settings['draft_header_preview_image'],
                'hero_x'=>(int)$settings['draft_header_hero_x']],
             'published'=>false]
        );
        return redirect()->route('draft-header.edit')
            ->with('success','Zapisano osobną roboczą kopię hero. Publiczna strona i obecny builder pozostały bez zmian.');
    }

    public function homeHeroEditor()
    {
        $draft = PageBuilder::whereNull('page_id')->where('type', 'home_hero_draft')->firstOrFail();
        $content = $draft->content ?: [];
        if (!array_key_exists('extra_sections', $content)) {
            $live = PageBuilder::whereNull('page_id')->where('type', 'home')->first();
            $content['extra_sections'] = array_values(array_filter($live?->content['sections'] ?? [],
                static fn ($section) => !in_array($section['id'] ?? '', ['hero-image','hero-heading','hero-text','hero-eyebrow','hero-portfolio-cta'], true)));
            $draft->content = $content;
            $draft->published = false;
            $draft->save();
        }
        $settings = $this->settings();
        $header = \App\Support\HeaderSettings::resolve(SiteSetting::pluck('value', 'key')->all());
        $menuItems = \App\Models\MenuItem::whereNull('parent_id')->where('published', true)->orderBy('sort_order')->get();
        $headerPositions = json_decode($settings['draft_header_builder_positions'] ?? '{}', true) ?: [];
        return view('admin.draft-header.home-hero-editor', [
            'draft' => $draft->content ?: [], 'settings' => $settings, 'header' => $header,
            'menuItems' => $menuItems, 'headerPositions' => $headerPositions,
            'homeSections' => $content['extra_sections'],
        ]);
    }

    public function saveHomeDraftSections(Request $request)
    {
        $data = $request->validate([
            'texts' => ['required', 'array', 'max:150'],
            'texts.*' => ['nullable', 'string', 'max:10000'],
        ]);
        $draft = PageBuilder::whereNull('page_id')->where('type', 'home_hero_draft')->firstOrFail();
        $content = $draft->content ?: [];
        abort_unless(isset($content['extra_sections']) && is_array($content['extra_sections']), 422);
        foreach ($data['texts'] as $index => $value) {
            abort_unless(ctype_digit((string)$index) && isset($content['extra_sections'][(int)$index]), 422);
            $section = &$content['extra_sections'][(int)$index];
            abort_unless(isset($section['content']) && is_string($section['content']), 422);
            $section['content'] = $value ?? '';
            unset($section);
        }
        $draft->content = $content;
        $draft->published = false;
        $draft->save();
        return response()->json(['ok' => true]);
    }

    public function uploadHomeHeroPhoto(Request $request)
    {
        $data = $request->validate([
            'hero_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $draft = PageBuilder::whereNull('page_id')->where('type', 'home_hero_draft')->firstOrFail();
        $path = $data['hero_photo']->store('home-hero-draft', 'public');
        $content = $draft->content ?: [];
        $content['preview_image'] = $path;
        $draft->content = $content;
        $draft->published = false;
        $draft->save();
        return response()->json(['ok' => true, 'image_url' => asset('storage/'.$path)]);
    }

    public function saveHomeHero(Request $request)
    {
        $data = $request->validate([
            'sections' => ['required', 'array', 'size:4'],
            'sections.*.id' => ['required', 'in:hero-eyebrow,hero-heading,hero-text,hero-portfolio-cta'],
            'sections.*.content' => ['required', 'string', 'max:600'],
            'sections.*.draft_geometry' => ['required', 'array'],
            'sections.*.draft_geometry.x' => ['required', 'integer', 'between:-400,400'],
            'sections.*.draft_geometry.y' => ['required', 'integer', 'between:-300,300'],
            'sections.*.draft_geometry.w' => ['sometimes', 'integer', 'between:40,1400'],
            'sections.*.draft_geometry.font' => ['sometimes', 'numeric', 'between:8,120'],
        ]);
        $draft = PageBuilder::whereNull('page_id')->where('type', 'home_hero_draft')->firstOrFail();
        $content = $draft->content ?: [];
        $old = collect($content['sections'] ?? [])->keyBy('id');
        $ids = array_column($data['sections'], 'id');
        abort_unless(count(array_unique($ids)) === 4, 422);
        $content['sections'] = array_map(static function ($input) use ($old) {
            $original = $old->get($input['id'], []);
            return array_replace($original, ['content' => $input['content'], 'draft_geometry' => $input['draft_geometry']]);
        }, $data['sections']);
        $draft->content = $content;
        $draft->published = false;
        $draft->save();
        return response()->json(['ok' => true]);
    }

    public function preview()
    {
        $settings = $this->settings();
        $hero = $settings['draft_header_preview_image']
            ?: SiteSetting::where('key', 'preview_hero_image')->value('value')
            ?: SiteSetting::where('key', 'hero_image')->value('value');
        $heroUrl = $hero ? asset('storage/'.$hero) : asset('images/foodfoto-carrots-hero.jpg');
        $header = \App\Support\HeaderSettings::resolve(SiteSetting::pluck('value', 'key')->all());
        $menuItems = \App\Models\MenuItem::whereNull('parent_id')->where('published', true)->orderBy('sort_order')->get();
        return view('admin.draft-header.preview', compact('settings', 'heroUrl', 'header', 'menuItems'));
    }
}
