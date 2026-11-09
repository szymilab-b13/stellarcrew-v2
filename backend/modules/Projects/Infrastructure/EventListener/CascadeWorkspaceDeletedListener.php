<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\EventListener;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Projects\Application\CreateProjectCommandHandler;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Workspaces\Api\Event\WorkspaceDeleted;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkspaceDeleted::class)]
final readonly class CascadeWorkspaceDeletedListener
{
    public const string CASCADE_REASON = 'workspace_deleted';

    public function __construct(
        private ProjectRepository $projects,
        private AuditLoggerInterface $audit,
    ) {
    }

    public function __invoke(WorkspaceDeleted $event): void
    {
        $active = $this->projects->findByWorkspace($event->workspaceId);
        if ($active === []) {
            return;
        }

        foreach ($active as $project) {
            $before = CreateProjectCommandHandler::snapshot($project);
            $project->softDelete($event->occurredAt);
            $this->projects->save($project);

            $this->audit->log(new AuditEntry(
                userId: $event->actorId,
                action: DeleteProjectCommandHandler::AUDIT_ACTION,
                occurredAt: $event->occurredAt,
                resourceType: DeleteProjectCommandHandler::AUDIT_RESOURCE_TYPE,
                resourceId: (string) $project->id(),
                metadata: [
                    'before_state' => $before,
                    'cascade' => [
                        'workspace_id' => (string) $event->workspaceId,
                        'reason' => self::CASCADE_REASON,
                    ],
                ],
            ));
        }
    }
}
