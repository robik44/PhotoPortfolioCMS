<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pages', 'galleries'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->foreignId('social_photo_id')->nullable()->constrained('photos')->nullOnDelete();
                $table->boolean('indexable')->default(true);
            });
        }
    }

    public function down(): void
    {
        foreach (['pages', 'galleries'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('social_photo_id');
                $table->dropColumn(['seo_title', 'seo_description', 'indexable']);
            });
        }
    }
};
