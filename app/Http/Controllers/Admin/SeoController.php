<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Gallery, Page, Photo, SiteSetting};
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoController extends Controller
{
    public function edit()
    {
        return view('admin.seo.edit', [
            'settings' => Seo::settings(),
            'pages' => Page::with('socialPhoto')->orderBy('title')->get(),
            'galleries' => Gallery::with(['socialPhoto', 'photos'])->orderBy('title')->get(),
            'photoCount' => Photo::count(),
            'missingAlt' => Photo::missingMetadata('alt')->count(),
            'missingTitle' => Photo::missingMetadata('title')->count(),
            'missingDescription' => Photo::missingMetadata('description')->count(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'seo_site_name' => ['nullable', 'string', 'max:255'],
            'seo_default_title' => ['nullable', 'string', 'max:255'],
            'seo_default_description' => ['nullable', 'string', 'max:2000'],
            'seo_social_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'seo_indexable' => ['required', 'boolean'],
            'home_seo_title' => ['nullable', 'string', 'max:255'],
            'home_seo_description' => ['nullable', 'string', 'max:2000'],
            'home_seo_social_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'home_seo_indexable' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        });
        return back()->with('success', 'Ustawienia SEO zostały zapisane.');
    }
}
