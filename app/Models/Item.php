<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Item extends Model
{
    protected $fillable = [
        'itemable_type',
        'itemable_id',
        'x',
        'y',
        'width',
        'height'
    ];

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
        'width' => 'integer',
        'height' => 'integer'
    ];

    /**
     * Get the owning itemable model (Todo, Checklist, or Folder).
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the type of the item based on the itemable model.
     */
    public function getTypeAttribute(): string
    {
        return strtolower(class_basename($this->itemable_type));
    }
}
