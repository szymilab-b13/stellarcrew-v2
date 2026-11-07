<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceNotFoundException;
use Modules\Workspaces\Domain\WorkspacePermissionDeniedException;
use Modules\Workspaces\Domain\WorkspaceRepository;

final readonly class UpdateWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepository $workspaces,
        private Clock $clock,
    ) {
    }

    public function __invoke(UpdateWorkspaceCommand $command): Workspace
    {
        $workspace = $this->workspaces->findById($command->workspaceId);
        if (!$workspace instanceof Workspace || $workspace->isDeleted()) {
            throw WorkspaceNotFoundException::byId($command->workspaceId);
        }

        $member = $this->workspaces->findMembership($command->workspaceId, $command->actorId);
        if (!$member instanceof WorkspaceMember || !$member->role()->canEditWorkspace()) {
            throw WorkspacePermissionDeniedException::forEdit($command->workspaceId);
        }

        $now = $this->clock->now();

        if ($command->name !== null) {
            $workspace->rename($command->name, $now);
        }

        if ($command->iconColor !== null || $command->iconInitials !== null) {
            $workspace->changeIcon(
                $command->iconColor ?? $workspace->iconColor(),
                $command->iconInitials ?? $workspace->iconInitials(),
                $now,
            );
        }

        $this->workspaces->saveWorkspace($workspace);

        return $workspace;
    }
}
