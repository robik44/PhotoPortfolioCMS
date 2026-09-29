<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Services\SiteFontLibrary;
use App\Support\HeaderSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'admin.pages.builder', 'pages.show', 'welcome', 'about', 'contact',
            'portfolio.gallery', 'admin.galleries.create', 'admin.galleries.edit', 'admin.photos.edit',
        ], function ($view) {
            $view->with('siteFonts', app(SiteFontLibrary::class)->catalog());
        });
        View::composer(['about', 'contact', 'portfolio.gallery'], function ($view) {
            $view->with('globalHeaderSettings', SiteSetting::whereIn('key', HeaderSettings::keys())
                ->pluck('value', 'key')->all());
            $view->with('globalHeaderMenuItems', MenuItem::query()
                ->whereNull('parent_id')
                ->where('published', true)
                ->with(['page', 'gallery', 'children' => function ($query) {
                    $query->where('published', true)
                        ->with(['page', 'gallery'])->orderBy('sort_order');
                }])
                ->orderBy('sort_order')->get());
        });
    }
}
