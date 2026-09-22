<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Photo;

class DashboardController extends Controller
{
    public function index()
    {
        $galleryCount = Gallery::count();
        $photoCount = Photo::count();

        $recentPhotos = Photo::query()
            ->latest()
            ->take(6)
            ->get();

        return view("dashboard", [
            "galleryCount" => $galleryCount,
            "photoCount" => $photoCount,
            "recentPhotos" => $recentPhotos,
        ]);
    }
}
