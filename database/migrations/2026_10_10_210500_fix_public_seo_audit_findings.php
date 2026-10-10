<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Privacy page: do not inherit homepage SEO.
        DB::table('pages')
            ->where('slug', 'polityka-prywatnosci')
            ->update([
                'seo_title' => 'Polityka prywatności | FoodFoto – Magda Gugała',
                'seo_description' => 'Polityka prywatności serwisu FoodFoto. Informacje o przetwarzaniu danych osobowych, zasadach kontaktu i ochronie prywatności użytkowników.',
                'updated_at' => $now,
            ]);

        // Distinguish the service landing page from the portfolio gallery.
        DB::table('pages')
            ->where('slug', 'zdjecia-na-opakowania')
            ->update([
                'seo_title' => 'Fotografia na opakowania produktów spożywczych | Magda Gugała',
                'updated_at' => $now,
            ]);

        // This is a separate, unique gallery and should be indexable.
        DB::table('galleries')
            ->where('slug', 'stylizacja')
            ->update([
                'indexable' => true,
                'updated_at' => $now,
            ]);

        // Footer/contact banner detected by the public audit.
        DB::table('photos')
            ->where(function ($query) {
                $query->where('webp', 'like', '%N5xERll6y7Epob76lXtA3TLsQv15ldUdbfB6oGXD%')
                    ->orWhere('filename', 'like', '%N5xERll6y7Epob76lXtA3TLsQv15ldUdbfB6oGXD%');
            })
            ->update([
                'alt' => 'Szukasz fotografa żywności lub foodstylisty? Kontakt w sprawie sesji zdjęciowych.',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // SEO correction migration: no destructive rollback.
    }
};
