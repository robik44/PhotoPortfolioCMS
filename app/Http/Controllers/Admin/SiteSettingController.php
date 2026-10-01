<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function edit()
    {
        $defaults = [
            'menu_gallery' => 'GALERIE',
            'menu_about' => 'O MNIE',
            'menu_contact' => 'KONTAKT',
            'hero_title' => 'FOTOGRAFIA TO SPOSÓB PATRZENIA NA ŚWIAT',
            'hero_text' => 'Fotografia kulinarna i artystyczna',
            'galleries_title' => 'GALERIE',
            'footer_text' => 'ROBERT WOŹNIAK FOTOGRAFIA',
            'hero_photo_id' => null,
            'hero_image' => null,
            'site_under_construction' => '0',
        ];

        $settings = array_merge(
            $defaults,
            SiteSetting::pluck('value', 'key')->toArray()
        );

        $photos = Photo::with('galleries')
            ->orderBy('gallery_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.site-settings.edit', compact('settings', 'photos'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'menu_gallery' => ['nullable', 'string', 'max:255'],
            'menu_about' => ['nullable', 'string', 'max:255'],
            'menu_contact' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:500'],
            'hero_text' => ['nullable', 'string', 'max:500'],
            'galleries_title' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'hero_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'site_under_construction' => ['sometimes', 'boolean'],
        ]);

        foreach ([
            'menu_gallery',
            'menu_about',
            'menu_contact',
            'hero_title',
            'hero_text',
            'galleries_title',
            'footer_text',
            'hero_photo_id',
            'background_color',
            'site_under_construction',
        ] as $key) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $data[$key] ?? null]
            );
        }

        if ($request->hasFile('hero_image')) {
            $oldImage = SiteSetting::where('key', 'hero_image')->value('value');

            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            $path = $request->file('hero_image')->store('hero', 'public');

            SiteSetting::updateOrCreate(
                ['key' => 'hero_image'],
                ['value' => $path]
            );
        }

        return redirect()
            ->route('site-settings.edit')
            ->with('success', 'Ustawienia strony zostały zapisane.');
    }
}
