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
            'footer_text' => 'MAGDA GUGAŁA — FOTOGRAFIA ŻYWNOŚCI',
            'hero_photo_id' => null,
            'hero_image' => null,
            'site_under_construction' => '0',
            'preview_basil_hero' => null,
            'preview_basil_editorial' => null,
            'preview_basil_process' => null,
            'preview_hero_image' => null,
            'preview_hero_title' => 'Apetyt na dobre kadry',
            'preview_hero_description' => 'Fotografia i stylizacja żywności. Fotografia kulinarna i reklamowa dla marek, które chcą smakować obrazem.',
            'preview_hero_button_label' => 'Zobacz portfolio',
            'preview_hero_button_url' => '#portfolio',
            'preview_editorial_title' => 'Więcej niż zwykłe zdjęcia',
            'preview_editorial_description' => 'Światło, kolor i detal tworzą obrazy, które nie tylko pobudzają apetyt, ale też podkreślają charakter produktu i opowiadają historię marki.',
            'preview_experience_title' => 'Od niemal 30 lat z aparatem przy stole',
            'preview_experience_description' => 'Fotografia i stylizacja żywności to połączenie doświadczenia, uważności na detal i umiejętności pokazania charakteru produktu. Każdy kadr powstaje z myślą o konkretnym zastosowaniu.',
            'preview_editorial_photo_id' => null,
            'preview_experience_photo_id' => null,
        ];

        $settings = array_merge(
            $defaults,
            SiteSetting::pluck('value', 'key')->toArray()
        );

        $photos = Photo::with('galleries')
            ->orderBy('gallery_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.site-settings.edit', compact('settings', 'photos'))->with('user', auth()->user());
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
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'hero_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:2048', 'dimensions:min_width=48,min_height=48'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'site_under_construction' => ['sometimes', 'boolean'],
            'preview_basil_hero_file' => ['nullable', 'image', 'mimes:png,webp', 'max:5120'],
            'preview_basil_editorial_file' => ['nullable', 'image', 'mimes:png,webp', 'max:5120'],
            'preview_basil_process_file' => ['nullable', 'image', 'mimes:png,webp', 'max:5120'],
            'preview_hero_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'preview_hero_title' => ['sometimes', 'required', 'string', 'max:160'],
            'preview_hero_description' => ['sometimes', 'required', 'string', 'max:500'],
            'preview_hero_button_label' => ['sometimes', 'required', 'string', 'max:80'],
            'preview_editorial_title' => ['sometimes', 'required', 'string', 'max:600'],
            'preview_editorial_description' => ['sometimes', 'required', 'string', 'max:600'],
            'preview_experience_title' => ['sometimes', 'required', 'string', 'max:600'],
            'preview_experience_description' => ['sometimes', 'required', 'string', 'max:600'],
            'preview_editorial_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_experience_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'preview_hero_button_url' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^(\/[^\s]*|\#[a-zA-Z][a-zA-Z0-9_-]*)$/'],
        ]);

        foreach ([
            'menu_gallery',
            'menu_about',
            'menu_contact',
            'hero_title',
            'hero_text',
            'galleries_title',
            'footer_text',
            'contact_email',
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

        if ($request->hasFile('favicon')) {
            $oldIcon = SiteSetting::where('key', 'favicon')->value('value');
            $path = $request->file('favicon')->store('favicons', 'public');
            SiteSetting::updateOrCreate(['key' => 'favicon'], ['value' => $path]);
            if ($oldIcon) {
                Storage::disk('public')->delete($oldIcon);
            }
        }

        foreach (['hero', 'editorial', 'process'] as $placement) {
            $input = 'preview_basil_'.$placement.'_file';
            if ($request->hasFile($input)) {
                $path = $request->file($input)->store('foodfoto-decorations', 'public');
                SiteSetting::updateOrCreate(['key' => 'preview_basil_'.$placement], ['value' => $path]);
            }
        }

        foreach (['preview_hero_title', 'preview_hero_description', 'preview_hero_button_label', 'preview_hero_button_url'] as $key) {
            if (array_key_exists($key, $data)) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $data[$key]]);
            }
        }

        foreach (['preview_editorial_title', 'preview_editorial_description', 'preview_experience_title', 'preview_experience_description', 'preview_editorial_photo_id', 'preview_experience_photo_id'] as $key) {
            if (array_key_exists($key, $data)) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $data[$key]]);
            }
        }

        if ($request->hasFile('preview_hero_image_file')) {
            $path = $request->file('preview_hero_image_file')->store('foodfoto-preview-hero', 'public');
            SiteSetting::updateOrCreate(['key' => 'preview_hero_image'], ['value' => $path]);
        }

        return redirect()
            ->route('site-settings.edit')
            ->with('success', 'Ustawienia strony zostały zapisane.');
    }
}
