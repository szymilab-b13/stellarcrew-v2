<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceNotFoundException;
use Modules\Workspaces\Domain\WorkspacePermissionDeniedException;
use Modules\Workspaces\Domain\WorkspaceRepository;

final readonly class DeleteWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepository $workspaces,
        private Clock $clock,
    ) {
    }

    public function __invoke(DeleteWorkspaceCommand $command): void
    {
        $workspace = $this->workspaces->findById($command->workspaceId);
        if (!$workspace instanceof Workspace || $workspace->isDeleted()) {
            throw WorkspaceNotFoundException::byId($command->workspaceId);
        }

        $member = $this->workspaces->findMembership($command->workspaceId, $command->actorId);
        if (!$member instanceof WorkspaceMember || !$member->role()->canDeleteWorkspace()) {
            throw WorkspacePermissionDeniedException::forDelete($command->workspaceId);
        }

        $workspace->softDelete($this->clock->now());

        $this->workspaces->saveWorkspace($workspace);
    }
}
