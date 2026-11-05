<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(string $email): ?User;

    public function count(): int;
}
