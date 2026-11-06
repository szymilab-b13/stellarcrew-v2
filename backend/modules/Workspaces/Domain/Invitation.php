<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function bin2hex;
use function filter_var;
use function random_bytes;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

use function sprintf;

use Symfony\Component\Uid\Uuid;

class Invitation
{
    public const int TOKEN_LENGTH = 64;

    public function __construct(
        private readonly Uuid $id,
        private readonly WorkspaceId $workspaceId,
        private readonly string $email,
        private readonly WorkspaceRole $role,
        private readonly string $token,
        private readonly DateTimeImmutable $expiresAt,
        private readonly UserId $invitedBy,
        private ?DateTimeImmutable $acceptedAt = null,
    ) {
        $this->guardEmail($email);
        $this->guardRole($role);
        $this->guardToken($token);
    }

    public static function issue(
        WorkspaceId $workspaceId,
        string $email,
        WorkspaceRole $role,
        UserId $invitedBy,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            Uuid::v7(),
            $workspaceId,
            $email,
            $role,
            bin2hex(random_bytes(self::TOKEN_LENGTH / 2)),
            $expiresAt,
            $invitedBy,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function workspaceId(): WorkspaceId
    {
        return $this->workspaceId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function role(): WorkspaceRole
    {
        return $this->role;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function invitedBy(): UserId
    {
        return $this->invitedBy;
    }

    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function isAccepted(): bool
    {
        return $this->acceptedAt !== null;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function accept(DateTimeImmutable $now): void
    {
        if ($this->acceptedAt !== null) {
            throw new InvalidArgumentException('Invitation already accepted.');
        }
        if ($this->isExpired($now)) {
            throw new InvalidArgumentException('Invitation has expired.');
        }
        $this->acceptedAt = $now;
    }

    private function guardEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('Invalid invitation email: "%s".', $email));
        }
    }

    private function guardRole(WorkspaceRole $role): void
    {
        if ($role === WorkspaceRole::Owner) {
            throw new InvalidArgumentException('Cannot invite with Owner role.');
        }
    }

    private function guardToken(string $token): void
    {
        if ($token === '') {
            throw new InvalidArgumentException('Invitation token cannot be empty.');
        }
    }
}
