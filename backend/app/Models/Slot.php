<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'remaining',
    ];

    /**
     * Get all holds for this slot
     * @return HasMany
     */
    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

    /**
     * Get active holds for this slot
     * @return HasMany
     */
    public function activeHolds(): HasMany
    {
        return $this->hasMany(Hold::class)->where('status', 'held');
    }
}

