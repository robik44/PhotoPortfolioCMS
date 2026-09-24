<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Page extends Model
{
    protected $fillable = [
        'seo_title', 'seo_description', 'social_photo_id', 'indexable',
        'title',
        'slug',
        'content',
        'featured_image',
        'featured_photo_id',
        'published',
        'sort_order',
    ];

    protected $casts = [
        'published' => 'boolean',
        'indexable' => 'boolean',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function builder(): HasOne
    {
        return $this->hasOne(PageBuilder::class);
    }
    public function socialPhoto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Photo::class, 'social_photo_id');
    }

    public function featuredPhoto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Photo::class, 'featured_photo_id');
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featuredPhoto?->imageUrl()
            ?? ($this->featured_image ? asset('storage/'.$this->featured_image) : null);
    }
}
