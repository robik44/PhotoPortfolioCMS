<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\FontLibraryController;
use App\Http\Controllers\Admin\HeaderSettingController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\ContentPageController;
use App\Models\Gallery;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('robots');

Route::get("/", function () {
    $galleries = Gallery::with("photos")
        ->orderBy("sort_order")
        ->get();

    $settings = SiteSetting::pluck("value", "key")->toArray();

    $menuItems = MenuItem::query()
        ->whereNull("parent_id")
        ->where("published", true)
        ->with([
            "page",
            "gallery",
            "children" => function ($query) {
                $query
                    ->where("published", true)
                    ->with(["page", "gallery"])
                    ->orderBy("sort_order");
            },
        ])
        ->orderBy("sort_order")
        ->get();

    $heroPhoto = $galleries
        ->flatMap(fn ($gallery) => $gallery->photos)
        ->first(fn ($photo) => (bool) $photo->pivot->is_cover)
        ?? $galleries->flatMap(fn ($gallery) => $gallery->photos)->first();

    $heroImageUrl = null;

    if (!empty($settings["hero_image"])) {
        $heroImageUrl = asset("storage/" . $settings["hero_image"]);
    } elseif ($heroPhoto) {
        $heroImageUrl = asset(
            "storage/photos/" . basename($heroPhoto->filename)
        );
    }

    $homeBuilder = \App\Models\PageBuilder::whereNull('page_id')
        ->where('type', 'home')->where('published', true)->first();
    $homeImages = collect($homeBuilder?->content['sections'] ?? [])
        ->where('type', 'image');
    $homeImage = $homeImages->firstWhere('id', 'hero-image') ?? $homeImages->first();
    if ($homeImage) {
        $selectedPhoto = \App\Models\Photo::find($homeImage['photo_id'] ?? null);
        $heroImageUrl = $selectedPhoto?->imageUrl() ?: ($homeImage['photo_url'] ?? $heroImageUrl);
    }

    $homeSections = collect($homeBuilder?->content['sections'] ?? []);
    $homeButtons = $homeSections->where('type', 'button')->values();

    $homeElements = [
        'hero_title' => $homeSections->firstWhere('id', 'hero-heading')
            ?? $homeSections->firstWhere('type', 'heading'),
        'hero_subtitle' => $homeSections->firstWhere('id', 'hero-text')
            ?? $homeSections->firstWhere('type', 'text'),
        'portfolio_title' => $homeSections->firstWhere('id', 'portfolio-heading'),
    ];

    foreach (['hero_title', 'hero_subtitle'] as $key) {
        $element = $homeElements[$key] ?? null;

        if ($element && array_key_exists('content', $element)) {
            $settings[$key] = $element['content'] ?? '';
        }
    }

    return view("welcome", compact(
        "galleries",
        "settings",
        "heroPhoto",
        "heroImageUrl",
        "menuItems",
        "homeButtons",
        "homeElements"
    ));
});

Route::get('/o-mnie', [ContentPageController::class, 'show'])->defaults('slug', 'o-mnie')->name('about');

Route::get('/kontakt', [ContentPageController::class, 'show'])->defaults('slug', 'kontakt')->name('contact');

Route::get("/portfolio/{gallery:slug}", [GalleryController::class, "publicShow"])
    ->name("portfolio.gallery");

Route::get("/strona/{page:slug}", function (\App\Models\Page $page) {
    abort_unless($page->published, 404);

    return view("pages.show", compact("page"));
})->name("page.public");

Route::middleware(["auth"])->group(function () {
    Route::get('/admin/seo', [\App\Http\Controllers\Admin\SeoController::class, 'edit'])->name('seo.edit');
    Route::put('/admin/seo', [\App\Http\Controllers\Admin\SeoController::class, 'update'])->name('seo.update');
    Route::get('/admin/content-pages/{slug}', [ContentPageController::class, 'edit'])
        ->whereIn('slug', ['o-mnie', 'kontakt'])->name('content-pages.edit');
    Route::get('/admin/fonts', [FontLibraryController::class, 'index'])->name('fonts.index');
    Route::post('/admin/fonts', [FontLibraryController::class, 'store'])->name('fonts.store');
    Route::put('/admin/fonts', [FontLibraryController::class, 'update'])->name('fonts.update');

    Route::get('/admin/header-settings', [HeaderSettingController::class, 'edit'])
        ->name('header-settings.edit');

    Route::put('/admin/header-settings', [HeaderSettingController::class, 'update'])
        ->name('header-settings.update');

    Route::get("/admin", [DashboardController::class, "index"])
        ->name("dashboard");

    Route::get("/admin/home-builder", [\App\Http\Controllers\Admin\HomeBuilderController::class, "edit"])
        ->name("home-builder.edit");

    Route::post("/admin/home-builder", [\App\Http\Controllers\Admin\HomeBuilderController::class, "save"])
        ->name("home-builder.save");

    Route::resource("galleries", GalleryController::class);

    Route::post("/galleries/reorder", [GalleryController::class, "reorder"])
        ->name("galleries.reorder");

    Route::get("/galleries/{gallery}/library", [GalleryController::class, "library"])
        ->name("galleries.library");

    Route::post("/galleries/{gallery}/photos", [GalleryController::class, "attachPhotos"])
        ->name("galleries.photos.attach");

    Route::delete("/galleries/{gallery}/photos/{photo}", [GalleryController::class, "detachPhoto"])
        ->name("galleries.photos.detach");

    Route::post("/galleries/{gallery}/photos/{photo}/cover", [GalleryController::class, "makeCover"])
        ->name("galleries.photos.cover");

    Route::post("/galleries/{gallery}/photos/reorder", [GalleryController::class, "reorderPhotos"])
        ->name("galleries.photos.reorder");

    Route::resource("photos", PhotoController::class);

    Route::resource("pages", PageController::class);

    Route::get("/pages/{page}/builder", [PageController::class, "builder"])
        ->name("pages.builder");

    Route::post("/pages/{page}/builder", [PageController::class, "saveBuilder"])
        ->name("pages.builder.save");

    Route::resource("menu", MenuItemController::class)
        ->parameters([
            "menu" => "menuItem",
        ])
        ->names([
            "index" => "menu.index",
            "create" => "menu.create",
            "store" => "menu.store",
            "show" => "menu.show",
            "edit" => "menu.edit",
            "update" => "menu.update",
            "destroy" => "menu.destroy",
        ]);

    Route::post("/menu/reorder", [MenuItemController::class, "reorder"])
        ->name("menu.reorder");

    Route::get("/site-settings", [SiteSettingController::class, "edit"])
        ->name("site-settings.edit");

    Route::put("/site-settings", [SiteSettingController::class, "update"])
        ->name("site-settings.update");
});

require __DIR__ . "/auth.php";
