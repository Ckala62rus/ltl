<?php

namespace App\Repositories;

use App\Contracts\HoldRepositoryInterface;
use App\Models\Hold;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HoldRepository extends BaseRepository implements HoldRepositoryInterface
{
    public function __construct()
    {
        $this->model = new Hold();
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
     * Get hold by ID
     * @param Builder $query
     * @param int $id
     * @return Model|null
     */
    public function getHoldById(Builder $query, int $id): ?Model
    {
        return $query->where('id', $id)->first();
    }

    /**
     * Get hold by idempotency key
     * @param Builder $query
     * @param string $key
     * @return Model|null
     */
    public function getHoldByIdempotencyKey(Builder $query, string $key): ?Model
    {
        return $query->where('idempotency_key', $key)->first();
    }

    /**
     * Create hold
     * @param Builder $query
     * @param array $data
     * @return Model
     */
    public function createHold(Builder $query, array $data): Model
    {
        return $query->create($data);
    }

    /**
     * Update hold
     * @param Builder $query
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function updateHold(Builder $query, int $id, array $data): ?Model
    {
        $hold = $this->getHoldById($query, $id);
        if (!$hold) {
            return null;
        }

        $hold->update($data);
        return $hold;
    }
}
