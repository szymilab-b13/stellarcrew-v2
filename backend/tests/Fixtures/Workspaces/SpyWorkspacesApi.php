<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workspaces;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;
use Modules\Workspaces\Domain\WorkspaceRole;
use Symfony\Component\Uid\Uuid;

final class SpyWorkspacesApi implements WorkspacesApi
{
    /** @var list<array{ownerId: UserId, name: string}> */
    public array $createCalls = [];

    public function createForOwner(UserId $ownerId, string $name): WorkspaceView
    {
        $this->createCalls[] = ['ownerId' => $ownerId, 'name' => $name];

        return new WorkspaceView(
            id: (string) Uuid::v7(),
            name: $name,
            slug: 'spy-slug',
            iconColor: '#8B5CF6',
            iconInitials: 'SP',
            ownerId: (string) $ownerId,
        );
    }

    public function findById(WorkspaceId $id): ?WorkspaceView
    {
        return null;
    }

    public function listForUser(UserId $userId): array
    {
        return [];
    }

    public function getMembership(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        return null;
    }

    public function hasPermission(WorkspaceId $workspaceId, UserId $userId, string $permission): bool
    {
        unset($workspaceId, $userId, $permission);

        return false;
    }

    public function assignMember(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
        ?UserId $invitedBy = null,
    ): MembershipView {
        unset($invitedBy);

        return new MembershipView(
            workspaceId: (string) $workspaceId,
            userId: (string) $userId,
            role: $role,
        );
    }

    public function changeMemberRole(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        return new MembershipView(
            workspaceId: (string) $workspaceId,
            userId: (string) $userId,
            role: $role,
        );
    }

    public function removeMember(WorkspaceId $workspaceId, UserId $userId): void
    {
        unset($workspaceId, $userId);
    }

    public function findMember(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        return $this->getMembership($workspaceId, $userId);
    }

    /** @var list<UserId> */
    public array $removeAllMembershipsOfCalls = [];

    public function removeAllMembershipsOf(UserId $userId): void
    {
        $this->removeAllMembershipsOfCalls[] = $userId;
    }

    /** @var list<array{workspaceId: WorkspaceId, userId: UserId}> */
    public array $removeMembershipAsSystemCalls = [];

    public function removeMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId): void
    {
        $this->removeMembershipAsSystemCalls[] = [
            'workspaceId' => $workspaceId,
            'userId' => $userId,
        ];
    }

    /** @var list<array{workspaceId: WorkspaceId, userId: UserId, role: string}> */
    public array $restoreMembershipAsSystemCalls = [];

    public function restoreMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId, string $role): void
    {
        $this->restoreMembershipAsSystemCalls[] = [
            'workspaceId' => $workspaceId,
            'userId' => $userId,
            'role' => $role,
        ];
    }

    /** @var list<array{workspaceId: WorkspaceId, userId: UserId, role: string}> */
    public array $setMembershipRoleAsSystemCalls = [];

    public function setMembershipRoleAsSystem(WorkspaceId $workspaceId, UserId $userId, string $role): void
    {
        $this->setMembershipRoleAsSystemCalls[] = [
            'workspaceId' => $workspaceId,
            'userId' => $userId,
            'role' => $role,
        ];
    }

    public function membershipFor(UserId $userId, WorkspaceRole $role): MembershipView
    {
        return new MembershipView(
            workspaceId: (string) Uuid::v7(),
            userId: (string) $userId,
            role: $role->value,
        );
    }
}
