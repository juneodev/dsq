<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Folder extends Model
{
    protected $fillable = [
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
}
