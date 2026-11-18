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
use Modules\Workers\Application\ListUserWorkersQueryHandler;
use Modules\Workers\Application\RevokeAllOwnerWorkersCommand;
use Modules\Workers\Application\RevokeAllOwnerWorkersCommandHandler;
use Modules\Workers\Application\RevokeWorkerCommandHandler;
use Modules\Workers\Domain\Worker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RevokeAllOwnerWorkersCommandHandlerTest extends TestCase
{
    public function testShouldReturnZeroAndDispatchNothingWhenOwnerHasNoWorkers(): void
    {
        $sut = $this->buildHandler(new InMemoryWorkerRepository(), new SpyEventDispatcher());

        self::assertSame(0, $sut(new RevokeAllOwnerWorkersCommand(new UserId(Uuid::v7()))));
    }

    public function testShouldRevokeAllOwnerWorkersAndIgnoreForeign(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $foreign = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $workerAId = WorkerId::generate();
        $workerBId = WorkerId::generate();
        $foreignId = WorkerId::generate();
        $workers->save(Worker::create($workerAId, $owner, 'A', 'worker-a', 'hash', $createdAt));
        $workers->save(Worker::create($workerBId, $owner, 'B', 'worker-b', 'hash', $createdAt));
        $workers->save(Worker::create($foreignId, $foreign, 'F', 'worker-f', 'hash', $createdAt));

        $events = new SpyEventDispatcher();
        $sut = $this->buildHandler($workers, $events);

        $count = $sut(new RevokeAllOwnerWorkersCommand($owner));

        self::assertSame(2, $count);
        self::assertNull($workers->findById($workerAId));
        self::assertNull($workers->findById($workerBId));
        self::assertNotNull($workers->findById($foreignId));
        self::assertCount(2, $events->dispatched);
        self::assertContainsOnlyInstancesOf(WorkerRevoked::class, $events->dispatched);
    }

    private function buildHandler(
        InMemoryWorkerRepository $workers,
        SpyEventDispatcher $events,
    ): RevokeAllOwnerWorkersCommandHandler {
        $clock = $this->fixedClock(new DateTimeImmutable('2026-11-18T12:00:00+00:00'));
        $revokeWorker = new RevokeWorkerCommandHandler($workers, new SpyAuditLogger(), $clock, $events);
        $listWorkers = new ListUserWorkersQueryHandler($workers, $clock);

        return new RevokeAllOwnerWorkersCommandHandler($revokeWorker, $listWorkers);
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
