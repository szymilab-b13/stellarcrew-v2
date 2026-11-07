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
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Application\DeactivateUserCommandHandler;
use Modules\UserManagement\Application\ReactivateUserCommandHandler;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;
use Modules\Workspaces\Api\WorkspacesApi;

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
            ReactivateUserCommandHandler::AUDIT_ACTION => true,
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
}
