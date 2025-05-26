<?php

namespace App\Models;

use App\Enums\ActionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Handling extends Model
{
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['action', 'started_at', 'stopped_at']);
    }

    public function handleable(): MorphTo
    {
        return $this->morphTo();
    }
}
