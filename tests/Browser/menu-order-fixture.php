<?php
// Render the real CMS view against a disposable in-memory database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\URL::forceRootUrl('http://menu.test');
Illuminate\Support\Facades\URL::forceScheme('http');
Illuminate\Support\Facades\URL::useAssetOrigin('http://menu.test');
$app->instance(Illuminate\Foundation\Vite::class, fn () => '');
Illuminate\Support\Facades\Auth::login(App\Models\User::factory()->create());
foreach ([['A', null], ['B', null], ['C', 1], ['D', 1], ['E', 2]] as [$title, $parent]) {
    App\Models\MenuItem::create(['title' => $title, 'type' => 'url', 'url' => '/'.$title, 'parent_id' => $parent, 'sort_order' => 0, 'published' => true]);
}
$html = app(App\Http\Controllers\Admin\MenuItemController::class)->index()->render();
echo str_replace('</head>', '<style>'.file_get_contents(__DIR__.'/../../resources/css/app.css').'</style></head>', $html);
