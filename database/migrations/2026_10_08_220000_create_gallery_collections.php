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

        // Existing builder blocks must stay attached to the original gallery module.
        // Otherwise adding a new module later would make legacy "all galleries" blocks
        // unexpectedly display subgalleries from every module.
        if (Schema::hasTable('page_builders')) {
            DB::table('page_builders')->orderBy('id')->get()->each(function ($builder) use ($defaultId) {
                $content = json_decode($builder->content ?? '', true);
                if (!is_array($content) || !is_array($content['sections'] ?? null)) {
                    return;
                }

                $changed = false;
                foreach ($content['sections'] as &$section) {
                    if (($section['type'] ?? null) === 'gallery' && empty($section['gallery_collection_id'])) {
                        $section['gallery_collection_id'] = $defaultId;
                        $changed = true;
                    }
                }
                unset($section);

                if ($changed) {
                    DB::table('page_builders')->where('id', $builder->id)->update([
                        'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn('gallery_collection_id');
        });

        Schema::dropIfExists('gallery_collections');
    }
};
