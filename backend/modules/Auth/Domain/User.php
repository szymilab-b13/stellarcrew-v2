<?php

declare(strict_types=1);

namespace Modules\Auth\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;

class User
{
    private ?DateTimeImmutable $deactivatedAt = null;

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

    public static function createWithRole(
        UserId $id,
        string $email,
        string $passwordHash,
        UserRole $role,
        DateTimeImmutable $createdAt,
        ?string $displayName = null,
    ): self {
        return new self($id, $email, $passwordHash, $role, $createdAt, $displayName);
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

    public function deactivatedAt(): ?DateTimeImmutable
    {
        return $this->deactivatedAt;
    }

    public function isDeactivated(): bool
    {
        return $this->deactivatedAt instanceof DateTimeImmutable;
    }

    public function deactivate(DateTimeImmutable $now): void
    {
        if ($this->deactivatedAt instanceof DateTimeImmutable) {
            return;
        }

        $this->deactivatedAt = $now;
    }

    public function reactivate(): void
    {
        $this->deactivatedAt = null;
    }
}
