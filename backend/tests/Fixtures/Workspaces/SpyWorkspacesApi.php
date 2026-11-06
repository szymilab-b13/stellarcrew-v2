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
            iconColor: 'purple',
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

    public function membershipFor(UserId $userId, WorkspaceRole $role): MembershipView
    {
        return new MembershipView(
            workspaceId: (string) Uuid::v7(),
            userId: (string) $userId,
            role: $role->value,
        );
    }
}
