<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Audit\Infrastructure\ReadModel\DbalAuditEntriesReadModel;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class DbalAuditEntriesReadModelTest extends KernelTestCase
{
    private DbalAuditEntriesReadModel $readModel;

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->connection = $connection;
        $this->readModel = new DbalAuditEntriesReadModel($connection);

        $this->connection->executeStatement('TRUNCATE audit.audit_log');
        $this->connection->executeStatement("DELETE FROM auth.users WHERE email LIKE '%@example.com'");
    }

    public function testShouldPaginateEntriesSortedDescByOccurredAt(): void
    {
        $actor = $this->seedActor('alice@example.com');
        $this->seedEntry(actor: $actor, action: 'a.one', occurredAt: '2026-11-01T10:00:00+00:00');
        $this->seedEntry(actor: $actor, action: 'a.two', occurredAt: '2026-11-02T10:00:00+00:00');
        $this->seedEntry(actor: $actor, action: 'a.three', occurredAt: '2026-11-03T10:00:00+00:00');

        $page = $this->readModel->search(new AuditQuery(perPage: 2));

        self::assertSame(3, $page->total);
        self::assertSame(1, $page->page);
        self::assertSame(2, $page->perPage);
        self::assertCount(2, $page->items);
        self::assertSame('a.three', $page->items[0]->action);
        self::assertSame('a.two', $page->items[1]->action);
    }

    public function testShouldFilterByActorUserId(): void
    {
        $alice = $this->seedActor('alice@example.com');
        $bob = $this->seedActor('bob@example.com');
        $this->seedEntry(actor: $alice, action: 'from.alice');
        $this->seedEntry(actor: $bob, action: 'from.bob');

        $page = $this->readModel->search(new AuditQuery(actorUserId: $alice));

        self::assertSame(1, $page->total);
        self::assertSame('from.alice', $page->items[0]->action);
        self::assertSame('alice@example.com', $page->items[0]->actorEmail);
    }

    public function testShouldFilterByActionExactMatch(): void
    {
        $actor = $this->seedActor('alice@example.com');
        $this->seedEntry(actor: $actor, action: 'users.created');
        $this->seedEntry(actor: $actor, action: 'users.updated');

        $page = $this->readModel->search(new AuditQuery(action: 'users.created'));

        self::assertSame(1, $page->total);
        self::assertSame('users.created', $page->items[0]->action);
    }

    public function testShouldFilterByResourceTypeAndId(): void
    {
        $actor = $this->seedActor('alice@example.com');
        $resourceA = (string) Uuid::v7();
        $resourceB = (string) Uuid::v7();
        $this->seedEntry(actor: $actor, action: 'rs.touched', resourceType: 'User', resourceId: $resourceA);
        $this->seedEntry(actor: $actor, action: 'rs.touched', resourceType: 'User', resourceId: $resourceB);

        $page = $this->readModel->search(new AuditQuery(resourceType: 'User', resourceId: $resourceA));

        self::assertSame(1, $page->total);
        self::assertSame($resourceA, $page->items[0]->resourceId);
    }

    public function testShouldFilterByDateRange(): void
    {
        $actor = $this->seedActor('alice@example.com');
        $this->seedEntry(actor: $actor, action: 'd.one', occurredAt: '2026-11-01T10:00:00+00:00');
        $this->seedEntry(actor: $actor, action: 'd.two', occurredAt: '2026-11-05T10:00:00+00:00');
        $this->seedEntry(actor: $actor, action: 'd.three', occurredAt: '2026-11-10T10:00:00+00:00');

        $page = $this->readModel->search(new AuditQuery(
            from: new DateTimeImmutable('2026-11-04T00:00:00+00:00'),
            to: new DateTimeImmutable('2026-11-09T00:00:00+00:00'),
        ));

        self::assertSame(1, $page->total);
        self::assertSame('d.two', $page->items[0]->action);
    }

    public function testShouldExcludeUndoneWhenIncludeUndoneIsFalse(): void
    {
        $actor = $this->seedActor('alice@example.com');
        $this->seedEntry(actor: $actor, action: 'live.one');
        $undoneId = $this->seedEntry(
            actor: $actor,
            action: 'undone.one',
            undoneAt: '2026-11-07T11:00:00+00:00',
            undoneBy: $actor,
        );

        $withUndone = $this->readModel->search(new AuditQuery(includeUndone: true));
        self::assertSame(2, $withUndone->total);

        $withoutUndone = $this->readModel->search(new AuditQuery(includeUndone: false));
        self::assertSame(1, $withoutUndone->total);
        self::assertSame('live.one', $withoutUndone->items[0]->action);
        self::assertNotSame((string) $undoneId, (string) $withoutUndone->items[0]->id);
    }

    public function testShouldReturnNullFromFindForMissingId(): void
    {
        self::assertNull($this->readModel->find(AuditEntryId::generate()));
    }

    public function testShouldFindSingleEntryWithActorEmail(): void
    {
        $actor = $this->seedActor('alice@example.com');
        $id = $this->seedEntry(actor: $actor, action: 'f.one');

        $entry = $this->readModel->find($id);
        self::assertNotNull($entry);
        self::assertSame('f.one', $entry->action);
        self::assertSame('alice@example.com', $entry->actorEmail);
    }

    private function seedActor(string $email): UserId
    {
        $id = UserId::generate();
        $this->connection->executeStatement(
            'INSERT INTO auth.users (id, email, password_hash, role, display_name, created_at) '
            . "VALUES (:id, :email, 'x', 'ROLE_USER', NULL, NOW())",
            ['id' => (string) $id, 'email' => $email],
        );

        return $id;
    }

    private function seedEntry(
        UserId $actor,
        string $action,
        string $occurredAt = '2026-11-07T10:00:00+00:00',
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?string $undoneAt = null,
        ?UserId $undoneBy = null,
    ): AuditEntryId {
        $id = AuditEntryId::generate();
        $this->connection->executeStatement(
            'INSERT INTO audit.audit_log (id, actor_user_id, action, resource_type, resource_id, '
            . 'before_state, after_state, metadata, occurred_at, undone_at, undone_by, reverts_entry_id) '
            . "VALUES (:id, :actor, :action, :rt, :ri, NULL, NULL, '{}'::jsonb, :occurred, :undone_at, :undone_by, NULL)",
            [
                'id' => (string) $id,
                'actor' => (string) $actor,
                'action' => $action,
                'rt' => $resourceType,
                'ri' => $resourceId,
                'occurred' => $occurredAt,
                'undone_at' => $undoneAt,
                'undone_by' => $undoneBy instanceof UserId ? (string) $undoneBy : null,
            ],
        );

        return $id;
    }
}
