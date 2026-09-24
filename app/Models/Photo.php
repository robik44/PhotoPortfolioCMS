<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Photo extends Model
{
    protected $fillable = [
        'gallery_id',
        'filename',
        'title',
        'alt',
        'description',
        'thumbnail',
        'webp',
        'is_cover',
        'sort_order',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
    ];

    public function scopeMissingMetadata(\Illuminate\Database\Eloquent\Builder $query, string $field): \Illuminate\Database\Eloquent\Builder
    {
        if (!in_array($field, ['alt', 'title', 'description'], true)) throw new \InvalidArgumentException('Unknown metadata field.');
        return $query->where(fn ($query) => $query->whereNull($field)->orWhereRaw('TRIM('.$field.") = ''"));
    }

    public static function storagePath(string $filename): string
    {
        return str_starts_with($filename, 'photos/') ? $filename : 'photos/'.$filename;
    }

    public function imageUrl(): string
    {
        return asset('storage/'.self::storagePath($this->filename));
    }

    public function thumbnailUrl(): string
    {
        return asset('storage/'.self::storagePath($this->thumbnail ?: $this->filename));
    }

    /** @return list<string> */
    public function filePaths(): array
    {
        return collect([$this->filename, $this->thumbnail, $this->webp])
            ->filter()
            ->map(fn (string $filename) => self::storagePath($filename))
            ->unique()
            ->values()
            ->all();
    }

    public function galleries(): BelongsToMany
    {
        return $this->belongsToMany(Gallery::class, 'gallery_photo')
            ->withPivot([
                'sort_order',
                'is_cover',
            ])
            ->withTimestamps();
    }
}
