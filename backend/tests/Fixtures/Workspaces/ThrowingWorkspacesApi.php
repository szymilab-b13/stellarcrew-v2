<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workspaces;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class ThrowingWorkspacesApi implements WorkspacesApi
{
    public ?RuntimeException $throwOnRemoveMembershipAsSystem = null;

    public ?RuntimeException $throwOnRestoreMembershipAsSystem = null;

    public ?RuntimeException $throwOnSetMembershipRoleAsSystem = null;

    public function createForOwner(UserId $ownerId, string $name): WorkspaceView
    {
        return new WorkspaceView(
            id: (string) Uuid::v7(),
            name: $name,
            slug: 'throwing-slug',
            iconColor: '#000000',
            iconInitials: 'TH',
            ownerId: (string) $ownerId,
        );
    }

    public function findById(WorkspaceId $id): ?WorkspaceView
    {
        unset($id);

        return null;
    }

    public function listForUser(UserId $userId): array
    {
        unset($userId);

        return [];
    }

    public function getMembership(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        unset($workspaceId, $userId);

        return null;
    }

    public function hasPermission(WorkspaceId $workspaceId, UserId $userId, string $permission): bool
    {
        unset($workspaceId, $userId, $permission);

        return false;
    }

    public function assignMember(
        UserId $actorUserId,
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        unset($actorUserId);

        return new MembershipView(
            workspaceId: (string) $workspaceId,
            userId: (string) $userId,
            role: $role,
        );
    }

    public function changeMemberRole(
        UserId $actorUserId,
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        unset($actorUserId);

        return new MembershipView(
            workspaceId: (string) $workspaceId,
            userId: (string) $userId,
            role: $role,
        );
    }

    public function removeMember(UserId $actorUserId, WorkspaceId $workspaceId, UserId $userId): void
    {
        unset($actorUserId, $workspaceId, $userId);
    }

    public function findMember(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        return $this->getMembership($workspaceId, $userId);
    }

    public function removeAllMembershipsOf(UserId $userId): void
    {
        unset($userId);
    }

    public function removeMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId): void
    {
        unset($workspaceId, $userId);

        if ($this->throwOnRemoveMembershipAsSystem instanceof RuntimeException) {
            throw $this->throwOnRemoveMembershipAsSystem;
        }
    }

    public function restoreMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId, string $role): void
    {
        unset($workspaceId, $userId, $role);

        if ($this->throwOnRestoreMembershipAsSystem instanceof RuntimeException) {
            throw $this->throwOnRestoreMembershipAsSystem;
        }
    }

    public function setMembershipRoleAsSystem(WorkspaceId $workspaceId, UserId $userId, string $role): void
    {
        unset($workspaceId, $userId, $role);

        if ($this->throwOnSetMembershipRoleAsSystem instanceof RuntimeException) {
            throw $this->throwOnSetMembershipRoleAsSystem;
        }
    }
}
