<?php

// Render the real edit form against a disposable database, never the project DB.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\URL::forceRootUrl('http://picker.test');
Illuminate\Support\Facades\URL::forceScheme('http');
Illuminate\Support\Facades\URL::useAssetOrigin('http://picker.test');
$app->instance(Illuminate\Foundation\Vite::class, fn () => '');
Illuminate\Support\Facades\Auth::login(App\Models\User::factory()->create());
Illuminate\Support\Facades\View::share('errors', new Illuminate\Support\ViewErrorBag);
App\Models\Photo::create(['filename' => 'photos/uuid-one.jpg', 'thumbnail' => 'one-thumb.jpg', 'title' => 'Żywność', 'alt' => 'Jabłko', 'description' => 'Sad']);
App\Models\Photo::create(['filename' => 'photos/uuid-two.jpg', 'title' => 'Kawa', 'alt' => 'Espresso', 'description' => 'Palarnia']);
for ($i = 3; $i <= 25; $i++) App\Models\Photo::create(['filename' => 'photos/uuid-'.$i.'.jpg']);
$page = App\Models\Page::create(['title' => 'Klienci', 'slug' => 'klienci', 'featured_image' => 'pages/legacy.jpg', 'published' => true]);
$html = app(App\Http\Controllers\Admin\PageController::class)->edit($page)->render();
echo str_replace('</head>', '<style>'.file_get_contents(__DIR__.'/../../resources/css/app.css').'</style></head>', $html);
