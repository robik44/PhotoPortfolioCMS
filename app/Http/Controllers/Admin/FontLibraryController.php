<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SiteFontLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FontLibraryController extends Controller
{
    public function index(SiteFontLibrary $library)
    {
        return view('admin.fonts.index', ['siteFonts' => $library->catalog()]);
    }

    public function store(Request $request, SiteFontLibrary $library)
    {
        $request->validate(['font_file' => SiteFontLibrary::uploadRules()], [
            'font_file.max' => 'Plik czcionki może mieć maksymalnie 5 MB.',
        ]);
        $library->upload($request->file('font_file'));

        return back()->with('success', 'Czcionka jest dostępna w całej witrynie.');
    }

    public function update(Request $request, SiteFontLibrary $library)
    {
        $rule = ['required', 'string', Rule::in(array_keys($library->catalog()['families']))];
        $data = $request->validate(array_fill_keys(array_keys(SiteFontLibrary::DEFAULTS), $rule));
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        return back()->with('success', 'Domyślne czcionki witryny zostały zapisane.');
    }
}
