<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceNotFoundException;
use Modules\Workspaces\Domain\WorkspacePermissionDeniedException;
use Modules\Workspaces\Domain\WorkspaceRepository;

final readonly class DeleteWorkspaceCommandHandler
{
    public const string AUDIT_ACTION = 'workspaces.workspace.deleted';

    public const string AUDIT_RESOURCE_TYPE = 'workspace';

    public function __construct(
        private WorkspaceRepository $workspaces,
        private AuditLoggerInterface $audit,
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

        $before = CreateWorkspaceCommandHandler::snapshot($workspace);
        $now = $this->clock->now();
        $workspace->softDelete($now);

        $this->workspaces->saveWorkspace($workspace);

        $this->audit->log(new AuditEntry(
            userId: $command->actorId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $command->workspaceId,
            metadata: [
                'before_state' => $before,
            ],
        ));
    }
}
