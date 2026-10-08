<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $pages = [
            'o-mnie' => [
                'seo_title' => 'Fotograf żywności i foodstylista | O mnie | Magda Gugała',
                'seo_description' => 'Fotografia żywności, fotografia kulinarna, stylizacja jedzenia i aranżacja potraw. Poznaj doświadczenie Magdy Gugały w zdjęciach reklamowych i na opakowania.',
            ],
            'kontakt' => [
                'seo_title' => 'Kontakt — fotografia żywności i kulinarna | Magda Gugała',
                'seo_description' => 'Kontakt w sprawie fotografii żywności, fotografii kulinarnej, zdjęć reklamowych i na opakowania oraz stylizacji i aranżacji potraw.',
            ],
            'klienci' => [
                'seo_title' => 'Klienci i realizacje — fotografia żywności | Magda Gugała',
                'seo_description' => 'Realizacje z zakresu fotografii żywności, fotografii kulinarnej, zdjęć produktowych i reklamowych oraz stylizacji jedzenia dla marek i wydawnictw.',
            ],
        ];

        foreach ($pages as $slug => $values) {
            DB::table('pages')
                ->where('slug', $slug)
                ->update($values + ['updated_at' => $now]);
        }

        $galleries = [
            'czasopisma' => [
                'seo_title' => 'Fotografia kulinarna do czasopism | Magda Gugała',
                'seo_description' => 'Fotografia kulinarna i fotografia żywności do czasopism, magazynów i publikacji. Zdjęcia potraw, jedzenia i produktów ze stylizacją i aranżacją.',
            ],
            'opakowania' => [
                'seo_title' => 'Zdjęcia żywności na opakowania | Magda Gugała',
                'seo_description' => 'Fotografia produktowa i reklamowa żywności na opakowania i etykiety. Zdjęcia produktów, potraw i dań ze stylizacją jedzenia i aranżacją kadru.',
            ],
        ];

        foreach ($galleries as $slug => $values) {
            DB::table('galleries')
                ->where('slug', $slug)
                ->update($values + ['updated_at' => $now]);
        }

        // Make sure builder-based core pages expose one real H1 without changing their visual layout.
        $pageIds = DB::table('pages')
            ->whereIn('slug', ['o-mnie', 'kontakt', 'klienci'])
            ->pluck('id', 'slug');

        foreach ($pageIds as $slug => $pageId) {
            $builders = DB::table('page_builders')
                ->where('page_id', $pageId)
                ->get();

            foreach ($builders as $builder) {
                $data = json_decode($builder->content ?? '', true);
                if (!is_array($data) || !is_array($data['sections'] ?? null)) {
                    continue;
                }

                $firstHeadingFound = false;
                $changed = false;

                foreach ($data['sections'] as &$section) {
                    if (($section['type'] ?? null) !== 'heading') {
                        continue;
                    }

                    if (!$firstHeadingFound) {
                        if (($section['semantic_tag'] ?? null) !== 'h1') {
                            $section['semantic_tag'] = 'h1';
                            $changed = true;
                        }
                        $section['heading_level'] = 'h1';
                        $firstHeadingFound = true;
                    } elseif (($section['semantic_tag'] ?? null) === 'h1') {
                        $section['semantic_tag'] = 'h2';
                        $section['heading_level'] = 'h2';
                        $changed = true;
                    }
                }
                unset($section);

                if ($changed) {
                    DB::table('page_builders')
                        ->where('id', $builder->id)
                        ->update([
                            'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'updated_at' => $now,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Editorial/SEO migration: previous values are intentionally not restored.
    }
};
