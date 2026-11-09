<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Api\WorkspacesApi;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class DeleteProjectCommandHandler
{
    public const string PERMISSION = 'projects.delete';

    public function __construct(
        private ProjectRepository $projects,
        private WorkspacesApi $workspaces,
        private Clock $clock,
    ) {
    }

    public function __invoke(DeleteProjectCommand $command): void
    {
        $project = $this->projects->findById($command->projectId);
        if (!$project instanceof Project || $project->isDeleted()) {
            throw new NotFoundHttpException(
                sprintf('Project "%s" not found.', (string) $command->projectId),
            );
        }

        if (!$this->workspaces->hasPermission($project->workspaceId(), $command->actorId, self::PERMISSION)) {
            throw new AccessDeniedException(
                sprintf('Cannot delete project "%s".', (string) $command->projectId),
            );
        }

        $project->softDelete($this->clock->now());
        $this->projects->save($project);
    }
}
