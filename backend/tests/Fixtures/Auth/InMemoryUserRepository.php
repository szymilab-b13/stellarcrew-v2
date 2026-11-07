<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Auth;

use function count;

use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Shared\Kernel\UserId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $usersByEmail = [];

    /** @var array<string, User> */
    private array $usersById = [];

    public function save(User $user): void
    {
        $this->usersByEmail[$user->email()] = $user;
        $this->usersById[(string) $user->id()] = $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->usersByEmail[$email] ?? null;
    }

    public function findById(UserId $id): ?User
    {
        return $this->usersById[(string) $id] ?? null;
    }

    public function count(): int
    {
        return count($this->usersByEmail);
    }
}
