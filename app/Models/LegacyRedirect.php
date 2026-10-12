<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyRedirect extends Model
{
    protected $fillable = ['source_path', 'destination_path', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
