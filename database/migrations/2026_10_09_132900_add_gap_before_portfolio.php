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
        $portfolioY = null;

        foreach ($sections as $section) {
            if (($section['id'] ?? null) === 'portfolio-heading') {
                $portfolioY = (float) ($section['position_y'] ?? 0);
                break;
            }
        }

        if ($portfolioY === null) return;

        // Add a little more breathing room between "Co tworzę" and Portfolio.
        $extraGap = 6.5;

        foreach ($sections as &$section) {
            $id = (string) ($section['id'] ?? '');
            $isService = str_starts_with($id, 'service-')
                || in_array($id, ['services-heading', 'services-lead'], true);

            if (!$isService && (float) ($section['position_y'] ?? 0) >= $portfolioY) {
                $section['position_y'] = (float) ($section['position_y'] ?? 0) + $extraGap;
            }
        }
        unset($section);

        usort($sections, fn ($a, $b) => ((float) ($a['position_y'] ?? 0)) <=> ((float) ($b['position_y'] ?? 0)));
        $data['sections'] = array_values($sections);

        DB::table('page_builders')
            ->where('id', $home->id)
            ->update([
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Visual spacing refinement only.
    }
};
