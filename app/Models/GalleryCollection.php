<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryCollection extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class)->orderBy('sort_order')->orderBy('title');
    }
}
