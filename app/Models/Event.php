<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'start_at',
        'end_at',
        'location',
        'all_day',
        'remind_minutes_before',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'remind_minutes_before' => 'integer',
    ];
}
