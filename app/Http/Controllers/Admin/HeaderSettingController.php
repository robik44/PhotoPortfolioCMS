<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SiteFontLibrary;
use App\Support\HeaderFonts;
use App\Support\HeaderSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeaderSettingController extends Controller
{
    public function edit()
    {
        $stored = SiteSetting::whereIn('key', HeaderSettings::keys())->pluck('value', 'key')->all();
        $settings = HeaderSettings::resolve($stored);
        $customFonts = HeaderFonts::custom($stored);
        $fontFamilies = HeaderFonts::families($customFonts);

        return view('admin.header-settings.edit', compact('settings', 'customFonts', 'fontFamilies'));
    }

    public function update(Request $request, SiteFontLibrary $library)
    {
        $stored = SiteSetting::whereIn('key', HeaderSettings::keys())->pluck('value', 'key')->all();
        $current = HeaderSettings::resolve($stored);
        $fonts = HeaderFonts::custom($stored);
        $validated = $request->validate(HeaderSettings::rules($fonts) + [
            'font_file' => SiteFontLibrary::uploadRules(false),
            'font_target' => ['required_with:font_file', 'in:logo,subtitle,both'],
        ], [
            'font_file.max' => 'Plik czcionki może mieć maksymalnie 5 MB.',
            'header_logo_font_family.in' => 'Wybierz czcionkę logo z listy.',
            'header_subtitle_font_family.in' => 'Wybierz czcionkę podtytułu z listy.',
            'header_padding_top.required' => 'Podaj odstęp nad logo i menu.',
            'header_padding_top.integer' => 'Odstęp nad logo i menu musi być liczbą całkowitą.',
            'header_padding_top.between' => 'Odstęp nad logo i menu musi mieścić się w zakresie od 0 do 160 px.',
            'header_padding_bottom.required' => 'Podaj odstęp pod logo i menu.',
            'header_padding_bottom.integer' => 'Odstęp pod logo i menu musi być liczbą całkowitą.',
            'header_padding_bottom.between' => 'Odstęp pod logo i menu musi mieścić się w zakresie od 0 do 160 px.',
        ]);
        $data = array_intersect_key($validated, HeaderSettings::DEFAULTS);
        $data['logo_subtitle'] = $data['logo_subtitle'] ?? '';
        foreach (['header_padding_top', 'header_padding_bottom'] as $key) {
            $data[$key] = $data[$key] ?? $current[$key];
        }

        $saveSettings = function (?string $id = null) use ($data, $validated) {
            if ($id) {
                foreach (['logo', 'subtitle'] as $part) {
                    if (in_array($validated['font_target'], [$part, 'both'], true)) {
                        $data['header_' . $part . '_font_family'] = $id;
                    }
                }
            }
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        };
        if ($request->hasFile('font_file')) {
            $library->upload($request->file('font_file'), $saveSettings);
        } else {
            DB::transaction(fn () => $saveSettings());
        }

        return redirect()->route('header-settings.edit')
            ->with('success', 'Globalny nagłówek został zapisany.');
    }
}
