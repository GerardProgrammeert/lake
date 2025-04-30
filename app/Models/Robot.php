<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Robot extends Model
{
    /** @use HasFactory<\Database\Factories\RobotFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
    ];

    public function handlings(): morphMany
    {
        return $this->morphMany(Handling::class, 'handler');
    }

    public function latestHandling(): HasOne
    {
        return $this->hasOne(Handling::class, 'handler_id', 'id')->latestOfMany();
    }
}
