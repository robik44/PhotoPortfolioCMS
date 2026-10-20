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

        $savedSections = $builder->fresh()->content['sections'] ?? [];
        $thumbnailSettingsCount = collect($savedSections)
            ->where('type', 'thumbnail_gallery')
            ->sum(function ($section) {
                return collect($section['photo_settings'] ?? [])
                    ->filter(fn ($settings) => is_array($settings) && count($settings) > 0)
                    ->count();
            });

        return response()->json([
            "success" => true,
            "message" => "Strona główna została zapisana.",
            "builder_id" => $builder->id,
            "thumbnail_settings_count" => $thumbnailSettingsCount,
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

        // Hero content belongs to the homepage builder, never the shared header.
        // Create missing blocks only: existing user-edited sections are left untouched.
        if ($findIndex($sections, fn ($item) => ($item["id"] ?? null) === "hero-eyebrow") === null) {
            $sections[] = [
                "id" => "hero-eyebrow",
                "type" => "text",
                "content" => $settings["draft_header_eyebrow"] ?? "APETYT NA PIĘKNE HISTORIE",
                "semantic_tag" => "p",
                "position_x" => 7,
                "position_y" => 5,
                "element_width" => 38,
                "style" => [
                    "font_size" => 12,
                    "font_weight" => 400,
                    "color" => "#485641",
                    "letter_spacing" => 4,
                    "line_height" => 1.4,
                ],
            ];
            $changed = true;
        }

        if ($findIndex($sections, fn ($item) => ($item["id"] ?? null) === "hero-portfolio-cta") === null) {
            $sections[] = [
                "id" => "hero-portfolio-cta",
                "type" => "button",
                "content" => $settings["draft_header_cta_text"] ?? "ZOBACZ PORTFOLIO",
                "button_link" => "#portfolio",
                "position_x" => 7,
                "position_y" => 32,
                "element_width" => 24,
                "button_background" => "#597649",
                "button_border_color" => "#597649",
                "button_border_width" => 0,
                "button_radius" => 40,
                "button_padding_x" => 32,
                "button_padding_y" => 17,
                "style" => [
                    "font_size" => 12,
                    "font_weight" => 600,
                    "color" => "#ffffff",
                    "letter_spacing" => 1.2,
                ],
            ];
            $changed = true;
        }

        // A removed Portfolio heading must stay removed. Navigation to the
        // gallery is now anchored to the gallery itself in the public canvas.

        if ($changed) {
            $content["sections"] = $sections;
            $builder->content = $content;
            $builder->save();
        }

        return $builder->fresh();
    }
}
