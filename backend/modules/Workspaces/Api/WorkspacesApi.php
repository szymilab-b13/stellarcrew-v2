<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\Exception\AdminMustBeDowngradedFirstException;
use Modules\Workspaces\Api\Exception\CannotRemoveOwnerException;
use Modules\Workspaces\Api\Exception\InvalidMemberRoleException;
use Modules\Workspaces\Api\Exception\MemberAlreadyExistsException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;

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

    /**
     * @throws MemberAlreadyExistsException
     * @throws InvalidMemberRoleException
     */
    public function assignMember(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
        ?UserId $invitedBy = null,
    ): MembershipView;

    /**
     * @throws MemberNotFoundException
     * @throws InvalidMemberRoleException
     */
    public function changeMemberRole(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView;

    /**
     * @throws MemberNotFoundException
     * @throws AdminMustBeDowngradedFirstException
     * @throws CannotRemoveOwnerException
     */
    public function removeMember(WorkspaceId $workspaceId, UserId $userId): void;

    public function findMember(WorkspaceId $workspaceId, UserId $userId): ?MembershipView;
}
