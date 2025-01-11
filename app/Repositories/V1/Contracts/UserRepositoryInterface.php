<?php

namespace App\Repositories\V1\Contracts;

interface UserRepositoryInterface
{
    public function find($id);

    public function findByEmail($email);

    public function create(array $data);
}
