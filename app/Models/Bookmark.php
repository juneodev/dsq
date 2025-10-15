<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'title',
        'url',
        'favicon_url',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
