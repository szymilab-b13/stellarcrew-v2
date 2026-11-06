<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Symfony\Component\Uid\Uuid;

class WorkspaceMember
{
    public function __construct(
        private readonly Uuid $id,
        private readonly WorkspaceId $workspaceId,
        private readonly UserId $userId,
        private WorkspaceRole $role,
        private readonly ?UserId $invitedBy,
        private readonly DateTimeImmutable $joinedAt,
    ) {
    }

    public static function join(
        WorkspaceId $workspaceId,
        UserId $userId,
        WorkspaceRole $role,
        DateTimeImmutable $joinedAt,
        ?UserId $invitedBy = null,
    ): self {
        return new self(Uuid::v7(), $workspaceId, $userId, $role, $invitedBy, $joinedAt);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function workspaceId(): WorkspaceId
    {
        return $this->workspaceId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function role(): WorkspaceRole
    {
        return $this->role;
    }

    public function invitedBy(): ?UserId
    {
        return $this->invitedBy;
    }

    public function joinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function changeRole(WorkspaceRole $role): void
    {
        $this->role = $role;
    }
}
