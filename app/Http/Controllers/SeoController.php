<?php

namespace App\Http\Controllers;

use App\Models\{Gallery, Page};
use App\Support\{ContentPages, Seo};

class SeoController extends Controller
{
    public function sitemap()
    {
        $entries = [];

        if (Seo::indexing()) {
            if (Seo::homeIndexing()) {
                $entries[url('/')] = ['loc' => url('/'), 'lastmod' => null];
            }

            foreach (Page::where('published', true)->where('indexable', true)->get() as $page) {
                $loc = Seo::pageUrl($page);
                $entries[$loc] = ['loc' => $loc, 'lastmod' => $page->updated_at?->toAtomString()];
            }

            foreach (Gallery::where('published', true)->where('indexable', true)->get() as $gallery) {
                $loc = route('portfolio.gallery', $gallery);
                $entries[$loc] = ['loc' => $loc, 'lastmod' => $gallery->updated_at?->toAtomString()];
            }

            // These public fallback pages can exist before their first CMS edit.
            foreach (ContentPages::PAGES as $slug => $defaults) {
                if (!Page::where('slug', $slug)->exists()) {
                    $loc = url('/'.$slug);
                    $entries[$loc] = ['loc' => $loc, 'lastmod' => null];
                }
            }
        }

        return response()->view('sitemap', ['entries' => array_values($entries)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $rules = Seo::indexing() ? "Disallow: /admin\nAllow: /\n" : "Disallow: /\n";
        return response("User-agent: *\n".$rules."Sitemap: ".url('/sitemap.xml')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
