<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'color',
        'description'
    ];

    /**
     * Get the item that owns this folder (polymorphic relationship).
     */
    public function item(): MorphOne
    {
        return $this->morphOne(Item::class, 'itemable');
    }

    /**
     * Items contained within this folder.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
