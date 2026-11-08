<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\UndoDescriberInterface;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Application\DeleteWorkspaceCommandHandler;
use Modules\Workspaces\Application\UpdateWorkspaceCommandHandler;

final readonly class WorkspacesUndoDescriber implements UndoDescriberInterface
{
    public function supports(string $action): bool
    {
        return match ($action) {
            CreateWorkspaceCommandHandler::AUDIT_ACTION,
            UpdateWorkspaceCommandHandler::AUDIT_ACTION,
            DeleteWorkspaceCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function describe(AuditEntryDto $entry): string
    {
        return match ($entry->action) {
            CreateWorkspaceCommandHandler::AUDIT_ACTION
                => 'Usuń utworzony workspace (soft delete).',
            UpdateWorkspaceCommandHandler::AUDIT_ACTION
                => 'Przywróć poprzednią nazwę, kolor i inicjały workspace\'a.',
            DeleteWorkspaceCommandHandler::AUDIT_ACTION
                => 'Przywróć usunięty workspace.',
            default => 'Cofnij akcję ' . $entry->action,
        };
    }
}
