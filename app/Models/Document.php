<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'url',
    ];

    /**
     * Optionally define media collections for documents.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk(config('media-library.disk_name'));
    }

    /**
     * Get the item that owns this document (polymorphic relationship).
     */
    public function item(): MorphOne
    {
        return $this->morphOne(Item::class, 'itemable');
    }
}
