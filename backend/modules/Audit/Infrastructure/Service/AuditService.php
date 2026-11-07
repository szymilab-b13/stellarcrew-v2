<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Service;

use function array_key_exists;
use function is_array;

use LogicException;
use Modules\Audit\Api\AuditApi;
use Modules\Audit\Api\AuditEntryWithState;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Dto\AuditPage;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Audit\Domain\AuditLogEntry;
use Modules\Audit\Domain\AuditLogEntryRepository;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\UuidGenerator;

final readonly class AuditService implements AuditApi, AuditLoggerInterface
{
    public function __construct(
        private AuditLogEntryRepository $repository,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    public function log(AuditEntry $entry): void
    {
        $actor = $entry->userId ?? throw new LogicException('AuditService cannot persist entry without actor user id.');

        $metadata = $entry->metadata;
        $beforeState = $this->extractStateKey($metadata, 'before_state');
        $afterState = $this->extractStateKey($metadata, 'after_state');

        $logEntry = new AuditLogEntry(
            id: new AuditEntryId($this->uuidGenerator->generate()),
            actorUserId: $actor,
            action: $entry->action,
            occurredAt: $entry->occurredAt,
            resourceType: $entry->resourceType,
            resourceId: $entry->resourceId,
            beforeState: $beforeState,
            afterState: $afterState,
            metadata: $metadata,
        );

        $this->repository->save($logEntry);
    }

    public function record(AuditEntryWithState $entry): AuditEntryId
    {
        $id = new AuditEntryId($this->uuidGenerator->generate());

        $logEntry = new AuditLogEntry(
            id: $id,
            actorUserId: $entry->actorUserId,
            action: $entry->action,
            occurredAt: $entry->occurredAt,
            resourceType: $entry->resourceType,
            resourceId: $entry->resourceId,
            beforeState: $entry->beforeState,
            afterState: $entry->afterState,
            metadata: $entry->metadata,
            revertsEntryId: $entry->revertsEntryId,
        );

        $this->repository->save($logEntry);

        return $id;
    }

    public function search(AuditQuery $query): AuditPage
    {
        throw new LogicException('Audit search is not implemented yet (BL-AUDIT-003).');
    }

    public function get(AuditEntryId $id): ?AuditEntryDto
    {
        throw new LogicException('Audit get is not implemented yet (BL-AUDIT-003).');
    }

    public function undo(AuditEntryId $id, UserId $performedBy): AuditEntryId
    {
        throw new LogicException('Audit undo is not implemented yet (BL-AUDIT-004).');
    }

    public function describeUndo(AuditEntryId $id): string
    {
        throw new LogicException('Audit undo describe is not implemented yet (BL-AUDIT-004).');
    }

    /**
     * @param array<string, mixed> &$metadata
     * @return array<string, mixed>|null
     */
    private function extractStateKey(array &$metadata, string $key): ?array
    {
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        $value = $metadata[$key];
        unset($metadata[$key]);

        return is_array($value) ? $value : null;
    }
}
