<?php

namespace App\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function create(array $data): Model;
    public function getById(int $id): ?Model;
    public function update(int $id, array $data): ?Model;
    public function delete(int $id): bool;
    public function getAll(): Collection;
    public function getAllWithPagination(int $limit): LengthAwarePaginator;
}
