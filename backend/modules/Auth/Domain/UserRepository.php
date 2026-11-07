<?php

declare(strict_types=1);

namespace Modules\Auth\Domain;

use Modules\Shared\Kernel\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(string $email): ?User;

    public function findById(UserId $id): ?User;

    public function count(): int;
}
