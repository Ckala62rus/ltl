<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SlotServiceInterface
{
    /**
     * Get available slots with caching and cache stampede protection
     * @return Collection
     */
    public function getAvailableSlots(): Collection;

    /**
     * Create hold for slot
     * @param int $slotId
     * @param string $idempotencyKey
     * @return array
     */
    public function createHold(int $slotId, string $idempotencyKey): array;

    /**
     * Confirm hold
     * @param int $holdId
     * @return array
     */
    public function confirmHold(int $holdId): array;

    /**
     * Cancel hold
     * @param int $holdId
     * @return array
     */
    public function cancelHold(int $holdId): array;

    /**
     * Invalidate cache for slots availability
     * @return void
     */
    public function invalidateCache(): void;
}

