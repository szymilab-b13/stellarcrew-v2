<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\UndoDescriberInterface;
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Application\DeactivateUserCommandHandler;
use Modules\UserManagement\Application\ReactivateUserCommandHandler;
use Modules\Workspaces\Application\AssignMemberCommandHandler;
use Modules\Workspaces\Application\ChangeMemberRoleCommandHandler;
use Modules\Workspaces\Application\RemoveMemberCommandHandler;

final readonly class UserManagementUndoDescriber implements UndoDescriberInterface
{
    public function supports(string $action): bool
    {
        return match ($action) {
            CreateUserCommandHandler::AUDIT_ACTION,
            DeactivateUserCommandHandler::AUDIT_ACTION,
            ReactivateUserCommandHandler::AUDIT_ACTION,
            AssignMemberCommandHandler::AUDIT_ACTION,
            RemoveMemberCommandHandler::AUDIT_ACTION,
            ChangeMemberRoleCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function describe(AuditEntryDto $entry): string
    {
        return match ($entry->action) {
            CreateUserCommandHandler::AUDIT_ACTION
                => "Dezaktywuj utworzonego użytkownika i usuń go ze wszystkich workspace'ów.",
            DeactivateUserCommandHandler::AUDIT_ACTION
                => 'Reaktywuj użytkownika.',
            ReactivateUserCommandHandler::AUDIT_ACTION
                => 'Dezaktywuj użytkownika ponownie.',
            AssignMemberCommandHandler::AUDIT_ACTION
                => "Usuń użytkownika z workspace'a.",
            RemoveMemberCommandHandler::AUDIT_ACTION
                => 'Przywróć członkostwo użytkownika w workspace.',
            ChangeMemberRoleCommandHandler::AUDIT_ACTION
                => 'Przywróć poprzednią rolę użytkownika w workspace.',
            default => 'Cofnij akcję ' . $entry->action,
        };
    }
}
