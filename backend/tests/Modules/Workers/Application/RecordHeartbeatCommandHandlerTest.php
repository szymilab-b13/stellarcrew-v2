<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\RecordHeartbeatCommand;
use Modules\Workers\Application\RecordHeartbeatCommandHandler;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class RecordHeartbeatCommandHandlerTest extends TestCase
{
    public function testShouldBumpLastHeartbeatAtAndPersist(): void
    {
        $workers = new InMemoryWorkerRepository();
        $workerId = WorkerId::generate();
        $workers->save(Worker::create(
            $workerId,
            new UserId(Uuid::v7()),
            'Worker',
            'worker-xyz',
            new DateTimeImmutable('2026-11-18T10:00:00+00:00'),
        ));
        $now = new DateTimeImmutable('2026-11-23T14:00:00+00:00');
        $sut = new RecordHeartbeatCommandHandler($workers, $this->fixedClock($now));

        $sut(new RecordHeartbeatCommand(workerId: $workerId));

        $worker = $workers->findById($workerId);
        self::assertNotNull($worker);
        self::assertEquals($now, $worker->lastHeartbeatAt());
    }

    public function testShouldThrowWhenWorkerUnknown(): void
    {
        $sut = new RecordHeartbeatCommandHandler(
            new InMemoryWorkerRepository(),
            $this->fixedClock(new DateTimeImmutable('2026-11-23T14:00:00+00:00')),
        );

        $this->expectException(WorkerNotFoundException::class);
        $sut(new RecordHeartbeatCommand(workerId: WorkerId::generate()));
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
