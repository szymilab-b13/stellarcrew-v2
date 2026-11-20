<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Core\SpyEventDispatcher;
use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Api\Event\WorkerRevoked;
use Modules\Workers\Application\RevokeWorkerCommand;
use Modules\Workers\Application\RevokeWorkerCommandHandler;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class RevokeWorkerCommandHandlerTest extends TestCase
{
    public function testShouldDeleteWorkerAndDispatchEventWhenActorIsOwner(): void
    {
        $workers = new InMemoryWorkerRepository();
        $workerId = WorkerId::generate();
        $owner = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $workers->save(Worker::create($workerId, $owner, 'Test', 'worker-xyz', $createdAt));
        $events = new SpyEventDispatcher();
        $now = new DateTimeImmutable('2026-11-18T12:00:00+00:00');
        $audit = new SpyAuditLogger();
        $sut = new RevokeWorkerCommandHandler($workers, $audit, $this->fixedClock($now), $events);

        $sut(new RevokeWorkerCommand($workerId, $owner));

        self::assertNull($workers->findById($workerId));
        self::assertCount(1, $events->dispatched);
        $dispatched = $events->dispatched[0];
        self::assertInstanceOf(WorkerRevoked::class, $dispatched);
        self::assertTrue($dispatched->workerId->equals($workerId));
        self::assertTrue($dispatched->ownerUserId->equals($owner));
        self::assertEquals($now, $dispatched->occurredAt);
    }

    public function testShouldWriteAuditEntryWhenRevoked(): void
    {
        $workers = new InMemoryWorkerRepository();
        $workerId = WorkerId::generate();
        $owner = new UserId(Uuid::v7());
        $workers->save(Worker::create(
            $workerId,
            $owner,
            'My Laptop',
            'worker-abc',
            new DateTimeImmutable('2026-11-18T10:00:00+00:00'),
        ));
        $audit = new SpyAuditLogger();
        $sut = new RevokeWorkerCommandHandler(
            $workers,
            $audit,
            $this->fixedClock(new DateTimeImmutable('2026-11-18T12:00:00+00:00')),
            new SpyEventDispatcher(),
        );

        $sut(new RevokeWorkerCommand($workerId, $owner));

        self::assertCount(1, $audit->entries);
        self::assertSame(RevokeWorkerCommandHandler::AUDIT_ACTION, $audit->entries[0]->action);
        self::assertSame((string) $workerId, $audit->entries[0]->resourceId);
        self::assertSame('My Laptop', $audit->entries[0]->metadata['name']);
        self::assertSame('worker-abc', $audit->entries[0]->metadata['rabbitmq_user']);
    }

    public function testShouldThrowWhenWorkerUnknown(): void
    {
        $sut = new RevokeWorkerCommandHandler(
            new InMemoryWorkerRepository(),
            new SpyAuditLogger(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T12:00:00+00:00')),
            new SpyEventDispatcher(),
        );

        $this->expectException(WorkerNotFoundException::class);
        $sut(new RevokeWorkerCommand(WorkerId::generate(), new UserId(Uuid::v7())));
    }

    public function testShouldThrowWhenActorIsNotOwner(): void
    {
        $workers = new InMemoryWorkerRepository();
        $workerId = WorkerId::generate();
        $owner = new UserId(Uuid::v7());
        $foreign = new UserId(Uuid::v7());
        $workers->save(Worker::create(
            $workerId,
            $owner,
            'Test',
            'worker-xyz',
            new DateTimeImmutable('2026-11-18T10:00:00+00:00'),
        ));
        $events = new SpyEventDispatcher();
        $sut = new RevokeWorkerCommandHandler(
            $workers,
            new SpyAuditLogger(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T12:00:00+00:00')),
            $events,
        );

        try {
            $sut(new RevokeWorkerCommand($workerId, $foreign));
            self::fail('AccessDeniedException expected');
        } catch (AccessDeniedException) {
            self::assertNotNull($workers->findById($workerId));
            self::assertSame([], $events->dispatched);
        }
    }

    private function fixedClock(DateTimeImmutable $now): Clock
    {
        return new readonly class ($now) implements Clock {
            public function __construct(private DateTimeImmutable $now)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->now;
            }
        };
    }
}
