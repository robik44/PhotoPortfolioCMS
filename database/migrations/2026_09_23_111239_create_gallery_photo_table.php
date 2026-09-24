<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_photo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gallery_id')
                ->constrained('galleries')
                ->cascadeOnDelete();

            $table->foreignId('photo_id')
                ->constrained('photos')
                ->cascadeOnDelete();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);

            $table->timestamps();

            $table->unique(['gallery_id', 'photo_id']);
            $table->index(['gallery_id', 'sort_order']);
        });

        $photos = DB::table('photos')
            ->select('id', 'gallery_id', 'sort_order', 'is_cover')
            ->whereNotNull('gallery_id')
            ->get();

        $now = now();

        foreach ($photos as $photo) {
            DB::table('gallery_photo')->insert([
                'gallery_id' => $photo->gallery_id,
                'photo_id' => $photo->id,
                'sort_order' => $photo->sort_order ?? 0,
                'is_cover' => (bool) $photo->is_cover,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photo');
    }
};
