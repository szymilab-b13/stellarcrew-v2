<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Service;

use function array_key_exists;

use DateTimeImmutable;

use function is_array;

use LogicException;
use Modules\Audit\Api\AuditApi;
use Modules\Audit\Api\AuditEntryWithState;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Dto\AuditPage;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Audit\Api\Exception\AuditEntryAlreadyUndoneException;
use Modules\Audit\Api\Exception\AuditEntryNotFoundException;
use Modules\Audit\Api\Exception\UndoNotSupportedException;
use Modules\Audit\Api\UndoDescriberInterface;
use Modules\Audit\Application\ReadModel\AuditEntriesReadModel;
use Modules\Audit\Domain\AuditLogEntry;
use Modules\Audit\Domain\AuditLogEntryRepository;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\UuidGenerator;

use function sprintf;

final readonly class AuditService implements AuditApi, AuditLoggerInterface
{
    public function __construct(
        private AuditLogEntryRepository $repository,
        private AuditEntriesReadModel $readModel,
        private UuidGenerator $uuidGenerator,
        private UndoDispatcher $undoDispatcher,
        private Clock $clock,
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
        return $this->readModel->search($query);
    }

    public function get(AuditEntryId $id): ?AuditEntryDto
    {
        return $this->readModel->find($id);
    }

    public function listRecentForUser(UserId $userId, int $limit = 20): array
    {
        return $this->readModel->listRecentForUser($userId, $limit);
    }

    public function undo(AuditEntryId $id, UserId $performedBy): AuditEntryId
    {
        $entry = $this->repository->findById($id) ?? throw AuditEntryNotFoundException::forId($id);

        if ($entry->undoneAt() instanceof DateTimeImmutable) {
            throw AuditEntryAlreadyUndoneException::forId($id);
        }

        $undoer = $this->undoDispatcher->findUndoerFor($entry->action())
            ?? throw UndoNotSupportedException::forAction($entry->action());

        $entryDto = $this->toDto($entry);
        $undoer->undo($entryDto, $performedBy);

        $now = $this->clock->now();
        $entry->markUndone($now, $performedBy);
        $this->repository->save($entry);

        return $this->record(new AuditEntryWithState(
            actorUserId: $performedBy,
            action: $entry->action() . '.undone',
            occurredAt: $now,
            resourceType: $entry->resourceType(),
            resourceId: $entry->resourceId(),
            metadata: ['original_action' => $entry->action()],
            revertsEntryId: $entry->id(),
        ));
    }

    public function describeUndo(AuditEntryId $id): string
    {
        $entry = $this->repository->findById($id) ?? throw AuditEntryNotFoundException::forId($id);

        $describer = $this->undoDispatcher->findDescriberFor($entry->action());

        if (!$describer instanceof UndoDescriberInterface) {
            return sprintf('Cofnij akcję %s', $entry->action());
        }

        return $describer->describe($this->toDto($entry));
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

    private function toDto(AuditLogEntry $entry): AuditEntryDto
    {
        return new AuditEntryDto(
            id: $entry->id(),
            actorUserId: $entry->actorUserId(),
            actorEmail: null,
            action: $entry->action(),
            resourceType: $entry->resourceType(),
            resourceId: $entry->resourceId(),
            beforeState: $entry->beforeState(),
            afterState: $entry->afterState(),
            metadata: $entry->metadata(),
            occurredAt: $entry->occurredAt(),
            undoneAt: $entry->undoneAt(),
            undoneBy: $entry->undoneBy(),
            undoneByEmail: null,
            revertsEntryId: $entry->revertsEntryId(),
        );
    }
}
