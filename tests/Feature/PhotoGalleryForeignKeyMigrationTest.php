<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhotoGalleryForeignKeyMigrationTest extends TestCase
{
    public function test_migration_preserves_existing_data_links_and_sequence_and_protects_direct_deletes(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $migrationPath = 'database/migrations/2026_09_24_000001_preserve_photos_when_deleting_galleries.php';
        $previousMigrations = array_values(array_filter(
            glob(database_path('migrations/*.php')),
            fn (string $path) => $path !== base_path($migrationPath)
        ));
        $this->artisan('migrate', ['--path' => $previousMigrations, '--realpath' => true, '--force' => true])->assertExitCode(0);
        $this->assertSame('cascade', Schema::getForeignKeys('photos')[0]['on_delete']);

        $first = Gallery::create(['title' => 'Pierwsza', 'slug' => 'first']);
        $second = Gallery::create(['title' => 'Druga', 'slug' => 'second']);
        $legacy = Photo::create(['filename' => 'legacy.jpg', 'gallery_id' => $first->id, 'sort_order' => 12, 'is_cover' => true]);
        $new = Photo::create(['filename' => 'photos/new.jpg']);
        $first->photos()->attach([$legacy->id => ['is_cover' => true, 'sort_order' => 8], $new->id => ['sort_order' => 9, 'is_cover' => false]]);
        $second->photos()->attach($legacy, ['is_cover' => false, 'sort_order' => 4]);
        DB::table('sqlite_sequence')->where('name', 'photos')->update(['seq' => 100]);
        $before = [];
        foreach (['photos', 'galleries', 'gallery_photo'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }

        $this->artisan('migrate', ['--path' => $migrationPath, '--force' => true])->assertExitCode(0);

        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table.' changed during migration');
        }
        $this->assertSame('set null', Schema::getForeignKeys('photos')[0]['on_delete']);
        $this->assertTrue(Schema::hasColumns('photos', ['gallery_id', 'sort_order', 'is_cover']));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame(101, Photo::create(['filename' => 'next.jpg'])->id);
        foreach (Schema::getForeignKeys('gallery_photo') as $foreignKey) {
            $this->assertSame('cascade', $foreignKey['on_delete']);
        }

        DB::table('galleries')->where('id', $first->id)->delete();
        $this->assertDatabaseCount('photos', 3);
        $this->assertNull($legacy->fresh()->gallery_id);
        $this->assertDatabaseHas('gallery_photo', ['gallery_id' => $second->id, 'photo_id' => $legacy->id, 'sort_order' => 4]);
        $this->assertDatabaseMissing('gallery_photo', ['gallery_id' => $first->id]);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }
}
