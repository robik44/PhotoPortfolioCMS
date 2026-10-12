<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegacyRedirect;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LegacyRedirectController extends Controller
{
    public function index()
    {
        return view('admin.seo.redirects', [
            'redirects' => LegacyRedirect::orderBy('source_path')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        LegacyRedirect::create($data);
        return back()->with('success', 'Przekierowanie dodane.');
    }

    public function update(Request $request, LegacyRedirect $redirect)
    {
        $data = $this->validated($request, $redirect->id);
        $redirect->update($data);
        return back()->with('success', 'Przekierowanie zapisane.');
    }

    public function destroy(LegacyRedirect $redirect)
    {
        $redirect->delete();
        return back()->with('success', 'Przekierowanie usunięte.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'source_path' => ['required', 'string', 'max:255', 'regex:~^/(?!/)[^?#]*$~'],
            'destination_path' => ['required', 'string', 'max:255', 'regex:~^/(?!/)[^?#]*$~'],
            'enabled' => ['nullable', 'boolean'],
        ]);
        $source = rtrim($data['source_path'], '/') ?: '/';
        $destination = rtrim($data['destination_path'], '/') ?: '/';
        if ($source === '/' || $source === $destination || str_starts_with($source, '/admin')
            || str_starts_with($source, '/login') || str_starts_with($source, '/logout')
            || in_array($source, ['/robots.txt', '/sitemap.xml'], true)) {
            throw ValidationException::withMessages(['source_path' => 'Nieprawidłowy adres źródłowy.']);
        }
        if (LegacyRedirect::where('source_path', $source)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            throw ValidationException::withMessages(['source_path' => 'Ten adres źródłowy już istnieje.']);
        }
        // Prevent redirects into CMS/auth and loops via other configured redirects.
        if (str_starts_with($destination, '/admin') || str_starts_with($destination, '/login')) {
            throw ValidationException::withMessages(['destination_path' => 'Nie przekierowuj na panel administracyjny.']);
        }
        $seen = [$source => true];
        $current = $destination;
        for ($i = 0; $i < 30; $i++) {
            if (isset($seen[$current])) {
                throw ValidationException::withMessages(['destination_path' => 'Wykryto pętlę przekierowań.']);
            }
            $seen[$current] = true;
            $next = LegacyRedirect::where('source_path', $current)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->where('enabled', true)->value('destination_path');
            if (!$next) break;
            $current = $next;
        }
        return ['source_path' => $source, 'destination_path' => $destination,
            'enabled' => (bool) ($data['enabled'] ?? false)];
    }
}
