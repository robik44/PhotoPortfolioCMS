<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PageController;
use App\Models\Page;
use App\Support\ContentPages;

class ContentPageController extends Controller
{
    public function show(string $slug)
    {
        abort_unless(isset(ContentPages::PAGES[$slug]), 404);
        $page = Page::with('builder')->where('slug', $slug)->where('published', true)->first();
        if ($page && $page->builder?->published && count($page->builder->content['sections'] ?? []) > 0) {
            return view('pages.show', compact('page'));
        }

        return view(ContentPages::PAGES[$slug]['view']);
    }

    public function edit(string $slug, PageController $pages)
    {
        abort_unless(isset(ContentPages::PAGES[$slug]), 404);
        $page = Page::firstOrCreate(['slug' => $slug], [
            'title' => ContentPages::PAGES[$slug]['title'], 'published' => true,
        ]);

        return $pages->builder($page);
    }
}
