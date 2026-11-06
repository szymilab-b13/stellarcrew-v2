<?php

declare(strict_types=1);

namespace Modules\Auth\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;

class User
{
    public function __construct(
        private readonly UserId $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly UserRole $role,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?string $displayName = null,
    ) {
    }

    public static function createAdmin(
        UserId $id,
        string $email,
        string $passwordHash,
        DateTimeImmutable $createdAt,
        ?string $displayName = null,
    ): self {
        return new self($id, $email, $passwordHash, UserRole::Admin, $createdAt, $displayName);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }
}
