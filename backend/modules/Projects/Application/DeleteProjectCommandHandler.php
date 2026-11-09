<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
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

    public const string AUDIT_ACTION = 'projects.project.deleted';

    public const string AUDIT_RESOURCE_TYPE = 'project';

    public function __construct(
        private ProjectRepository $projects,
        private WorkspacesApi $workspaces,
        private AuditLoggerInterface $audit,
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

        $before = CreateProjectCommandHandler::snapshot($project);
        $now = $this->clock->now();
        $project->softDelete($now);
        $this->projects->save($project);

        $this->audit->log(new AuditEntry(
            userId: $command->actorId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $project->id(),
            metadata: [
                'before_state' => $before,
            ],
        ));
    }
}
