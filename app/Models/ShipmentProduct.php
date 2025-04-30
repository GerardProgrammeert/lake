<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ShipmentProduct extends Model
{
    /** @use HasFactory<\Database\Factories\ShipmentProductFactory> */
    use HasFactory;

    protected $fillable = [
        'barcode',
    ];

    public function shipment(): belongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function handlings(): morphMany
    {
        return $this->morphMany(Handling::class, 'handleable');
    }

    public function handlers(): morphMany
    {
        return $this->morphMany(Handling::class, 'handler');
    }
}
