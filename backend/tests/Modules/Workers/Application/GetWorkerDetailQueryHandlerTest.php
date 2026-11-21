<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\GetWorkerDetailQuery;
use Modules\Workers\Application\GetWorkerDetailQueryHandler;
use Modules\Workers\Domain\Worker;
use PHPUnit\Framework\TestCase;

use function str_repeat;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class GetWorkerDetailQueryHandlerTest extends TestCase
{
    public function testShouldReturnFullDetailWhenOwnerAsksForOwnWorker(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $worker = Worker::create(WorkerId::generate(), $owner, 'Mac', 'worker-mac', $createdAt, 'mac.local');
        $hash = str_repeat('a', 64);
        $worker->updateFingerprint($hash, ['os' => 'macOS'], $createdAt);
        $worker->recordHeartbeat(new DateTimeImmutable('2026-11-18T15:04:00+00:00'));

        $workers->save($worker);

        $sut = new GetWorkerDetailQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $result = $sut(new GetWorkerDetailQuery($worker->id(), $owner));

        self::assertSame('Mac', $result->name);
        self::assertSame('mac.local', $result->hostLabel);
        self::assertSame($hash, $result->fingerprintHash);
        self::assertSame(str_repeat('a', 12), $result->fingerprintHashShort);
        self::assertSame('ok', $result->fingerprintStatus);
        self::assertSame(['os' => 'macOS'], $result->fingerprintMeta);
        self::assertSame([], $result->fingerprintChangeHistory);
        self::assertTrue($result->isOnline);
    }

    public function testShouldReturnChangeHistoryWithLatestDetectedAsWarning(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $worker = Worker::create(WorkerId::generate(), $owner, 'Mac', 'worker-mac', $createdAt);
        $worker->updateFingerprint(str_repeat('a', 64), ['hostname' => 'old'], $createdAt);
        $worker->recordFingerprintMismatch(str_repeat('b', 64), ['hostname' => 'new'], new DateTimeImmutable('2026-11-18T16:00:00+00:00'));

        $workers->save($worker);

        $sut = new GetWorkerDetailQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T17:00:00+00:00')));

        $result = $sut(new GetWorkerDetailQuery($worker->id(), $owner));

        self::assertSame('warning', $result->fingerprintStatus);
        self::assertCount(1, $result->fingerprintChangeHistory);
        self::assertSame('detected', $result->fingerprintChangeHistory[0]['status']);
    }

    public function testShouldThrowNotFoundWhenWorkerMissing(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());

        $sut = new GetWorkerDetailQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:00:00+00:00')));

        $this->expectException(NotFoundHttpException::class);
        $sut(new GetWorkerDetailQuery(WorkerId::generate(), $owner));
    }

    public function testShouldThrowAccessDeniedWhenForeignActorAsksForWorker(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $other = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $worker = Worker::create(WorkerId::generate(), $owner, 'Mac', 'worker-mac', $createdAt);
        $workers->save($worker);

        $sut = new GetWorkerDetailQueryHandler($workers, $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')));

        $this->expectException(AccessDeniedHttpException::class);
        $sut(new GetWorkerDetailQuery($worker->id(), $other));
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
