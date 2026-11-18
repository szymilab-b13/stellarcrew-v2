<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\ListUserWorkersQuery;
use Modules\Workers\Application\ListUserWorkersQueryHandler;
use Modules\Workers\Domain\Worker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListUserWorkersQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyListWhenUserHasNoWorkers(): void
    {
        $sut = new ListUserWorkersQueryHandler(new InMemoryWorkerRepository(), $this->fixedClock(new DateTimeImmutable('2026-11-18T15:00:00+00:00')));

        self::assertSame([], $sut(new ListUserWorkersQuery(new UserId(Uuid::v7()))));
    }

    public function testShouldReturnOwnerWorkersWithOnlineFlag(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $other = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');

        $onlineWorker = Worker::create(WorkerId::generate(), $owner, 'Online', 'worker-online', 'hash', $createdAt);
        $onlineWorker->recordHeartbeat(new DateTimeImmutable('2026-11-18T15:04:00+00:00'));
        $workers->save($onlineWorker);

        $offlineWorker = Worker::create(WorkerId::generate(), $owner, 'Offline', 'worker-offline', 'hash', $createdAt);
        $workers->save($offlineWorker);

        $foreignWorker = Worker::create(WorkerId::generate(), $other, 'Foreign', 'worker-foreign', 'hash', $createdAt);
        $workers->save($foreignWorker);

        $sut = new ListUserWorkersQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $result = $sut(new ListUserWorkersQuery($owner));

        self::assertCount(2, $result);
        self::assertSame('Online', $result[0]->name);
        self::assertTrue($result[0]->isOnline);
        self::assertSame('Offline', $result[1]->name);
        self::assertFalse($result[1]->isOnline);
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
