<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Api;

use function array_map;
use function array_values;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;
use Modules\Workspaces\Application\CreateWorkspaceCommand;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class DefaultWorkspacesApi implements WorkspacesApi
{
    public function __construct(
        private CreateWorkspaceCommandHandler $create,
        private WorkspaceRepository $workspaces,
    ) {
    }

    public function createForOwner(UserId $ownerId, string $name): WorkspaceView
    {
        $workspace = ($this->create)(new CreateWorkspaceCommand($ownerId, $name));

        return $this->toView($workspace);
    }

    public function findById(WorkspaceId $id): ?WorkspaceView
    {
        $workspace = $this->workspaces->findById($id);
        if (!$workspace instanceof Workspace || $workspace->isDeleted()) {
            return null;
        }

        return $this->toView($workspace);
    }

    public function listForUser(UserId $userId): array
    {
        return array_values(array_map(
            fn (Workspace $w): WorkspaceView => $this->toView($w),
            $this->workspaces->listForUser($userId),
        ));
    }

    public function getMembership(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        $member = $this->workspaces->findMembership($workspaceId, $userId);
        if ($member === null) {
            return null;
        }

        return new MembershipView(
            workspaceId: (string) $member->workspaceId(),
            userId: (string) $member->userId(),
            role: $member->role()->value,
        );
    }

    public function hasPermission(WorkspaceId $workspaceId, UserId $userId, string $permission): bool
    {
        $member = $this->workspaces->findMembership($workspaceId, $userId);
        if ($member === null) {
            return false;
        }

        return match ($permission) {
            'workspace.edit' => $member->role()->canEditWorkspace(),
            'workspace.delete' => $member->role()->canDeleteWorkspace(),
            'members.manage' => $member->role()->canManageMembers(),
            'members.change_role' => $member->role()->canChangeRoles(),
            'projects.create' => $member->role()->canCreateProjects(),
            'messages.read' => $member->role()->canReadMessages(),
            default => false,
        };
    }

    private function toView(Workspace $workspace): WorkspaceView
    {
        return new WorkspaceView(
            id: (string) $workspace->id(),
            name: $workspace->name(),
            slug: $workspace->slug(),
            iconColor: $workspace->iconColor(),
            iconInitials: $workspace->iconInitials(),
            ownerId: (string) $workspace->ownerId(),
        );
    }
}
