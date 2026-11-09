<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Projects;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;
use RuntimeException;

/**
 * Configurable test double limited to the WorkspacesApi surface used by Projects handlers:
 * existence check (findById) + permission check (hasPermission).
 */
final class FakeWorkspacesApi implements WorkspacesApi
{
    /** @var array<string, WorkspaceView> */
    private array $workspaces = [];

    /** @var array<string, true> keyed by workspaceId|userId|permission */
    private array $permissions = [];

    public function withWorkspace(WorkspaceId $id): self
    {
        $this->workspaces[(string) $id] = new WorkspaceView(
            id: (string) $id,
            name: 'Fake',
            slug: 'fake',
            iconColor: '#000000',
            iconInitials: 'FA',
            ownerId: (string) $id,
        );

        return $this;
    }

    public function withPermission(WorkspaceId $workspaceId, UserId $userId, string $permission): self
    {
        $this->permissions[$this->key($workspaceId, $userId, $permission)] = true;

        return $this;
    }

    public function findById(WorkspaceId $id): ?WorkspaceView
    {
        return $this->workspaces[(string) $id] ?? null;
    }

    public function hasPermission(WorkspaceId $workspaceId, UserId $userId, string $permission): bool
    {
        return isset($this->permissions[$this->key($workspaceId, $userId, $permission)]);
    }

    public function createForOwner(UserId $ownerId, string $name): WorkspaceView
    {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function listForUser(UserId $userId): array
    {
        return [];
    }

    public function getMembership(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        return null;
    }

    public function assignMember(
        UserId $actorUserId,
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function changeMemberRole(
        UserId $actorUserId,
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function removeMember(UserId $actorUserId, WorkspaceId $workspaceId, UserId $userId): void
    {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function findMember(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        return null;
    }

    public function removeAllMembershipsOf(UserId $userId): void
    {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function removeMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId): void
    {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function restoreMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId, string $role): void
    {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    public function setMembershipRoleAsSystem(WorkspaceId $workspaceId, UserId $userId, string $role): void
    {
        throw new RuntimeException('Not implemented in FakeWorkspacesApi.');
    }

    private function key(WorkspaceId $workspaceId, UserId $userId, string $permission): string
    {
        return $workspaceId . '|' . $userId . '|' . $permission;
    }
}
