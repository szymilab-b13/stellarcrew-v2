<?php

declare(strict_types=1);

namespace Modules\Projects\Application\Query;

use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Workspaces\Api\WorkspacesApi;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class GetProjectQueryHandler
{
    public const string PERMISSION = 'projects.view';

    public function __construct(
        private ProjectRepository $projects,
        private WorkspacesApi $workspaces,
    ) {
    }

    public function __invoke(GetProjectQuery $query): Project
    {
        $project = $this->projects->findById($query->projectId);

        if (!$project instanceof Project || $project->isDeleted()) {
            throw new NotFoundHttpException(
                sprintf('Project "%s" not found.', (string) $query->projectId),
            );
        }

        if (!$this->workspaces->hasPermission($project->workspaceId(), $query->actorId, self::PERMISSION)) {
            throw new AccessDeniedException(
                sprintf('Cannot view project "%s".', (string) $query->projectId),
            );
        }

        return $project;
    }
}
