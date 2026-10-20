<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class DraftHeaderController extends Controller
{
    private const DEFAULTS = [
        'draft_header_gap' => 80,
        'draft_header_radius' => 36,
        'draft_header_height' => 84,
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

    public function savePositions(Request $request)
    {
        $data = $request->validate([
            'positions' => ['required', 'array'],
            'positions.*.x' => ['required', 'integer', 'between:-400,400'],
            'positions.*.y' => ['required', 'integer', 'between:-300,300'],
            'positions.*.w' => ['sometimes', 'integer', 'between:1,1400'],
            'positions.*.h' => ['sometimes', 'integer', 'between:15,180'],
            'positions.*.font' => ['sometimes', 'numeric', 'between:8,120'],
            'positions.*.spacing' => ['sometimes', 'numeric', 'between:-2,20'],
            'positions.*.line' => ['sometimes', 'numeric', 'between:0.7,3'],
        ]);
        $allowed = ['brand', 'brand-image', 'brand-divider', 'brand-text', 'menu-panel', 'navlinks', 'contact', 'eyebrow', 'headline', 'description', 'cta'];
        $positions = array_intersect_key($data['positions'], array_flip($allowed));
        SiteSetting::updateOrCreate(
            ['key' => 'draft_header_builder_positions'],
            ['value' => json_encode($positions)]
        );
        return response()->json(['ok' => true, 'positions' => $positions]);
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
