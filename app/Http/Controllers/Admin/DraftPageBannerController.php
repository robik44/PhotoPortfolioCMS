<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class DraftPageBannerController extends Controller
{
    public function save(Request $request, Page $page)
    {
        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'button' => ['nullable', 'string', 'max:80'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'reset_photo' => ['sometimes', 'boolean'],
        ]);

        $key = 'draft_page_banner_'.$page->id;
        $previous = json_decode((string) SiteSetting::where('key', $key)->value('value'), true);
        $draft = is_array($previous) ? $previous : [];

        foreach (['eyebrow', 'title', 'description', 'button'] as $field) {
            $draft[$field] = (string) ($data[$field] ?? '');
        }

        if ($request->boolean('reset_photo')) {
            $draft['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            $draft['photo'] = $request->file('photo')->store('draft-page-banners/'.$page->id, 'public');
        }

        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]
        );

        $photo = ($draft['photo'] ?? null) ?: SiteSetting::where('key', 'draft_header_preview_image')->value('value');

        return response()->json([
            'ok' => true,
            'draft' => $draft,
            'photo_url' => $photo ? asset('storage/'.$photo) : null,
        ]);
    }
}
