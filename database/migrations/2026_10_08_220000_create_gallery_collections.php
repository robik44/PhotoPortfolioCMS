<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->unsignedBigInteger('gallery_collection_id')->nullable()->index();
        });

        $now = now();
        $defaultId = DB::table('gallery_collections')->insertGetId([
            'name' => 'Galerie',
            'slug' => 'galerie',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('galleries')->update(['gallery_collection_id' => $defaultId]);
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn('gallery_collection_id');
        });

        Schema::dropIfExists('gallery_collections');
    }
};
