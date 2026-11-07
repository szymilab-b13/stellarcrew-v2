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

    /**
     * System-level bulk removal of all memberships a user has across every workspace.
     * Bypasses the admin/owner guards used in {@see self::removeMember()} — intended for
     * audit undo of {@code user_management.user.created}, not for end-user flows.
     */
    public function removeAllMembershipsOf(UserId $userId): void;

    /**
     * System-level membership removal that bypasses the admin-downgrade guard.
     * Intended for audit undo of {@code workspace_member.assigned}; does not emit audit.
     *
     * @throws MemberNotFoundException
     * @throws CannotRemoveOwnerException
     */
    public function removeMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId): void;

    /**
     * System-level membership restoration that bypasses audit emission.
     * Intended for audit undo of {@code workspace_member.removed}.
     *
     * @throws MemberAlreadyExistsException
     * @throws InvalidMemberRoleException
     */
    public function restoreMembershipAsSystem(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): void;

    /**
     * System-level role change that bypasses audit emission.
     * Intended for audit undo of {@code workspace_member.role_changed}.
     *
     * @throws MemberNotFoundException
     * @throws InvalidMemberRoleException
     */
    public function setMembershipRoleAsSystem(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): void;
}
