<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Models\Gallery;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;

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
        ->first(fn ($photo) => $photo->is_cover)
        ?? $galleries->flatMap(fn ($gallery) => $gallery->photos)->first();

    $heroImageUrl = null;

    if (!empty($settings["hero_image"])) {
        $heroImageUrl = asset("storage/" . $settings["hero_image"]);
    } elseif ($heroPhoto) {
        $heroImageUrl = asset(
            "storage/photos/" . basename($heroPhoto->filename)
        );
    }

    return view("welcome", compact(
        "galleries",
        "settings",
        "heroPhoto",
        "heroImageUrl",
        "menuItems"
    ));
});

Route::view("/o-mnie", "about")->name("about");

Route::view("/kontakt", "contact")->name("contact");

Route::get("/portfolio/{gallery}", [GalleryController::class, "publicShow"])
    ->name("portfolio.gallery");

Route::get("/strona/{page:slug}", function (\App\Models\Page $page) {
    abort_unless($page->published, 404);

    return view("pages.show", compact("page"));
})->name("page.public");

Route::middleware(["auth"])->group(function () {
    Route::get("/admin", [DashboardController::class, "index"])
        ->name("dashboard");

    Route::get("/admin/home-builder", [\App\Http\Controllers\Admin\HomeBuilderController::class, "edit"])
        ->name("home-builder.edit");

    Route::post("/admin/home-builder", [\App\Http\Controllers\Admin\HomeBuilderController::class, "save"])
        ->name("home-builder.save");

    Route::resource("galleries", GalleryController::class);

    Route::post("/galleries/reorder", [GalleryController::class, "reorder"])
        ->name("galleries.reorder");

    Route::resource("photos", PhotoController::class);

    Route::post("/photos/reorder", [PhotoController::class, "reorder"])
        ->name("photos.reorder");

    Route::post("/photos/{photo}/make-cover", [PhotoController::class, "makeCover"])
        ->name("photos.make-cover");

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
