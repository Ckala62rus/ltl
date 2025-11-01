<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hold extends Model
{
    use HasFactory;

    public const STATUS_HELD = 'held';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'slot_id',
        'status',
        'idempotency_key',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get slot model via relation
     * @return BelongsTo
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Check if hold is expired
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if hold is active (not expired and not cancelled)
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_HELD && !$this->isExpired();
    }
}

