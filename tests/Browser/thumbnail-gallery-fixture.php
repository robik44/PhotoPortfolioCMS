<?php

// Real Blade views with isolated, repeatable records. No project database writes.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\URL::forceRootUrl('http://thumbnails.test');
Illuminate\Support\Facades\URL::forceScheme('http');
Illuminate\Support\Facades\URL::useAssetOrigin('http://thumbnails.test');
$app->instance(Illuminate\Foundation\Vite::class, fn () => '');
Illuminate\Support\Facades\Auth::login(App\Models\User::factory()->create());
Illuminate\Support\Facades\View::share('errors', new Illuminate\Support\ViewErrorBag);
$input = json_decode(file_get_contents('php://stdin'), true) ?: [];
for ($i = 1; $i <= 36; $i++) {
    if ($i === ($input['missing'] ?? null)) continue;
    App\Models\Photo::forceCreate(['id' => $i, 'filename' => 'photos/photo-'.$i.'.png', 'thumbnail' => 'thumb-'.$i.'.png',
        'title' => 'Firma '.$i, 'alt' => 'Logo '.$i, 'description' => 'Opis firmy '.$i]);
}
$gallery = App\Models\Gallery::create(['title' => 'Istniejąca galeria', 'slug' => 'galeria']);
$gallery->photos()->attach(36);
$page = App\Models\Page::create(['title' => 'Klienci', 'slug' => 'klienci', 'published' => true]);
$page->builder()->create(['type' => 'page', 'published' => true, 'content' => $input['content'] ?? ['version' => 1, 'sections' => []]]);
$html = ($input['mode'] ?? '') === 'public'
    ? view('pages.show', compact('page'))->render()
    : app(App\Http\Controllers\Admin\PageController::class)->builder($page)->render();
echo str_replace('</head>', '<style>'.file_get_contents(__DIR__.'/../../resources/css/app.css').'</style></head>', $html);
