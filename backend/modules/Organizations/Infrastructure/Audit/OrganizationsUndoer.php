<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Audit;

use LogicException;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\UndoerInterface;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Application\UpdateOrganizationCommandHandler;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class OrganizationsUndoer implements UndoerInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {
    }

    public function supports(string $action): bool
    {
        return match ($action) {
            CreateOrganizationCommandHandler::AUDIT_ACTION,
            UpdateOrganizationCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function undo(AuditEntryDto $entry, UserId $performedBy): void
    {
        unset($performedBy);

        match ($entry->action) {
            CreateOrganizationCommandHandler::AUDIT_ACTION => $this->undoCreated($entry),
            UpdateOrganizationCommandHandler::AUDIT_ACTION => $this->undoUpdated($entry),
            default => throw new LogicException(
                'OrganizationsUndoer received unsupported action: ' . $entry->action,
            ),
        };
    }

    private function undoCreated(AuditEntryDto $entry): void
    {
        $organization = $this->requireOrganization($entry);

        if ($organization->isDeleted()) {
            throw new UndoConflictException('already_deleted');
        }

        $organization->softDelete($this->clock->now());
        $this->organizations->save($organization);
    }

    private function undoUpdated(AuditEntryDto $entry): void
    {
        $organization = $this->requireOrganization($entry);

        if ($organization->isDeleted()) {
            throw new UndoConflictException('organization_deleted');
        }

        $before = $entry->beforeState ?? throw new UndoConflictException('missing_state');
        $name = $before['name'] ?? throw new UndoConflictException('missing_name');
        $slug = $before['slug'] ?? throw new UndoConflictException('missing_slug');

        $now = $this->clock->now();
        $organization->rename((string) $name, $now);
        $organization->changeSlug((string) $slug, $now);

        $this->organizations->save($organization);
    }

    private function requireOrganization(AuditEntryDto $entry): Organization
    {
        $resourceId = $entry->resourceId ?? throw new UndoConflictException('missing_resource_id');
        $organization = $this->organizations->findById(OrganizationId::fromString($resourceId));

        if (!$organization instanceof Organization) {
            throw new UndoConflictException('organization_not_found');
        }

        return $organization;
    }
}
