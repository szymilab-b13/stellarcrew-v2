<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Auth;

use DateTimeImmutable;
use Modules\Auth\Api\AuthApi;
use Modules\Auth\Api\Dto\AuthUser;
use Modules\Auth\Api\Exception\EmailTakenException;
use Modules\Auth\Api\Exception\UserNotFoundException;
use Modules\Shared\Kernel\UserId;
use Symfony\Component\Uid\Uuid;

final class SpyAuthApi implements AuthApi
{
    /** @var list<array{email: string, password: string, displayName: ?string}> */
    public array $calls = [];

    /** @var list<array{email: string, password: string, isSuperAdmin: bool, displayName: ?string}> */
    public array $createUserCalls = [];

    /** @var list<UserId> */
    public array $markDeactivatedCalls = [];

    /** @var list<UserId> */
    public array $markReactivatedCalls = [];

    /** @var array<string, true> */
    private array $takenEmails = [];

    /** @var array<string, true> */
    private array $missingIds = [];

    public function createAdmin(
        string $email,
        string $plaintextPassword,
        ?string $displayName = null,
    ): AuthUser {
        $this->calls[] = [
            'email' => $email,
            'password' => $plaintextPassword,
            'displayName' => $displayName,
        ];

        return new AuthUser(
            new UserId(Uuid::v7()),
            $email,
            new DateTimeImmutable('2026-11-05T09:00:00+00:00'),
            $displayName,
        );
    }

    public function createUser(
        string $email,
        string $plaintextPassword,
        bool $isSuperAdmin = false,
        ?string $displayName = null,
    ): AuthUser {
        if (isset($this->takenEmails[$email])) {
            throw EmailTakenException::forEmail($email);
        }

        $this->createUserCalls[] = [
            'email' => $email,
            'password' => $plaintextPassword,
            'isSuperAdmin' => $isSuperAdmin,
            'displayName' => $displayName,
        ];

        return new AuthUser(
            new UserId(Uuid::v7()),
            $email,
            new DateTimeImmutable('2026-11-07T14:00:00+00:00'),
            $displayName,
        );
    }

    public function markDeactivated(UserId $userId): void
    {
        if (isset($this->missingIds[(string) $userId])) {
            throw UserNotFoundException::forId($userId);
        }

        $this->markDeactivatedCalls[] = $userId;
    }

    public function markReactivated(UserId $userId): void
    {
        if (isset($this->missingIds[(string) $userId])) {
            throw UserNotFoundException::forId($userId);
        }

        $this->markReactivatedCalls[] = $userId;
    }

    public function withTakenEmail(string $email): self
    {
        $this->takenEmails[$email] = true;

        return $this;
    }

    public function withMissingUserId(UserId $id): self
    {
        $this->missingIds[(string) $id] = true;

        return $this;
    }
}
