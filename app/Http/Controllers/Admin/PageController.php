<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy("sort_order")
            ->orderBy("title")
            ->get();

        return view("admin.pages.index", compact("pages"));
    }

    public function create()
    {
        return view("admin.pages.create");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => ["required", "string", "max:255"],
            "slug" => ["nullable", "string", "max:255", "unique:pages,slug"],
            "content" => ["nullable", "string"],
            "featured_image" => [
                "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "max:10240",
            ],
            "published" => ["nullable", "boolean"],
        ]);

        $slug = $data["slug"] ?? "";

        if ($slug === "") {
            $slug = Str::slug($data["title"]);
        } else {
            $slug = Str::slug($slug);
        }

        $data["slug"] = $slug;
        $data["published"] = $request->boolean("published");
        $data["sort_order"] = ((int) Page::max("sort_order")) + 1;

        if ($request->hasFile("featured_image")) {
            $data["featured_image"] = $request
                ->file("featured_image")
                ->store("pages", "public");
        }

        Page::create($data);

        return redirect()
            ->route("pages.index")
            ->with("success", "Strona została utworzona.");
    }

    public function show(Page $page)
    {
        return redirect()->route("pages.edit", $page);
    }

    public function edit(Page $page)
    {
        return view("admin.pages.edit", compact("page"));
    }

    public function builder(Page $page)
    {
        $builder = $page->builder;

        if (!$builder) {
            $builder = PageBuilder::create([
                "page_id" => $page->id,
                "type" => "page",
                "content" => [
                    "version" => 1,
                    "settings" => [
                        "background_color" => "#ffffff",
                    ],
                    "sections" => [],
                ],
                "published" => true,
            ]);
        }

        return view("admin.pages.builder", [
            "page" => $page,
            "builder" => $builder,
            "publicPageUrl" => route("page.public", $page),
            "editPageUrl" => route("pages.edit", $page),
            "builderSaveUrl" => route("pages.builder.save", $page),
        ]);
    }

    public function saveBuilder(Request $request, Page $page)
    {
        $data = $request->validate([
            "content" => ["required", "array"],
        ]);

        $builder = PageBuilder::updateOrCreate(
            [
                "page_id" => $page->id,
                "type" => "page",
            ],
            [
                "content" => $data["content"],
                "published" => true,
            ]
        );

        return response()->json([
            "success" => true,
            "message" => "Układ strony został zapisany.",
            "builder_id" => $builder->id,
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            "title" => ["required", "string", "max:255"],
            "slug" => [
                "required",
                "string",
                "max:255",
                "unique:pages,slug," . $page->id,
            ],
            "content" => ["nullable", "string"],
            "featured_image" => [
                "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "max:10240",
            ],
            "remove_featured_image" => ["nullable", "boolean"],
            "published" => ["nullable", "boolean"],
        ]);

        $data["slug"] = Str::slug($data["slug"]);
        $data["published"] = $request->boolean("published");

        if ($request->boolean("remove_featured_image")) {
            if ($page->featured_image) {
                Storage::disk("public")->delete($page->featured_image);
            }

            $data["featured_image"] = null;
        }

        if ($request->hasFile("featured_image")) {
            if ($page->featured_image) {
                Storage::disk("public")->delete($page->featured_image);
            }

            $data["featured_image"] = $request
                ->file("featured_image")
                ->store("pages", "public");
        }

        $page->update($data);

        return redirect()
            ->route("pages.index")
            ->with("success", "Strona została zaktualizowana.");
    }

    public function destroy(Page $page)
    {
        if ($page->featured_image) {
            Storage::disk("public")->delete($page->featured_image);
        }

        $page->delete();

        return redirect()
            ->route("pages.index")
            ->with("success", "Strona została usunięta.");
    }
}
