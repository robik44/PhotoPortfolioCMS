<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('gallery_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('filename');

            $table->string('title')->nullable();

            $table->string('alt')->nullable();

            $table->text('description')->nullable();

            $table->string('thumbnail')->nullable();

            $table->string('webp')->nullable();

            $table->boolean('is_cover')->default(false);

            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};