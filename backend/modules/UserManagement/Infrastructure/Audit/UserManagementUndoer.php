<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Audit;

use LogicException;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\UndoerInterface;
use Modules\Auth\Api\AuthApi;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Application\DeactivateUserCommandHandler;
use Modules\UserManagement\Application\ReactivateUserCommandHandler;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;
use Modules\Workspaces\Api\Exception\MemberAlreadyExistsException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Api\WorkspacesApi;
use Modules\Workspaces\Application\AssignMemberCommandHandler;
use Modules\Workspaces\Application\ChangeMemberRoleCommandHandler;
use Modules\Workspaces\Application\RemoveMemberCommandHandler;

final readonly class UserManagementUndoer implements UndoerInterface
{
    public function __construct(
        private AuthApi $auth,
        private UserProfileRepository $profiles,
        private WorkspacesApi $workspaces,
        private Clock $clock,
    ) {
    }

    public function supports(string $action): bool
    {
        return match ($action) {
            CreateUserCommandHandler::AUDIT_ACTION,
            DeactivateUserCommandHandler::AUDIT_ACTION,
            ReactivateUserCommandHandler::AUDIT_ACTION,
            AssignMemberCommandHandler::AUDIT_ACTION,
            RemoveMemberCommandHandler::AUDIT_ACTION,
            ChangeMemberRoleCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function undo(AuditEntryDto $entry, UserId $performedBy): void
    {
        unset($performedBy);

        match ($entry->action) {
            CreateUserCommandHandler::AUDIT_ACTION => $this->undoUserCreated($entry),
            DeactivateUserCommandHandler::AUDIT_ACTION => $this->undoUserDeactivated($entry),
            ReactivateUserCommandHandler::AUDIT_ACTION => $this->undoUserReactivated($entry),
            AssignMemberCommandHandler::AUDIT_ACTION => $this->undoMemberAssigned($entry),
            RemoveMemberCommandHandler::AUDIT_ACTION => $this->undoMemberRemoved($entry),
            ChangeMemberRoleCommandHandler::AUDIT_ACTION => $this->undoMemberRoleChanged($entry),
            default => throw new LogicException(
                'UserManagementUndoer received unsupported action: ' . $entry->action,
            ),
        };
    }

    private function undoUserCreated(AuditEntryDto $entry): void
    {
        $userId = $this->userIdFrom($entry);
        $profile = $this->requireProfile($userId);

        if ($profile->isDeactivated()) {
            throw new UndoConflictException('already_deactivated');
        }

        $this->auth->markDeactivated($userId);
        $profile->deactivate($this->clock->now());
        $this->profiles->save($profile);

        $this->workspaces->removeAllMembershipsOf($userId);
    }

    private function undoUserDeactivated(AuditEntryDto $entry): void
    {
        $userId = $this->userIdFrom($entry);
        $profile = $this->requireProfile($userId);

        if (!$profile->isDeactivated()) {
            throw new UndoConflictException('not_deactivated');
        }

        $this->auth->markReactivated($userId);
        $profile->reactivate($this->clock->now());
        $this->profiles->save($profile);
    }

    private function undoUserReactivated(AuditEntryDto $entry): void
    {
        $userId = $this->userIdFrom($entry);
        $profile = $this->requireProfile($userId);

        if ($profile->isDeactivated()) {
            throw new UndoConflictException('already_deactivated');
        }

        $this->auth->markDeactivated($userId);
        $profile->deactivate($this->clock->now());
        $this->profiles->save($profile);
    }

    private function undoMemberAssigned(AuditEntryDto $entry): void
    {
        [$workspaceId, $userId] = $this->membershipCoordsFrom($entry->afterState);

        try {
            $this->workspaces->removeMembershipAsSystem($workspaceId, $userId);
        } catch (MemberNotFoundException) {
            throw new UndoConflictException('not_a_member_anymore');
        }
    }

    private function undoMemberRemoved(AuditEntryDto $entry): void
    {
        [$workspaceId, $userId] = $this->membershipCoordsFrom($entry->beforeState);
        $role = $this->roleFrom($entry->beforeState);

        try {
            $this->workspaces->restoreMembershipAsSystem($workspaceId, $userId, $role);
        } catch (MemberAlreadyExistsException) {
            throw new UndoConflictException('already_member_again');
        }
    }

    private function undoMemberRoleChanged(AuditEntryDto $entry): void
    {
        $workspaceId = $this->workspaceIdFromMetadata($entry->metadata);
        $userId = $this->userIdFromMetadata($entry->metadata);
        $role = $this->roleFrom($entry->beforeState);

        try {
            $this->workspaces->setMembershipRoleAsSystem($workspaceId, $userId, $role);
        } catch (MemberNotFoundException) {
            throw new UndoConflictException('member_not_found');
        }
    }

    private function userIdFrom(AuditEntryDto $entry): UserId
    {
        $resourceId = $entry->resourceId ?? throw new UndoConflictException('missing_resource_id');

        return UserId::fromString($resourceId);
    }

    private function requireProfile(UserId $userId): UserProfile
    {
        $profile = $this->profiles->findById($userId);
        if (!$profile instanceof UserProfile) {
            throw new UndoConflictException('user_not_found');
        }

        return $profile;
    }

    /**
     * @param array<string, mixed>|null $state
     * @return array{0: WorkspaceId, 1: UserId}
     */
    private function membershipCoordsFrom(?array $state): array
    {
        if ($state === null) {
            throw new UndoConflictException('missing_state');
        }

        $workspaceId = $state['workspace_id'] ?? throw new UndoConflictException('missing_workspace_id');
        $userId = $state['user_id'] ?? throw new UndoConflictException('missing_user_id');

        return [
            WorkspaceId::fromString((string) $workspaceId),
            UserId::fromString((string) $userId),
        ];
    }

    /**
     * @param array<string, mixed>|null $state
     */
    private function roleFrom(?array $state): string
    {
        if ($state === null) {
            throw new UndoConflictException('missing_state');
        }

        $role = $state['role'] ?? throw new UndoConflictException('missing_role');

        return (string) $role;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function workspaceIdFromMetadata(array $metadata): WorkspaceId
    {
        $value = $metadata['workspace_id'] ?? throw new UndoConflictException('missing_workspace_id');

        return WorkspaceId::fromString((string) $value);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function userIdFromMetadata(array $metadata): UserId
    {
        $value = $metadata['user_id'] ?? throw new UndoConflictException('missing_user_id');

        return UserId::fromString((string) $value);
    }
}
