<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\UndoDescriberInterface;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Application\UpdateOrganizationCommandHandler;

final readonly class OrganizationsUndoDescriber implements UndoDescriberInterface
{
    public function supports(string $action): bool
    {
        return match ($action) {
            CreateOrganizationCommandHandler::AUDIT_ACTION,
            UpdateOrganizationCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function describe(AuditEntryDto $entry): string
    {
        return match ($entry->action) {
            CreateOrganizationCommandHandler::AUDIT_ACTION
                => 'Usuń utworzoną organizację (soft delete).',
            UpdateOrganizationCommandHandler::AUDIT_ACTION
                => 'Przywróć poprzednią nazwę i slug organizacji.',
            default => 'Cofnij akcję ' . $entry->action,
        };
    }
}
