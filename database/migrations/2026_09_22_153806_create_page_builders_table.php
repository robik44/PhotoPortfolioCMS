<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_builders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('page_id')
                ->nullable()
                ->constrained('pages')
                ->cascadeOnDelete();

            $table->string('type')->default('page');

            $table->json('content')->nullable();

            $table->boolean('published')->default(true);

            $table->timestamps();

            $table->unique(['page_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_builders');
    }
};
