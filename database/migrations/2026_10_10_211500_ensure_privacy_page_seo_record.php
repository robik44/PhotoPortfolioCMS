<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $page = Page::firstOrCreate(
            ['slug' => 'polityka-prywatnosci'],
            [
                'title' => 'Polityka prywatności',
                'content' => null,
                'published' => true,
                'sort_order' => 99,
            ]
        );

        $page->fill([
            'title' => 'Polityka prywatności',
            'seo_title' => 'Polityka prywatności | FoodFoto – Magda Gugała',
            'seo_description' => 'Polityka prywatności serwisu FoodFoto. Informacje o przetwarzaniu danych osobowych, zasadach kontaktu i ochronie prywatności użytkowników.',
            'published' => true,
            'indexable' => true,
        ]);

        $page->save();
    }

    public function down(): void
    {
        // SEO correction: keep the privacy page record intact.
    }
};
