<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBuilder;
use App\Models\SiteSetting;
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

        $builder = $this->ensureEditableHomepageElements($builder);

        return view("admin.pages.builder", [
            "page" => (object) [
                "title" => "Strona główna",
            ],
            "builder" => $builder,
            "publicPageUrl" => url("/"),
            "editPageUrl" => route("home-builder.edit"),
            "builderSaveUrl" => route("home-builder.save"),
            "isHomeBuilder" => true,
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

    private function ensureEditableHomepageElements(PageBuilder $builder): PageBuilder
    {
        $content = $builder->content ?: [
            "version" => 1,
            "settings" => [],
            "sections" => [],
        ];

        $content["version"] ??= 1;
        $content["settings"] ??= [];
        $sections = array_values($content["sections"] ?? []);
        $settings = SiteSetting::pluck("value", "key")->toArray();
        $changed = false;

        $findIndex = static function (array $items, callable $callback): ?int {
            foreach ($items as $index => $item) {
                if ($callback($item)) {
                    return $index;
                }
            }

            return null;
        };

        $heroImageIndex = $findIndex($sections, fn ($item) => ($item["id"] ?? null) === "hero-image");
        if ($heroImageIndex === null) {
            $heroImageIndex = $findIndex($sections, fn ($item) => ($item["type"] ?? null) === "image");
            if ($heroImageIndex !== null) {
                $sections[$heroImageIndex]["id"] = "hero-image";
                $changed = true;
            }
        }

        $heroHeadingIndex = $findIndex($sections, fn ($item) => ($item["id"] ?? null) === "hero-heading");
        if ($heroHeadingIndex === null) {
            $heroHeadingIndex = $findIndex($sections, fn ($item) =>
                ($item["type"] ?? null) === "heading"
                && ($item["id"] ?? null) !== "portfolio-heading"
                && mb_strtolower(trim((string) ($item["content"] ?? ""))) !== "portfolio"
            );

            if ($heroHeadingIndex !== null) {
                $sections[$heroHeadingIndex]["id"] = "hero-heading";
                $changed = true;
            }
        }

        if ($heroHeadingIndex === null) {
            $sections[] = [
                "id" => "hero-heading",
                "type" => "heading",
                "content" => $settings["hero_title"] ?? "Fotografia i stylizacja żywności",
                "heading_level" => "h1",
                "semantic_tag" => "h1",
                "position_x" => 7,
                "position_y" => 10,
                "element_width" => 52,
                "style" => [
                    "font_size" => 72,
                    "font_weight" => 400,
                    "color" => "#ffffff",
                    "text_align" => "left",
                    "line_height" => 1.05,
                    "letter_spacing" => 0,
                ],
            ];
            $changed = true;
        } elseif (empty($sections[$heroHeadingIndex]["semantic_tag"])) {
            $sections[$heroHeadingIndex]["semantic_tag"] = "h1";
            $changed = true;
        }

        $heroTextIndex = $findIndex($sections, fn ($item) => ($item["id"] ?? null) === "hero-text");
        if ($heroTextIndex === null) {
            $heroTextIndex = $findIndex($sections, fn ($item) => ($item["type"] ?? null) === "text");

            if ($heroTextIndex !== null) {
                $sections[$heroTextIndex]["id"] = "hero-text";
                $changed = true;
            }
        }

        if ($heroTextIndex === null) {
            $sections[] = [
                "id" => "hero-text",
                "type" => "text",
                "semantic_tag" => "p",
                "content" => $settings["hero_subtitle"] ?? $settings["site_subtitle"] ?? "Fotografia kulinarna i artystyczna",
                "position_x" => 7,
                "position_y" => 20,
                "element_width" => 45,
                "style" => [
                    "font_size" => 18,
                    "font_weight" => 400,
                    "color" => "#ffffff",
                    "text_align" => "left",
                    "line_height" => 1.4,
                    "letter_spacing" => 0,
                ],
            ];
            $changed = true;
        } elseif (empty($sections[$heroTextIndex]["semantic_tag"])) {
            $sections[$heroTextIndex]["semantic_tag"] = "p";
            $changed = true;
        }

        $portfolioHeadingIndex = $findIndex($sections, fn ($item) => ($item["id"] ?? null) === "portfolio-heading");

        if ($portfolioHeadingIndex === null) {
            $portfolioHeadingIndex = $findIndex($sections, fn ($item) =>
                ($item["type"] ?? null) === "heading"
                && mb_strtolower(trim((string) ($item["content"] ?? ""))) === "portfolio"
            );

            if ($portfolioHeadingIndex !== null) {
                $sections[$portfolioHeadingIndex]["id"] = "portfolio-heading";
                $changed = true;
            }
        }

        if ($portfolioHeadingIndex === null) {
            $sections[] = [
                "id" => "portfolio-heading",
                "type" => "heading",
                "content" => "Portfolio",
                "heading_level" => "h2",
                "semantic_tag" => "h2",
                "position_x" => 7,
                "position_y" => 90,
                "element_width" => 40,
                "style" => [
                    "font_size" => 42,
                    "font_weight" => 400,
                    "color" => "#222222",
                    "text_align" => "left",
                    "line_height" => 1.2,
                    "letter_spacing" => 0,
                ],
            ];
            $changed = true;
        } elseif (empty($sections[$portfolioHeadingIndex]["semantic_tag"])) {
            $sections[$portfolioHeadingIndex]["semantic_tag"] = "h2";
            $changed = true;
        }

        if ($changed) {
            $content["sections"] = $sections;
            $builder->content = $content;
            $builder->save();
        }

        return $builder->fresh();
    }
}
