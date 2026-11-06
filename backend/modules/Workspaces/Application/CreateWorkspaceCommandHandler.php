<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\SlugGenerator;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class CreateWorkspaceCommandHandler
{
    public const string DEFAULT_ICON_COLOR = 'purple';

    public function __construct(
        private WorkspaceRepository $workspaces,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateWorkspaceCommand $command): Workspace
    {
        $now = $this->clock->now();
        $id = WorkspaceId::generate();
        $slug = $this->uniqueSlug($command->name);

        $workspace = Workspace::create(
            $id,
            $command->name,
            $slug,
            $command->iconColor ?? self::DEFAULT_ICON_COLOR,
            $command->ownerId,
            $now,
            $command->iconInitials,
        );

        $this->workspaces->saveWorkspace($workspace);
        $this->workspaces->saveMembership(
            WorkspaceMember::join($id, $command->ownerId, WorkspaceRole::Owner, $now),
        );

        return $workspace;
    }

    private function uniqueSlug(string $name): string
    {
        $base = SlugGenerator::fromName($name);
        $slug = $base;
        while ($this->workspaces->findBySlug($slug) !== null) {
            $slug = SlugGenerator::withRandomSuffix($base);
        }

        return $slug;
    }
}
