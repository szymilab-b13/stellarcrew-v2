<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\ReadModel;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

use function is_array;
use function json_decode;
use function max;
use function min;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Dto\AuditPage;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Audit\Application\ReadModel\AuditEntriesReadModel;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;

final readonly class DbalAuditEntriesReadModel implements AuditEntriesReadModel
{
    private const int MAX_PER_PAGE = 200;

    public function __construct(private Connection $connection)
    {
    }

    public function search(AuditQuery $query): AuditPage
    {
        $perPage = max(1, min($query->perPage, self::MAX_PER_PAGE));
        $page = max(1, $query->page);
        $offset = ($page - 1) * $perPage;

        [$where, $params, $types] = $this->buildFilters($query);
        $whereClause = $where === '' ? '' : ' WHERE ' . $where;

        $totalRow = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM audit.audit_log al' . $whereClause,
            $params,
            $types,
        );
        $total = (int) $totalRow;

        $rows = $this->connection->fetchAllAssociative(
            'SELECT al.id, al.actor_user_id, al.action, al.resource_type, al.resource_id, '
            . 'al.before_state, al.after_state, al.metadata, al.occurred_at, '
            . 'al.undone_at, al.undone_by, al.reverts_entry_id, '
            . 'actor.email AS actor_email, undoer.email AS undone_by_email '
            . 'FROM audit.audit_log al '
            . 'LEFT JOIN auth.users actor ON actor.id = al.actor_user_id '
            . 'LEFT JOIN auth.users undoer ON undoer.id = al.undone_by'
            . $whereClause
            . ' ORDER BY al.occurred_at DESC LIMIT :limit OFFSET :offset',
            [...$params, 'limit' => $perPage, 'offset' => $offset],
            [...$types, 'limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        return new AuditPage(
            items: $items,
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    public function find(AuditEntryId $id): ?AuditEntryDto
    {
        $row = $this->connection->fetchAssociative(
            'SELECT al.id, al.actor_user_id, al.action, al.resource_type, al.resource_id, '
            . 'al.before_state, al.after_state, al.metadata, al.occurred_at, '
            . 'al.undone_at, al.undone_by, al.reverts_entry_id, '
            . 'actor.email AS actor_email, undoer.email AS undone_by_email '
            . 'FROM audit.audit_log al '
            . 'LEFT JOIN auth.users actor ON actor.id = al.actor_user_id '
            . 'LEFT JOIN auth.users undoer ON undoer.id = al.undone_by '
            . 'WHERE al.id = :id',
            ['id' => (string) $id],
            ['id' => ParameterType::STRING],
        );

        if ($row === false) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, ParameterType>}
     */
    private function buildFilters(AuditQuery $query): array
    {
        $parts = [];
        $params = [];
        $types = [];

        if ($query->actorUserId instanceof UserId) {
            $parts[] = 'al.actor_user_id = :actor_user_id';
            $params['actor_user_id'] = (string) $query->actorUserId;
            $types['actor_user_id'] = ParameterType::STRING;
        }

        if ($query->action !== null) {
            $parts[] = 'al.action = :action';
            $params['action'] = $query->action;
            $types['action'] = ParameterType::STRING;
        }

        if ($query->resourceType !== null) {
            $parts[] = 'al.resource_type = :resource_type';
            $params['resource_type'] = $query->resourceType;
            $types['resource_type'] = ParameterType::STRING;
        }

        if ($query->resourceId !== null) {
            $parts[] = 'al.resource_id = :resource_id';
            $params['resource_id'] = $query->resourceId;
            $types['resource_id'] = ParameterType::STRING;
        }

        if ($query->from instanceof DateTimeImmutable) {
            $parts[] = 'al.occurred_at >= :from';
            $params['from'] = $query->from->format('c');
            $types['from'] = ParameterType::STRING;
        }

        if ($query->to instanceof DateTimeImmutable) {
            $parts[] = 'al.occurred_at <= :to';
            $params['to'] = $query->to->format('c');
            $types['to'] = ParameterType::STRING;
        }

        if (!$query->includeUndone) {
            $parts[] = 'al.undone_at IS NULL';
        }

        return [implode(' AND ', $parts), $params, $types];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): AuditEntryDto
    {
        $occurredAt = $row['occurred_at'];
        if (!$occurredAt instanceof DateTimeImmutable) {
            $occurredAt = new DateTimeImmutable((string) $occurredAt);
        }

        $undoneAt = $row['undone_at'];
        if ($undoneAt !== null && !$undoneAt instanceof DateTimeImmutable) {
            $undoneAt = new DateTimeImmutable((string) $undoneAt);
        }

        $undoneBy = $row['undone_by'] !== null
            ? UserId::fromString((string) $row['undone_by'])
            : null;

        $revertsEntryId = $row['reverts_entry_id'] !== null
            ? AuditEntryId::fromString((string) $row['reverts_entry_id'])
            : null;

        return new AuditEntryDto(
            id: AuditEntryId::fromString((string) $row['id']),
            actorUserId: UserId::fromString((string) $row['actor_user_id']),
            actorEmail: $row['actor_email'] === null ? null : (string) $row['actor_email'],
            action: (string) $row['action'],
            resourceType: $row['resource_type'] === null ? null : (string) $row['resource_type'],
            resourceId: $row['resource_id'] === null ? null : (string) $row['resource_id'],
            beforeState: $this->decodeJson($row['before_state']),
            afterState: $this->decodeJson($row['after_state']),
            metadata: $this->decodeJson($row['metadata']) ?? [],
            occurredAt: $occurredAt,
            undoneAt: $undoneAt,
            undoneBy: $undoneBy,
            undoneByEmail: $row['undone_by_email'] === null ? null : (string) $row['undone_by_email'],
            revertsEntryId: $revertsEntryId,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
