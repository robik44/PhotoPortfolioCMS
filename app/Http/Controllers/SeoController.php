<?php

namespace App\Http\Controllers;

use App\Models\{Gallery, Page};
use App\Support\{ContentPages, Seo};

class SeoController extends Controller
{
    public function sitemap()
    {
        $urls = [];
        if (Seo::indexing()) {
            $urls[] = url('/');
            foreach (Page::where('published', true)->where('indexable', true)->get() as $page) $urls[] = Seo::pageUrl($page);
            foreach (Gallery::where('published', true)->where('indexable', true)->get() as $gallery) $urls[] = route('portfolio.gallery', $gallery);
            // These public fallback pages can exist before their first CMS edit.
            foreach (ContentPages::PAGES as $slug => $defaults) {
                if (!Page::where('slug', $slug)->exists()) $urls[] = url('/'.$slug);
            }
        }
        return response()->view('sitemap', ['urls' => array_unique($urls)])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $rules = Seo::indexing() ? "Disallow: /admin\nAllow: /\n" : "Disallow: /\n";
        return response("User-agent: *\n".$rules."Sitemap: ".url('/sitemap.xml')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
