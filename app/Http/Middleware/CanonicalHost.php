<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only redirect FoodFoto's real production hosts, never local or preview environments.
        $host = strtolower($request->getHost());
        if (app()->environment('production') && in_array($host, ['foodfoto.pl', 'www.foodfoto.pl'], true)
            && ($host !== 'www.foodfoto.pl' || !$request->isSecure())) {
            return redirect()->away('https://www.foodfoto.pl'.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
