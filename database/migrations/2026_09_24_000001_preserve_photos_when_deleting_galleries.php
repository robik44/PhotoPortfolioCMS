<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite can only disable foreign keys outside a transaction. Otherwise its
    // table rebuild could cascade into gallery_photo while replacing photos.
    public $withinTransaction = false;

    public function up(): void
    {
        $foreignKey = collect(Schema::getForeignKeys('photos'))
            ->first(fn (array $key) => $key['columns'] === ['gallery_id']);

        if (! $foreignKey || $foreignKey['foreign_table'] !== 'galleries') {
            throw new RuntimeException('Unexpected legacy photos.gallery_id foreign key.');
        }

        $sqlite = DB::getDriverName() === 'sqlite';
        $replaceForeignKey = function () use ($foreignKey, $sqlite) {
            Schema::table('photos', function (Blueprint $table) use ($foreignKey, $sqlite) {
                $table->dropForeign($sqlite ? ['gallery_id'] : $foreignKey['name']);
                $table->foreign('gallery_id')->references('id')->on('galleries')->nullOnDelete();
            });
        };

        if (! $sqlite) {
            $replaceForeignKey();

            return;
        }

        if (DB::transactionLevel() !== 0) {
            throw new RuntimeException('Run this migration outside a transaction on SQLite.');
        }

        if (DB::select('PRAGMA foreign_key_check')) {
            throw new RuntimeException('Resolve existing foreign key violations before migrating.');
        }

        Schema::withoutForeignKeyConstraints(function () use ($replaceForeignKey) {
            DB::transaction(function () use ($replaceForeignKey) {
                $sequence = DB::table('sqlite_sequence')->where('name', 'photos')->value('seq');
                $replaceForeignKey();

                // Preserve the next ID even when the highest photo IDs were deleted.
                if ($sequence !== null) {
                    DB::table('sqlite_sequence')->where('name', 'photos')->update(['seq' => $sequence]);
                }

                if (DB::select('PRAGMA foreign_key_check')) {
                    throw new RuntimeException('Foreign key integrity check failed after migration.');
                }
            });
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This migration must not restore cascading deletion of library photos.');
    }
};
