<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Auth;

use function count;

use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $usersByEmail = [];

    public function save(User $user): void
    {
        $this->usersByEmail[$user->email()] = $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->usersByEmail[$email] ?? null;
    }

    public function count(): int
    {
        return count($this->usersByEmail);
    }
}
