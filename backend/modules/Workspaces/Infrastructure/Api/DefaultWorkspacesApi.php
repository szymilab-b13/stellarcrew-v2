<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Api;

use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\MembershipView;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\Exception\CannotRemoveOwnerException;
use Modules\Workspaces\Api\Exception\InvalidMemberRoleException;
use Modules\Workspaces\Api\Exception\MemberAlreadyExistsException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Api\WorkspacesApi;
use Modules\Workspaces\Application\AssignMemberCommand;
use Modules\Workspaces\Application\AssignMemberCommandHandler;
use Modules\Workspaces\Application\ChangeMemberRoleCommand;
use Modules\Workspaces\Application\ChangeMemberRoleCommandHandler;
use Modules\Workspaces\Application\CreateWorkspaceCommand;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Application\RemoveMemberCommand;
use Modules\Workspaces\Application\RemoveMemberCommandHandler;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class DefaultWorkspacesApi implements WorkspacesApi
{
    public function __construct(
        private CreateWorkspaceCommandHandler $create,
        private AssignMemberCommandHandler $assignMember,
        private ChangeMemberRoleCommandHandler $changeMemberRole,
        private RemoveMemberCommandHandler $removeMember,
        private WorkspaceRepository $workspaces,
        private Clock $clock,
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
        $rows = $this->workspaces->listForUserWithRoles($userId);
        $views = [];
        foreach ($rows as $row) {
            $views[] = $this->toViewWithRole($row['workspace'], $row['role']);
        }

        return $views;
    }

    public function getMembership(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        $member = $this->workspaces->findMembership($workspaceId, $userId);
        if (!$member instanceof WorkspaceMember) {
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
        if (!$member instanceof WorkspaceMember) {
            return false;
        }

        return match ($permission) {
            'workspace.edit' => $member->role()->canEditWorkspace(),
            'workspace.delete' => $member->role()->canDeleteWorkspace(),
            'members.manage' => $member->role()->canManageMembers(),
            'members.change_role' => $member->role()->canChangeRoles(),
            'projects.create' => $member->role()->canCreateProjects(),
            'projects.edit' => $member->role()->canEditProjects(),
            'projects.view' => $member->role()->canViewProjects(),
            'messages.read' => $member->role()->canReadMessages(),
            default => false,
        };
    }

    public function assignMember(
        UserId $actorUserId,
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        $member = ($this->assignMember)(new AssignMemberCommand(
            $actorUserId,
            $workspaceId,
            $userId,
            $this->toRole($role),
        ));

        return $this->toMembershipView($member);
    }

    public function changeMemberRole(
        UserId $actorUserId,
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): MembershipView {
        $member = ($this->changeMemberRole)(new ChangeMemberRoleCommand(
            $actorUserId,
            $workspaceId,
            $userId,
            $this->toRole($role),
        ));

        return $this->toMembershipView($member);
    }

    private function toRole(string $role): WorkspaceRole
    {
        return WorkspaceRole::tryFrom($role) ?? throw InvalidMemberRoleException::unknownRole($role);
    }

    public function removeMember(UserId $actorUserId, WorkspaceId $workspaceId, UserId $userId): void
    {
        ($this->removeMember)(new RemoveMemberCommand($actorUserId, $workspaceId, $userId));
    }

    public function findMember(WorkspaceId $workspaceId, UserId $userId): ?MembershipView
    {
        return $this->getMembership($workspaceId, $userId);
    }

    public function removeAllMembershipsOf(UserId $userId): void
    {
        $this->workspaces->removeAllMembershipsOf($userId);
    }

    public function removeMembershipAsSystem(WorkspaceId $workspaceId, UserId $userId): void
    {
        $member = $this->workspaces->findMembership($workspaceId, $userId);
        if (!$member instanceof WorkspaceMember) {
            throw MemberNotFoundException::forPair($workspaceId, $userId);
        }

        if ($member->role() === WorkspaceRole::Owner) {
            throw CannotRemoveOwnerException::forPair($workspaceId, $userId);
        }

        $this->workspaces->removeMembership($member);
    }

    public function restoreMembershipAsSystem(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): void {
        $workspaceRole = $this->toRole($role);
        if ($workspaceRole === WorkspaceRole::Owner) {
            throw InvalidMemberRoleException::ownerNotAssignable();
        }

        if ($this->workspaces->findMembership($workspaceId, $userId) instanceof WorkspaceMember) {
            throw MemberAlreadyExistsException::forPair($workspaceId, $userId);
        }

        $member = WorkspaceMember::join(
            $workspaceId,
            $userId,
            $workspaceRole,
            $this->clock->now(),
        );
        $this->workspaces->saveMembership($member);
    }

    public function setMembershipRoleAsSystem(
        WorkspaceId $workspaceId,
        UserId $userId,
        string $role,
    ): void {
        $workspaceRole = $this->toRole($role);
        if ($workspaceRole === WorkspaceRole::Owner) {
            throw InvalidMemberRoleException::ownerNotAssignable();
        }

        $member = $this->workspaces->findMembership($workspaceId, $userId);
        if (!$member instanceof WorkspaceMember) {
            throw MemberNotFoundException::forPair($workspaceId, $userId);
        }

        $member->changeRole($workspaceRole);
        $this->workspaces->saveMembership($member);
    }

    private function toMembershipView(WorkspaceMember $member): MembershipView
    {
        return new MembershipView(
            workspaceId: (string) $member->workspaceId(),
            userId: (string) $member->userId(),
            role: $member->role()->value,
        );
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

    private function toViewWithRole(Workspace $workspace, WorkspaceRole $role): WorkspaceView
    {
        return new WorkspaceView(
            id: (string) $workspace->id(),
            name: $workspace->name(),
            slug: $workspace->slug(),
            iconColor: $workspace->iconColor(),
            iconInitials: $workspace->iconInitials(),
            ownerId: (string) $workspace->ownerId(),
            role: $role->value,
        );
    }
}
