<?php

namespace App\Http\Middleware;

use App\Models\LegacyRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyRedirects
{
    private const DEFAULTS = [
        '/reklamowe.html' => '/strona/fotografia-reklamowa-zywnosci',
        '/gallery.html' => '/portfolio/zywnosc',
        '/etykiety-2-2.html' => '/portfolio/opakowania',
        '/przyprawy-p.html' => '/portfolio/kamis-przyprawy',
        '/contacts.html' => '/kontakt',
        '/privacy-policy.html' => '/polityka-prywatnosci',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        $destination = null;
        // No DB query until the migration has run; defaults still work beforehand.
        if (\Illuminate\Support\Facades\Schema::hasTable('legacy_redirects')) {
            $destination = LegacyRedirect::where('source_path', $path)
                ->where('enabled', true)->value('destination_path');
        }
        $destination ??= self::DEFAULTS[$path] ?? null;

        if ($destination !== null && $destination !== $path) {
            return redirect($destination, 301);
        }
        return $next($request);
    }
}
