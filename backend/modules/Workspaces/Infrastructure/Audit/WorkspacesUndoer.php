<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Audit;

use LogicException;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\UndoerInterface;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Application\DeleteWorkspaceCommandHandler;
use Modules\Workspaces\Application\UpdateWorkspaceCommandHandler;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceRepository;

final readonly class WorkspacesUndoer implements UndoerInterface
{
    public function __construct(
        private WorkspaceRepository $workspaces,
        private Clock $clock,
    ) {
    }

    public function supports(string $action): bool
    {
        return match ($action) {
            CreateWorkspaceCommandHandler::AUDIT_ACTION,
            UpdateWorkspaceCommandHandler::AUDIT_ACTION,
            DeleteWorkspaceCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function undo(AuditEntryDto $entry, UserId $performedBy): void
    {
        unset($performedBy);

        match ($entry->action) {
            CreateWorkspaceCommandHandler::AUDIT_ACTION => $this->undoCreated($entry),
            UpdateWorkspaceCommandHandler::AUDIT_ACTION => $this->undoUpdated($entry),
            DeleteWorkspaceCommandHandler::AUDIT_ACTION => $this->undoDeleted($entry),
            default => throw new LogicException(
                'WorkspacesUndoer received unsupported action: ' . $entry->action,
            ),
        };
    }

    private function undoCreated(AuditEntryDto $entry): void
    {
        $workspace = $this->requireWorkspace($entry);

        if ($workspace->isDeleted()) {
            throw new UndoConflictException('already_deleted');
        }

        $workspace->softDelete($this->clock->now());
        $this->workspaces->saveWorkspace($workspace);
    }

    private function undoUpdated(AuditEntryDto $entry): void
    {
        $workspace = $this->requireWorkspace($entry);

        if ($workspace->isDeleted()) {
            throw new UndoConflictException('workspace_deleted');
        }

        $before = $entry->beforeState ?? throw new UndoConflictException('missing_state');
        $name = $before['name'] ?? throw new UndoConflictException('missing_name');
        $iconColor = $before['icon_color'] ?? throw new UndoConflictException('missing_icon_color');
        $iconInitials = $before['icon_initials'] ?? throw new UndoConflictException('missing_icon_initials');

        $now = $this->clock->now();
        $workspace->rename((string) $name, $now);
        $workspace->changeIcon((string) $iconColor, (string) $iconInitials, $now);

        $this->workspaces->saveWorkspace($workspace);
    }

    private function undoDeleted(AuditEntryDto $entry): void
    {
        $workspace = $this->requireWorkspace($entry);

        if (!$workspace->isDeleted()) {
            throw new UndoConflictException('not_deleted');
        }

        $workspace->restore($this->clock->now());
        $this->workspaces->saveWorkspace($workspace);
    }

    private function requireWorkspace(AuditEntryDto $entry): Workspace
    {
        $resourceId = $entry->resourceId ?? throw new UndoConflictException('missing_resource_id');
        $workspace = $this->workspaces->findById(WorkspaceId::fromString($resourceId));

        if (!$workspace instanceof Workspace) {
            throw new UndoConflictException('workspace_not_found');
        }

        return $workspace;
    }
}
