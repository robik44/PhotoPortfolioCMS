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
                'description' => 'Fotografia kulinarna realizowana do czasopism, magazynów i publikacji. Tworzę zdjęcia potraw i produktów spożywczych z myślą o konkretnym materiale redakcyjnym — od prostych, naturalnych kadrów po bardziej rozbudowane aranżacje. Każda realizacja powstaje z dbałością o światło, kompozycję, kolor i sposób podania jedzenia.',
                'updated_at' => now(),
            ]);

        DB::table('galleries')
            ->where('slug', 'opakowania')
            ->update([
                'description' => 'Fotografia produktowa żywności przygotowywana z myślą o opakowaniach i materiałach reklamowych. Zdjęcia produktów spożywczych i gotowych dań tworzę tak, aby były apetyczne, czytelne i spójne z charakterem marki. W razie potrzeby zajmuję się również stylizacją żywności i aranżacją całego kadru.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Content migration: previous descriptions are intentionally not restored.
    }
};
