<?php

namespace App\Repositories;

use App\Contracts\SlotRepositoryInterface;
use App\Models\Slot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SlotRepository extends BaseRepository implements SlotRepositoryInterface
{
    public function __construct()
    {
        $this->model = new Slot();
    }

    /**
     * Get query
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Get slot by id
     * @param Builder $query
     * @param int $id
     * @return Model|null
     */
    public function getSlotById(Builder $query, int $id): ?Model
    {
        return $query->where('id', $id)->first();
    }

    /**
     * Decrement remaining seats atomically
     * @param Builder $query
     * @param int $id
     * @return bool
     */
    public function decrementRemaining(Builder $query, int $id): bool
    {
        return $query->where('id', $id)
            ->where('remaining', '>', 0)
            ->decrement('remaining') > 0;
    }

    /**
     * Increment remaining seats atomically
     * @param Builder $query
     * @param int $id
     * @return bool
     */
    public function incrementRemaining(Builder $query, int $id): bool
    {
        $slot = $this->getSlotById($this->getQuery(), $id);
        if (!$slot) {
            return false;
        }

        if ($slot->remaining >= $slot->capacity) {
            return false;
        }

        return $query->where('id', $id)
            ->increment('remaining') > 0;
    }

    /**
     * Get slot by id with lock for update (exclusive row lock)
     * @param Builder $query
     * @param int $id
     * @return Model|null
     */
    public function getSlotByIdWithLock(Builder $query, int $id): ?Model
    {
        return $query->where('id', $id)->lockForUpdate()->first();
    }
}
