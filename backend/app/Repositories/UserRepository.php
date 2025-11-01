<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{

    public function __construct()
    {
        $this->model = new User();
    }

    /**
     * @param array $data
     * @return Model
     */
    public function createUser(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function getUserById(int $id): ?Model
    {
        return $this->getById($id);
    }

    /**
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function updateUser(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return $this->getAll();
    }

    /**
     * @param int $limit
     * @return LengthAwarePaginator
     */
    public function getAllUserWithPaginate(int $limit): LengthAwarePaginator
    {
        return $this->getAllWithPagination($limit);
    }
}
