<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gallery extends Model
{
    protected $fillable = [
        'seo_title', 'seo_description', 'social_photo_id', 'indexable',
        'title',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = ['indexable' => 'boolean', 'published' => 'boolean'];

    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'gallery_photo')
            ->withPivot([
                'sort_order',
                'is_cover',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
    public function socialPhoto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Photo::class, 'social_photo_id');
    }
}
