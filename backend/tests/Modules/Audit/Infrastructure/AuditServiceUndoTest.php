<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use App\Tests\Fixtures\Audit\StubUndoDescriber;
use App\Tests\Fixtures\Audit\StubUndoer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Audit\Api\AuditEntryWithState;
use Modules\Audit\Api\Exception\AuditEntryAlreadyUndoneException;
use Modules\Audit\Api\Exception\AuditEntryNotFoundException;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\Exception\UndoNotSupportedException;
use Modules\Audit\Infrastructure\Doctrine\Repository\DoctrineAuditLogEntryRepository;
use Modules\Audit\Infrastructure\ReadModel\DbalAuditEntriesReadModel;
use Modules\Audit\Infrastructure\Service\AuditService;
use Modules\Audit\Infrastructure\Service\UndoDispatcher;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\Uuid7Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class AuditServiceUndoTest extends KernelTestCase
{
    private Connection $connection;

    private DoctrineAuditLogEntryRepository $repository;

    private DbalAuditEntriesReadModel $readModel;

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
        $this->readModel = new DbalAuditEntriesReadModel($connection);

        $this->connection->executeStatement('TRUNCATE audit.audit_log');
    }

    public function testShouldCallUndoerMarkOriginalAndRecordReverseEntry(): void
    {
        $actor = new UserId(Uuid::v7());
        $performedBy = new UserId(Uuid::v7());
        $undoer = new StubUndoer(supports: 'user_management.user.deactivated');
        $sut = $this->service(undoers: [$undoer], now: '2026-11-08T12:00:00+00:00');

        $originalId = $sut->record(new AuditEntryWithState(
            actorUserId: $actor,
            action: 'user_management.user.deactivated',
            occurredAt: new DateTimeImmutable('2026-11-08T11:00:00+00:00'),
            resourceType: 'User',
            resourceId: (string) Uuid::v7(),
        ));

        $newId = $sut->undo($originalId, $performedBy);

        self::assertCount(1, $undoer->calls);
        self::assertTrue($undoer->calls[0]['performedBy']->equals($performedBy));

        $original = $this->repository->findById($originalId);
        self::assertNotNull($original);
        self::assertNotNull($original->undoneAt());
        self::assertSame('2026-11-08T12:00:00+00:00', $original->undoneAt()->format(DATE_ATOM));
        self::assertNotNull($original->undoneBy());
        self::assertTrue($original->undoneBy()->equals($performedBy));

        $new = $this->repository->findById($newId);
        self::assertNotNull($new);
        self::assertSame('user_management.user.deactivated.undone', $new->action());
        self::assertTrue($new->actorUserId()->equals($performedBy));
        $revertsId = $new->revertsEntryId();
        self::assertNotNull($revertsId);
        self::assertTrue($revertsId->equals($originalId));
        self::assertSame(['original_action' => 'user_management.user.deactivated'], $new->metadata());
    }

    public function testShouldThrowWhenEntryNotFound(): void
    {
        $sut = $this->service();

        $this->expectException(AuditEntryNotFoundException::class);

        $sut->undo(new AuditEntryId(Uuid::v7()), new UserId(Uuid::v7()));
    }

    public function testShouldThrowWhenEntryAlreadyUndone(): void
    {
        $undoer = new StubUndoer(supports: 'user_management.user.deactivated');
        $sut = $this->service(undoers: [$undoer]);

        $originalId = $sut->record(new AuditEntryWithState(
            actorUserId: new UserId(Uuid::v7()),
            action: 'user_management.user.deactivated',
            occurredAt: new DateTimeImmutable('2026-11-08T11:00:00+00:00'),
        ));
        $sut->undo($originalId, new UserId(Uuid::v7()));

        $this->expectException(AuditEntryAlreadyUndoneException::class);

        $sut->undo($originalId, new UserId(Uuid::v7()));
    }

    public function testShouldThrowWhenNoUndoerForAction(): void
    {
        $sut = $this->service();

        $originalId = $sut->record(new AuditEntryWithState(
            actorUserId: new UserId(Uuid::v7()),
            action: 'user_management.user.password_reset',
            occurredAt: new DateTimeImmutable('2026-11-08T11:00:00+00:00'),
        ));

        $this->expectException(UndoNotSupportedException::class);

        $sut->undo($originalId, new UserId(Uuid::v7()));
    }

    public function testShouldNotMarkOriginalWhenUndoerThrowsConflict(): void
    {
        $conflict = new UndoConflictException('already_deactivated');
        $undoer = new StubUndoer(supports: 'user_management.user.deactivated', throws: $conflict);
        $sut = $this->service(undoers: [$undoer]);

        $originalId = $sut->record(new AuditEntryWithState(
            actorUserId: new UserId(Uuid::v7()),
            action: 'user_management.user.deactivated',
            occurredAt: new DateTimeImmutable('2026-11-08T11:00:00+00:00'),
        ));

        try {
            $sut->undo($originalId, new UserId(Uuid::v7()));
            self::fail('Expected UndoConflictException');
        } catch (UndoConflictException $undoConflictException) {
            self::assertSame('already_deactivated', $undoConflictException->conflictCode());
        }

        $original = $this->repository->findById($originalId);
        self::assertNotNull($original);
        self::assertNull($original->undoneAt());
        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM audit.audit_log');
        self::assertSame(1, $count);
    }

    public function testShouldDescribeUndoThroughRegisteredDescriber(): void
    {
        $describer = new StubUndoDescriber(supports: 'user_management.user.deactivated', text: 'Przywróć użytkownika');
        $sut = $this->service(describers: [$describer]);

        $id = $sut->record(new AuditEntryWithState(
            actorUserId: new UserId(Uuid::v7()),
            action: 'user_management.user.deactivated',
            occurredAt: new DateTimeImmutable('2026-11-08T11:00:00+00:00'),
        ));

        self::assertSame('Przywróć użytkownika', $sut->describeUndo($id));
    }

    public function testShouldDescribeUndoWithFallbackWhenNoDescriberRegistered(): void
    {
        $sut = $this->service();

        $id = $sut->record(new AuditEntryWithState(
            actorUserId: new UserId(Uuid::v7()),
            action: 'workspaces.workspace.deleted',
            occurredAt: new DateTimeImmutable('2026-11-08T11:00:00+00:00'),
        ));

        self::assertSame('Cofnij akcję workspaces.workspace.deleted', $sut->describeUndo($id));
    }

    /**
     * @param list<StubUndoer>         $undoers
     * @param list<StubUndoDescriber>  $describers
     */
    private function service(array $undoers = [], array $describers = [], string $now = '2026-11-08T12:00:00+00:00'): AuditService
    {
        return new AuditService(
            $this->repository,
            $this->readModel,
            new Uuid7Generator(),
            new UndoDispatcher($undoers, $describers),
            new FrozenClock(new DateTimeImmutable($now)),
        );
    }
}
