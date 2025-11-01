<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface HoldRepositoryInterface
{
    /**
     * Get query
     * @return Builder
     */
    public function getQuery(): Builder;

    /**
     * Get hold by ID
     * @param Builder $query
     * @param int $id
     * @return Model|null
     */
    public function getHoldById(Builder $query, int $id): ?Model;

    /**
     * Get hold by idempotency key
     * @param Builder $query
     * @param string $key
     * @return Model|null
     */
    public function getHoldByIdempotencyKey(Builder $query, string $key): ?Model;

    /**
     * Create hold
     * @param Builder $query
     * @param array $data
     * @return Model
     */
    public function createHold(Builder $query, array $data): Model;

    /**
     * Update hold
     * @param Builder $query
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function updateHold(Builder $query, int $id, array $data): ?Model;
}
