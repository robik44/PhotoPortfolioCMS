<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBuilder;
use App\Support\ContentPages;
use Illuminate\Http\Request;
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
            "featured_photo_id" => ["nullable", "integer", "exists:photos,id"],
            "featured_image" => ["prohibited"],
            "published" => ["nullable", "boolean"],
        ] + \App\Support\Seo::rules());

        $slug = $data["slug"] ?? "";

        if ($slug === "") {
            $slug = Str::slug($data["title"]);
        } else {
            $slug = Str::slug($slug);
        }

        $data["slug"] = $slug;
        $data["published"] = $request->boolean("published");
        $data["sort_order"] = ((int) Page::max("sort_order")) + 1;

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

        $preparedStaticContent = isset(ContentPages::PAGES[$page->slug]) && empty($builder->content['sections']);
        if ($preparedStaticContent) {
            // Only change the in-memory preview. The existing JSON remains untouched until Save.
            $builder->content = array_replace($builder->content ?? [], ['sections' => ContentPages::initialContent($page)['sections']]);
        }

        return view("admin.pages.builder", [
            'preparedStaticContent' => $preparedStaticContent,
            "page" => $page,
            "builder" => $builder,
            "publicPageUrl" => isset(ContentPages::PAGES[$page->slug]) ? url('/' . $page->slug) : route("page.public", $page),
            "editPageUrl" => route("pages.edit", $page),
            "builderSaveUrl" => route("pages.builder.save", $page),
        ]);
    }

    public function saveBuilder(Request $request, Page $page)
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
            "featured_photo_id" => ["nullable", "integer", "exists:photos,id"],
            "featured_image" => ["prohibited"],
            "remove_featured_image" => ["nullable", "boolean"],
            "published" => ["nullable", "boolean"],
        ] + \App\Support\Seo::rules());

        $data["slug"] = Str::slug($data["slug"]);
        $data["published"] = $request->boolean("published");

        // Ordinary saves retain legacy paths. Explicit replacement/removal detaches
        // the old reference, but never deletes its file or a library Photo.
        if ($request->boolean("remove_featured_image") || !empty($data["featured_photo_id"])) {
            $data["featured_image"] = null;
        }
        if ($request->boolean("remove_featured_image")) {
            $data["featured_photo_id"] = null;
        }
        unset($data["remove_featured_image"]);

        $page->update($data);

        return redirect()
            ->route("pages.index")
            ->with("success", "Strona została zaktualizowana.");
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route("pages.index")
            ->with("success", "Strona została usunięta.");
    }
}
