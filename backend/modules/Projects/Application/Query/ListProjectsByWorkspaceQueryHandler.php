<?php

declare(strict_types=1);

namespace Modules\Projects\Application\Query;

use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class ListProjectsByWorkspaceQueryHandler
{
    public const string PERMISSION = 'projects.view';

    public function __construct(
        private ProjectRepository $projects,
        private WorkspacesApi $workspaces,
    ) {
    }

    /**
     * @return list<Project>
     */
    public function __invoke(ListProjectsByWorkspaceQuery $query): array
    {
        if (!$this->workspaces->findById($query->workspaceId) instanceof WorkspaceView) {
            throw new NotFoundHttpException(
                sprintf('Workspace "%s" not found.', (string) $query->workspaceId),
            );
        }

        if (!$this->workspaces->hasPermission($query->workspaceId, $query->actorId, self::PERMISSION)) {
            throw new AccessDeniedException(
                sprintf('Cannot view projects in workspace "%s".', (string) $query->workspaceId),
            );
        }

        return $this->projects->findByWorkspace($query->workspaceId);
    }
}
