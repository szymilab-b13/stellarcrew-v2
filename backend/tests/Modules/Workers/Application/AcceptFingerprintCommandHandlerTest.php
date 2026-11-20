<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Application\AcceptFingerprintCommand;
use Modules\Workers\Application\AcceptFingerprintCommandHandler;
use Modules\Workers\Domain\Worker;
use PHPUnit\Framework\TestCase;

use function str_repeat;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class AcceptFingerprintCommandHandlerTest extends TestCase
{
    public function testShouldUpdateFingerprintWhenActorIsOwner(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $worker = $this->createWorker($owner);
        $oldHash = str_repeat('a', 64);
        $worker->updateFingerprint($oldHash, ['hostname' => 'old'], new DateTimeImmutable('2026-11-19T11:00:00+00:00'));
        $workers->save($worker);
        $newHash = str_repeat('b', 64);
        $acceptAt = new DateTimeImmutable('2026-11-19T12:00:00+00:00');
        $sut = new AcceptFingerprintCommandHandler($workers, $this->fixedClock($acceptAt));

        $sut(new AcceptFingerprintCommand(
            workerId: $worker->id(),
            actorId: $owner,
            fingerprintHash: $newHash,
            fingerprintMeta: ['hostname' => 'new'],
        ));

        $updated = $workers->findById($worker->id());
        self::assertNotNull($updated);
        self::assertSame($newHash, $updated->fingerprintHash());
        self::assertSame(['hostname' => 'new'], $updated->fingerprintMeta());

        $history = $updated->fingerprintChangeHistory();
        self::assertCount(1, $history);
        self::assertSame('accepted', $history[0]['status']);
        self::assertSame($oldHash, $history[0]['previousHash']);
        self::assertSame($newHash, $history[0]['newHash']);
        self::assertSame($acceptAt->format(DateTimeImmutable::ATOM), $history[0]['at']);
    }

    public function testShouldReject404WhenWorkerUnknown(): void
    {
        $sut = new AcceptFingerprintCommandHandler(
            new InMemoryWorkerRepository(),
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:00:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);
        $sut(new AcceptFingerprintCommand(
            workerId: WorkerId::generate(),
            actorId: new UserId(Uuid::v7()),
            fingerprintHash: str_repeat('c', 64),
        ));
    }

    public function testShouldReject403WhenActorNotOwner(): void
    {
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $worker = $this->createWorker($owner);
        $workers->save($worker);
        $sut = new AcceptFingerprintCommandHandler(
            $workers,
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:00:00+00:00')),
        );

        $this->expectException(AccessDeniedHttpException::class);
        $sut(new AcceptFingerprintCommand(
            workerId: $worker->id(),
            actorId: new UserId(Uuid::v7()),
            fingerprintHash: str_repeat('d', 64),
        ));
    }

    private function createWorker(UserId $owner): Worker
    {
        return Worker::create(
            WorkerId::generate(),
            $owner,
            'Worker',
            'worker-x-01',
            new DateTimeImmutable('2026-11-19T11:00:00+00:00'),
        );
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
