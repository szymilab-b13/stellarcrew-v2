<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\UndoDescriberInterface;
use Modules\Projects\Application\CreateProjectCommandHandler;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Application\RestoreProjectCommandHandler;
use Modules\Projects\Application\UpdateProjectCommandHandler;

final readonly class ProjectsUndoDescriber implements UndoDescriberInterface
{
    public function supports(string $action): bool
    {
        return match ($action) {
            CreateProjectCommandHandler::AUDIT_ACTION,
            UpdateProjectCommandHandler::AUDIT_ACTION,
            DeleteProjectCommandHandler::AUDIT_ACTION,
            RestoreProjectCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function describe(AuditEntryDto $entry): string
    {
        return match ($entry->action) {
            CreateProjectCommandHandler::AUDIT_ACTION
                => 'Usuń utworzony projekt (soft delete).',
            UpdateProjectCommandHandler::AUDIT_ACTION
                => 'Przywróć poprzednią nazwę, slug i opis projektu.',
            DeleteProjectCommandHandler::AUDIT_ACTION
                => 'Przywróć usunięty projekt.',
            RestoreProjectCommandHandler::AUDIT_ACTION
                => 'Ponownie usuń przywrócony projekt (soft delete).',
            default => 'Cofnij akcję ' . $entry->action,
        };
    }
}
