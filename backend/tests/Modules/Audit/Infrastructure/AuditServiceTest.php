<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Audit\Api\AuditEntryWithState;
use Modules\Audit\Infrastructure\Doctrine\Repository\DoctrineAuditLogEntryRepository;
use Modules\Audit\Infrastructure\Service\AuditService;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\Uuid7Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AuditServiceTest extends KernelTestCase
{
    private AuditService $service;

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $repository = new DoctrineAuditLogEntryRepository($em);
        $this->service = new AuditService($repository, new Uuid7Generator());

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->connection = $connection;

        $this->connection->executeStatement('TRUNCATE audit.audit_log');
    }

    public function testShouldPersistCoreAuditEntryAndExtractStateFromMetadata(): void
    {
        $actorId = new UserId(Uuid::v7());
        $entry = new AuditEntry(
            userId: $actorId,
            action: 'user_management.user.created',
            occurredAt: new DateTimeImmutable('2026-11-08T10:00:00+00:00'),
            resourceType: 'User',
            resourceId: (string) Uuid::v7(),
            metadata: [
                'ip' => '127.0.0.1',
                'before_state' => null,
                'after_state' => ['email' => 'new@example.com'],
            ],
        );

        $this->service->log($entry);

        $row = $this->connection->fetchAssociative(
            "SELECT action, before_state, after_state, metadata FROM audit.audit_log WHERE action = 'user_management.user.created'",
        );
        self::assertIsArray($row);
        self::assertNull($row['before_state']);
        self::assertSame(['email' => 'new@example.com'], json_decode((string) $row['after_state'], true));
        $metadata = json_decode((string) $row['metadata'], true);
        self::assertSame(['ip' => '127.0.0.1'], $metadata);
    }

    public function testShouldRecordRichEntryAndReturnGeneratedId(): void
    {
        $actorId = new UserId(Uuid::v7());
        $entry = new AuditEntryWithState(
            actorUserId: $actorId,
            action: 'user_management.user.deleted',
            occurredAt: new DateTimeImmutable('2026-11-08T10:05:00+00:00'),
            resourceType: 'User',
            resourceId: (string) Uuid::v7(),
            beforeState: ['email' => 'old@example.com'],
            metadata: ['ip' => '10.0.0.1'],
        );

        $returnedId = $this->service->record($entry);

        $row = $this->connection->fetchAssociative(
            'SELECT id, before_state, after_state FROM audit.audit_log WHERE id = ?',
            [(string) $returnedId],
        );
        self::assertIsArray($row);
        self::assertSame(['email' => 'old@example.com'], json_decode((string) $row['before_state'], true));
        self::assertNull($row['after_state']);
    }

    public function testShouldCreateDistinctIdsForSuccessiveRecordCalls(): void
    {
        $first = $this->service->record($this->sampleEntry());
        $second = $this->service->record($this->sampleEntry());

        self::assertFalse($first->equals($second));
        $count = $this->connection->fetchOne("SELECT COUNT(*) FROM audit.audit_log WHERE action = 'test.action'");
        self::assertSame(2, (int) $count);
    }

    private function sampleEntry(): AuditEntryWithState
    {
        return new AuditEntryWithState(
            actorUserId: new UserId(Uuid::v7()),
            action: 'test.action',
            occurredAt: new DateTimeImmutable('2026-11-08T10:00:00+00:00'),
        );
    }
}
