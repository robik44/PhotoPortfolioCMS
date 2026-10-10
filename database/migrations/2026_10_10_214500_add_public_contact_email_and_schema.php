<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $currentEmail = trim((string) SiteSetting::where('key', 'contact_email')->value('value'));
        $page = DB::table('pages')->where('slug', 'kontakt')->first();

        if (!$page) return;

        $builders = DB::table('page_builders')->where('page_id', $page->id)->get();

        foreach ($builders as $builder) {
            $data = json_decode($builder->content ?? '', true);
            if (!is_array($data) || !is_array($data['sections'] ?? null)) continue;

            $changed = false;

            foreach ($data['sections'] as &$section) {
                $content = trim((string) ($section['content'] ?? ''));
                if ($content === '') continue;

                if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $content, $match)) {
                    $email = $match[0];

                    if ($currentEmail === '') {
                        $currentEmail = $email;
                        SiteSetting::updateOrCreate(
                            ['key' => 'contact_email'],
                            ['value' => $email]
                        );
                    }

                    if ($content === $email && ($section['type'] ?? null) === 'text') {
                        $section['text_link'] = 'mailto:'.$email;
                        $changed = true;
                    }
                }
            }
            unset($section);

            if ($changed) {
                DB::table('page_builders')
                    ->where('id', $builder->id)
                    ->update([
                        'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Keep the public contact address and link intact.
    }
};
