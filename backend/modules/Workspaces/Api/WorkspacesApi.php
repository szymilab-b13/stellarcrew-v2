<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;

interface WorkspacesApi
{
    public function createForOwner(UserId $ownerId, string $name): WorkspaceView;

    public function findById(WorkspaceId $id): ?WorkspaceView;

    /**
     * @return list<WorkspaceView>
     */
    public function listForUser(UserId $userId): array;

    public function getMembership(WorkspaceId $workspaceId, UserId $userId): ?MembershipView;

    public function hasPermission(WorkspaceId $workspaceId, UserId $userId, string $permission): bool;
}
