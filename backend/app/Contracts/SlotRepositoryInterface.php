<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface SlotRepositoryInterface
{
    /**
     * Get query
     * @return Builder
     */
    public function getQuery(): Builder;

    /**
     * Get slot by id
     * @param Builder $query
     * @param int $id
     * @return Model|null
     */
    public function getSlotById(Builder $query, int $id): ?Model;

    /**
     * Decrement remaining seats atomically
     * @param Builder $query
     * @param int $id
     * @return bool
     */
    public function decrementRemaining(Builder $query, int $id): bool;

    /**
     * Increment remaining seats atomically
     * @param Builder $query
     * @param int $id
     * @return bool
     */
    public function incrementRemaining(Builder $query, int $id): bool;

    /**
     * Get slot by id with lock for update (exclusive row lock)
     * @param Builder $query
     * @param int $id
     * @return Model|null
     */
    public function getSlotByIdWithLock(Builder $query, int $id): ?Model;
}
