<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Checklist extends Model
{
    protected $fillable = [
        'title',
        'description',
        'items'
    ];

    protected $casts = [
        'items' => 'array'
    ];

    /**
     * Get the item that owns this checklist (polymorphic relationship).
     */
    public function item(): MorphOne
    {
        return $this->morphOne(Item::class, 'itemable');
    }
}
