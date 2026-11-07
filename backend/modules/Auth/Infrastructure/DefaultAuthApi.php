<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure;

use Modules\Auth\Api\AuthApi;
use Modules\Auth\Api\Dto\AuthUser;
use Modules\Auth\Api\Exception\EmailTakenException;
use Modules\Auth\Api\Exception\UserNotFoundException;
use Modules\Auth\Application\PasswordHasher;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
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

    public function createAdmin(
        string $email,
        string $plaintextPassword,
        ?string $displayName = null,
    ): AuthUser {
        $now = $this->clock->now();
        $trimmedName = $displayName === null ? null : trim($displayName);
        $effectiveName = $trimmedName === '' ? null : $trimmedName;

        $user = User::createAdmin(
            new UserId($this->uuids->generate()),
            $email,
            $this->hasher->hash($plaintextPassword),
            $now,
            $effectiveName,
        );
        $this->users->save($user);

        return new AuthUser($user->id(), $user->email(), $now, $user->displayName());
    }

    public function createUser(
        string $email,
        string $plaintextPassword,
        bool $isSuperAdmin = false,
        ?string $displayName = null,
    ): AuthUser {
        if ($this->users->findByEmail($email) instanceof User) {
            throw EmailTakenException::forEmail($email);
        }

        $now = $this->clock->now();
        $trimmedName = $displayName === null ? null : trim($displayName);
        $effectiveName = $trimmedName === '' ? null : $trimmedName;

        $user = User::createWithRole(
            new UserId($this->uuids->generate()),
            $email,
            $this->hasher->hash($plaintextPassword),
            $isSuperAdmin ? UserRole::Admin : UserRole::User,
            $now,
            $effectiveName,
        );
        $this->users->save($user);

        return new AuthUser($user->id(), $user->email(), $now, $user->displayName());
    }

    public function markDeactivated(UserId $userId): void
    {
        $user = $this->users->findById($userId);
        if (!$user instanceof User) {
            throw UserNotFoundException::forId($userId);
        }

        $user->deactivate($this->clock->now());
        $this->users->save($user);
    }

    public function markReactivated(UserId $userId): void
    {
        $user = $this->users->findById($userId);
        if (!$user instanceof User) {
            throw UserNotFoundException::forId($userId);
        }

        $user->reactivate();
        $this->users->save($user);
    }
}
