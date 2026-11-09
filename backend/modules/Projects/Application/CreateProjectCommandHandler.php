<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Projects\Domain\ProjectSlugGenerator;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\WorkspacesApi;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CreateProjectCommandHandler
{
    public const string PERMISSION = 'projects.create';

    public function __construct(
        private ProjectRepository $projects,
        private WorkspacesApi $workspaces,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateProjectCommand $command): Project
    {
        if (!$this->workspaces->findById($command->workspaceId) instanceof WorkspaceView) {
            throw new NotFoundHttpException(
                sprintf('Workspace "%s" not found.', (string) $command->workspaceId),
            );
        }

        if (!$this->workspaces->hasPermission($command->workspaceId, $command->actorId, self::PERMISSION)) {
            throw new AccessDeniedException(
                sprintf('Cannot create project in workspace "%s".', (string) $command->workspaceId),
            );
        }

        $now = $this->clock->now();
        $baseSlug = $command->slug ?? ProjectSlugGenerator::fromName($command->name);
        $slug = $this->uniqueSlug($command->workspaceId, $baseSlug);

        $project = Project::create(
            ProjectId::generate(),
            $command->workspaceId,
            $command->name,
            $slug,
            $command->description,
            $now,
        );

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
