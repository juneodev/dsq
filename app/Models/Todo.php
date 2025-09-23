<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Todo extends Model
{
    protected $fillable = [
        'title',
        'description',
        'completed'
    ];

    protected $casts = [
        'completed' => 'boolean'
    ];

    /**
     * Get the item that owns this todo (polymorphic relationship).
     */
    public function item(): MorphOne
    {
        return $this->morphOne(Item::class, 'itemable');
    }
}
