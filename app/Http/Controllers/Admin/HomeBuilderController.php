<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBuilder;
use Illuminate\Http\Request;

class HomeBuilderController extends Controller
{
    public function edit()
    {
        $builder = PageBuilder::whereNull("page_id")
            ->where("type", "home")
            ->first();

        if (!$builder) {
            $builder = PageBuilder::create([
                "page_id" => null,
                "type" => "home",
                "content" => [
                    "version" => 1,
                    "settings" => [],
                    "sections" => [],
                ],
                "published" => true,
            ]);
        }

        return view("admin.pages.builder", [
            "page" => (object) [
                "title" => "Strona główna",
            ],
            "builder" => $builder,
            "publicPageUrl" => url("/"),
            "editPageUrl" => route("home-builder.edit"),
            "builderSaveUrl" => route("home-builder.save"),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            "content" => ["required", "array"],
        ]);

        try {
            \App\Support\BuilderContent::validate($data['content']);
            app(\App\Services\SiteFontLibrary::class)->validateContent($data['content']);
        } catch (\Illuminate\Validation\ValidationException $error) {
            return response()->json(['message' => $error->getMessage(), 'errors' => $error->errors()], 422);
        }

        $builder = PageBuilder::updateOrCreate(
            [
                "page_id" => null,
                "type" => "home",
            ],
            [
                "content" => $data["content"],
                "published" => true,
            ]
        );

        return response()->json([
            "success" => true,
            "message" => "Strona główna została zapisana.",
            "builder_id" => $builder->id,
        ]);
    }
}
