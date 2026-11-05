<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure;

use Modules\Auth\Api\AuthApi;
use Modules\Auth\Api\Dto\AuthUser;
use Modules\Auth\Application\PasswordHasher;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\UuidGenerator;

final readonly class DefaultAuthApi implements AuthApi
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private UuidGenerator $uuids,
        private Clock $clock,
    ) {
    }

    public function createAdmin(string $email, string $plaintextPassword): AuthUser
    {
        $now = $this->clock->now();
        $user = User::createAdmin(
            new UserId($this->uuids->generate()),
            $email,
            $this->hasher->hash($plaintextPassword),
            $now,
        );
        $this->users->save($user);

        return new AuthUser($user->id(), $user->email(), $now);
    }
}
