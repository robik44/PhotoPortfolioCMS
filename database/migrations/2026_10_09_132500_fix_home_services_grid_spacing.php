<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $home = DB::table('page_builders')
            ->whereNull('page_id')
            ->where('type', 'home')
            ->first();

        if (!$home) return;

        $data = json_decode($home->content ?: '{}', true);
        if (!is_array($data) || !is_array($data['sections'] ?? null)) return;

        $sections = array_values($data['sections']);
        $now = now();

        $servicesY = null;
        $currentPortfolioY = null;

        foreach ($sections as $section) {
            if (($section['id'] ?? null) === 'services-heading') {
                $servicesY = (float) ($section['position_y'] ?? 70);
            }
            if (($section['id'] ?? null) === 'portfolio-heading') {
                $currentPortfolioY = (float) ($section['position_y'] ?? 0);
            }
        }

        if ($servicesY === null || $currentPortfolioY === null) return;

        // Exact 2 x 3 service grid. Every image gets its title and description
        // directly below it; service elements are never shifted with Portfolio.
        $cards = [
            'kulinarna' => [6.5,  $servicesY + 13],
            'produktowa' => [36.75, $servicesY + 13],
            'reklamowa' => [67.0, $servicesY + 13],
            'foodstyling' => [6.5,  $servicesY + 48],
            'food-photo' => [36.75, $servicesY + 48],
            'packaging' => [67.0, $servicesY + 48],
        ];

        foreach ($cards as $key => [$x, $y]) {
            $positions = [
                'image' => [$y, 190],
                'title' => [$y + 22.5, 0],
                'text'  => [$y + 27.5, 0],
            ];

            foreach ($positions as $part => [$targetY, $height]) {
                $targetId = 'service-'.$key.'-'.$part;

                foreach ($sections as &$section) {
                    if (($section['id'] ?? null) !== $targetId) continue;

                    $section['position_x'] = $x;
                    $section['position_y'] = $targetY;
                    $section['element_width'] = 26.5;

                    if ($part === 'image') {
                        $section['element_height'] = $height;
                        $section['image_height'] = $height;
                        $section['image_width'] = 100;
                        $section['image_fit'] = 'cover';
                        $section['image_ratio'] = '4 / 3';
                    }
                }
                unset($section);
            }
        }

        // Pull Portfolio up directly below the second service row.
        $desiredPortfolioY = $servicesY + 82;
        $delta = $desiredPortfolioY - $currentPortfolioY;

        foreach ($sections as &$section) {
            $id = (string) ($section['id'] ?? '');
            $isService = str_starts_with($id, 'service-') || in_array($id, ['services-heading', 'services-lead'], true);

            if (!$isService && (float) ($section['position_y'] ?? 0) >= $currentPortfolioY) {
                $section['position_y'] = (float) ($section['position_y'] ?? 0) + $delta;
            }
        }
        unset($section);

        usort($sections, fn ($a, $b) => ((float) ($a['position_y'] ?? 0)) <=> ((float) ($b['position_y'] ?? 0)));

        $data['sections'] = array_values($sections);

        DB::table('page_builders')
            ->where('id', $home->id)
            ->update([
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Layout-only refinement.
    }
};
