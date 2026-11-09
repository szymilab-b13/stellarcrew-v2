<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Projects\Domain\ProjectSlugGenerator;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\WorkspacesApi;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class UpdateProjectCommandHandler
{
    public const string PERMISSION = 'projects.edit';

    public function __construct(
        private ProjectRepository $projects,
        private WorkspacesApi $workspaces,
        private Clock $clock,
    ) {
    }

    public function __invoke(UpdateProjectCommand $command): Project
    {
        $project = $this->projects->findById($command->projectId);
        if (!$project instanceof Project || $project->isDeleted()) {
            throw new NotFoundHttpException(
                sprintf('Project "%s" not found.', (string) $command->projectId),
            );
        }

        if (!$this->workspaces->hasPermission($project->workspaceId(), $command->actorId, self::PERMISSION)) {
            throw new AccessDeniedException(
                sprintf('Cannot update project "%s".', (string) $command->projectId),
            );
        }

        $now = $this->clock->now();

        if ($command->name !== null) {
            $project->rename($command->name, $now);
        }

        if ($command->slug !== null && $command->slug !== $project->slug()) {
            $project->changeSlug(
                $this->uniqueSlug($project->workspaceId(), $command->slug),
                $now,
            );
        }

        if ($command->description !== null) {
            $project->changeDescription($command->description, $now);
        }

        $this->projects->save($project);

        return $project;
    }

    private function uniqueSlug(WorkspaceId $workspaceId, string $base): string
    {
        $slug = $base;
        while ($this->projects->existsSlugInWorkspace($workspaceId, $slug)) {
            $slug = ProjectSlugGenerator::withRandomSuffix($base);
        }

        return $slug;
    }
}
