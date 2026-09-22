<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilder extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'content',
        'published',
    ];

    protected $casts = [
        'content' => 'array',
        'published' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
