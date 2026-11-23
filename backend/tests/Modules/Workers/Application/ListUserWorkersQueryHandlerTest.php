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

use function str_repeat;

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

        $onlineWorker = Worker::create(WorkerId::generate(), $owner, 'Online', 'worker-online', $createdAt);
        $onlineWorker->recordHeartbeat(new DateTimeImmutable('2026-11-18T15:04:00+00:00'));

        $workers->save($onlineWorker);

        $offlineWorker = Worker::create(WorkerId::generate(), $owner, 'Offline', 'worker-offline', $createdAt);
        $workers->save($offlineWorker);

        $foreignWorker = Worker::create(WorkerId::generate(), $other, 'Foreign', 'worker-foreign', $createdAt);
        $workers->save($foreignWorker);

        $sut = new ListUserWorkersQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $result = $sut(new ListUserWorkersQuery($owner));

        self::assertCount(2, $result);
        self::assertSame('Online', $result[0]->name);
        self::assertTrue($result[0]->isOnline);
        self::assertSame('Offline', $result[1]->name);
        self::assertFalse($result[1]->isOnline);
    }

    public function testShouldExposeFingerprintHashShortAndStatusForEachWorker(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');

        $missingWorker = Worker::create(WorkerId::generate(), $owner, 'NoFp', 'worker-nofp', $createdAt);
        $workers->save($missingWorker);

        $okWorker = Worker::create(WorkerId::generate(), $owner, 'Ok', 'worker-ok', $createdAt);
        $okWorker->updateFingerprint(str_repeat('a', 64), null, $createdAt);

        $workers->save($okWorker);

        $warningWorker = Worker::create(WorkerId::generate(), $owner, 'Warning', 'worker-warn', $createdAt);
        $warningWorker->updateFingerprint(str_repeat('a', 64), null, $createdAt);
        $warningWorker->recordFingerprintMismatch(str_repeat('b', 64), null, new DateTimeImmutable('2026-11-18T15:10:00+00:00'));

        $workers->save($warningWorker);

        $sut = new ListUserWorkersQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:30:00+00:00')));

        $result = $sut(new ListUserWorkersQuery($owner));

        self::assertCount(3, $result);
        self::assertNull($result[0]->fingerprintHashShort);
        self::assertSame('missing', $result[0]->fingerprintStatus);
        self::assertSame(str_repeat('a', 12), $result[1]->fingerprintHashShort);
        self::assertSame('ok', $result[1]->fingerprintStatus);
        self::assertSame(str_repeat('a', 12), $result[2]->fingerprintHashShort);
        self::assertSame('warning', $result[2]->fingerprintStatus);
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
