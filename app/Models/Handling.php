<?php

namespace App\Models;

use App\Enums\ActionEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Handling extends Model
{
    protected $fillable = [
        'action',
        'started_at',
        'stopped_at',
    ];

    protected $casts = [
        'action' => ActionEnum::class,
        'started_at' => 'date',
        'stopped_at' => 'date',
    ];

    public function handleable(): MorphTo
    {
        return $this->morphTo();
    }
}
