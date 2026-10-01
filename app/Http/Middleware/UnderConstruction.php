<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnderConstruction
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (string) SiteSetting::where('key', 'site_under_construction')->value('value') === '1';

        if (!$enabled || auth()->check()
            || $request->routeIs('login', 'logout', 'password.*', 'verification.*')
            || $request->is('robots.txt', 'sitemap.xml', 'up')) {
            return $next($request);
        }

        return response()->view('under-construction', status: 503)
            ->header('Retry-After', '3600');
    }
}
