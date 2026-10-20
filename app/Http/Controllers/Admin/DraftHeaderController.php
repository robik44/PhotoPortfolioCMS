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
        ]);
        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        return redirect()->route('draft-header.edit')->with('success', 'Zapisano testowy nagłówek. Strona publiczna nie została zmieniona.');
    }

    public function preview()
    {
        $settings = $this->settings();
        $hero = SiteSetting::where('key', 'preview_hero_image')->value('value')
            ?: SiteSetting::where('key', 'hero_image')->value('value');
        $heroUrl = $hero ? asset('storage/'.$hero) : asset('images/foodfoto-carrots-hero.jpg');
        $header = \App\Support\HeaderSettings::resolve(SiteSetting::pluck('value', 'key')->all());
        $menuItems = \App\Models\MenuItem::whereNull('parent_id')->where('published', true)->orderBy('sort_order')->get();
        return view('admin.draft-header.preview', compact('settings', 'heroUrl', 'header', 'menuItems'));
    }
}
