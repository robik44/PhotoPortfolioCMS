<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('galleries')
            ->where('slug', 'czasopisma')
            ->update([
                'description' => 'Fotografia kulinarna i fotografia żywności realizowana do czasopism, magazynów i publikacji. Tworzę zdjęcia potraw, jedzenia i produktów spożywczych z myślą o konkretnym materiale redakcyjnym — od prostych, naturalnych kadrów po bardziej rozbudowane aranżacje. W razie potrzeby przygotowuję również stylizację żywności i aranżację potraw, dbając o światło, kompozycję, kolor i sposób podania.',
                'updated_at' => now(),
            ]);

        DB::table('galleries')
            ->where('slug', 'opakowania')
            ->update([
                'description' => 'Fotografia produktowa i reklamowa żywności przygotowywana z myślą o opakowaniach, etykietach i materiałach promocyjnych. Wykonuję zdjęcia produktów spożywczych, potraw i gotowych dań, tak aby były apetyczne, czytelne i spójne z charakterem marki. Realizację mogę uzupełnić o stylizację jedzenia, aranżację potraw oraz przygotowanie całego kadru do zdjęć na opakowania.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Content migration: previous descriptions are intentionally not restored.
    }
};
