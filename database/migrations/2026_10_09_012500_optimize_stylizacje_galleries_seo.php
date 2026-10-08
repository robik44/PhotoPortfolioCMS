<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('galleries')
            ->where('slug', 'zywnosc')
            ->update([
                'description' => 'Stylizacja żywności i fotografia kulinarna to połączenie pracy nad samym produktem z budowaniem całego obrazu. Przygotowuję potrawy i produkty spożywcze do zdjęć, dobieram naczynia, dodatki, tła i rekwizyty oraz układam kompozycję tak, aby jedzenie wyglądało naturalnie, apetycznie i wiarygodnie. W zależności od charakteru realizacji aranżacja może być oszczędna lub bardziej rozbudowana — zawsze podporządkowana produktowi, marce i miejscu wykorzystania zdjęcia.',
                'seo_title' => 'Stylizacja żywności i fotografia kulinarna | Magda Gugała',
                'seo_description' => 'Stylizacja żywności, aranżacja potraw i fotografia kulinarna. Zdjęcia jedzenia i produktów spożywczych do reklam, publikacji i materiałów marki.',
                'updated_at' => now(),
            ]);

        DB::table('galleries')
            ->where('slug', 'stylizacja')
            ->update([
                'description' => 'Aranżacja potraw i food styling są integralną częścią fotografii jedzenia. Pracuję nad formą produktu, sposobem podania, kolorem, fakturą i detalem, a następnie dopasowuję stylizację do światła i kompozycji kadru. Tak powstają zdjęcia żywności wykorzystywane w fotografii reklamowej i produktowej, materiałach promocyjnych, publikacjach oraz projektach opakowań.',
                'seo_title' => 'Food styling i aranżacja potraw | Magda Gugała',
                'seo_description' => 'Food styling, stylizacja jedzenia i aranżacja potraw do fotografii reklamowej i produktowej. Profesjonalne zdjęcia żywności i gotowych dań.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Content migration: previous editorial values are intentionally not restored.
    }
};
