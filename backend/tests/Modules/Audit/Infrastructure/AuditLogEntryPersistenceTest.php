<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Audit\Domain\AuditLogEntry;
use Modules\Audit\Infrastructure\Doctrine\Repository\DoctrineAuditLogEntryRepository;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AuditLogEntryPersistenceTest extends KernelTestCase
{
    private DoctrineAuditLogEntryRepository $repository;

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $this->repository = new DoctrineAuditLogEntryRepository($em);

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->connection = $connection;

        $this->connection->executeStatement('TRUNCATE audit.audit_log');
    }

    public function testShouldPersistAndHydrateAuditEntryWithFullPayload(): void
    {
        $id = AuditEntryId::generate();
        $actor = new UserId(Uuid::v7());
        $entry = new AuditLogEntry(
            id: $id,
            actorUserId: $actor,
            action: 'user_management.user.created',
            occurredAt: new DateTimeImmutable('2026-11-08T10:00:00+00:00'),
            resourceType: 'User',
            resourceId: (string) Uuid::v7(),
            afterState: ['email' => 'new@example.com', 'is_super_admin' => false],
            metadata: ['ip' => '127.0.0.1', 'request_id' => 'req-123'],
        );

        $this->repository->save($entry);

        $hydrated = $this->repository->findById($id);
        self::assertNotNull($hydrated);
        self::assertTrue($id->equals($hydrated->id()));
        self::assertSame('user_management.user.created', $hydrated->action());
        self::assertSame('User', $hydrated->resourceType());
        self::assertNull($hydrated->beforeState());
        self::assertSame(
            ['email' => 'new@example.com', 'is_super_admin' => false],
            $hydrated->afterState(),
        );
        self::assertSame(['ip' => '127.0.0.1', 'request_id' => 'req-123'], $hydrated->metadata());
    }

    public function testShouldLinkRevertsEntryIdToAnotherEntry(): void
    {
        $original = $this->persistEntry(action: 'user_management.user.created');

        $undo = new AuditLogEntry(
            id: AuditEntryId::generate(),
            actorUserId: new UserId(Uuid::v7()),
            action: 'user_management.user.created.undone',
            occurredAt: new DateTimeImmutable('2026-11-08T10:05:00+00:00'),
            resourceType: 'User',
            resourceId: $original->resourceId(),
            revertsEntryId: $original->id(),
        );
        $this->repository->save($undo);

        $hydratedUndo = $this->repository->findById($undo->id());
        self::assertNotNull($hydratedUndo);
        self::assertNotNull($hydratedUndo->revertsEntryId());
        self::assertTrue($original->id()->equals($hydratedUndo->revertsEntryId()));
    }

    public function testShouldHaveAllExpectedIndexesOnAuditLog(): void
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT indexname FROM pg_indexes WHERE schemaname = 'audit' AND tablename = 'audit_log'",
        );
        $indexes = array_map(static fn (array $row): string => (string) $row['indexname'], $rows);

        self::assertContains('idx_audit_log_actor_time', $indexes);
        self::assertContains('idx_audit_log_action_time', $indexes);
        self::assertContains('idx_audit_log_resource_time', $indexes);
        self::assertContains('idx_audit_log_occurred_at', $indexes);
        self::assertContains('idx_audit_log_undone_at', $indexes);
    }

    private function persistEntry(string $action): AuditLogEntry
    {
        $entry = new AuditLogEntry(
            id: AuditEntryId::generate(),
            actorUserId: new UserId(Uuid::v7()),
            action: $action,
            occurredAt: new DateTimeImmutable('2026-11-08T10:00:00+00:00'),
            resourceType: 'User',
            resourceId: (string) Uuid::v7(),
            afterState: ['email' => 'x@example.com'],
        );
        $this->repository->save($entry);

        return $entry;
    }
}
