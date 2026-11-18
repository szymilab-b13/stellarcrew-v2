<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerPairingCodeRepository;
use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use DomainException;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Workers\Application\ClaimPairingCodeCommand;
use Modules\Workers\Application\ClaimPairingCodeCommandHandler;
use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Infrastructure\Support\WorkerSecretTokenHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class ClaimPairingCodeCommandHandlerTest extends TestCase
{
    public function testShouldCreateWorkerAndReturnCredentialsWhenCodeValid(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $hasher = new WorkerSecretTokenHasher();
        $owner = new UserId(Uuid::v7());
        $issuedAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, $issuedAt));
        $claimAt = new DateTimeImmutable('2026-11-18T15:05:00+00:00');
        $sut = new ClaimPairingCodeCommandHandler($codes, $workers, $hasher, $this->fixedClock($claimAt));

        $result = $sut(new ClaimPairingCodeCommand(
            code: 'ABCD-2345',
            name: 'MacBook Pro',
            hostLabel: 'macbook.local',
        ));

        $worker = $workers->findById($result->workerId);
        self::assertNotNull($worker);
        self::assertSame('MacBook Pro', $worker->name());
        self::assertSame('macbook.local', $worker->hostLabel());
        self::assertTrue($worker->ownerUserId()->equals($owner));
        self::assertSame($result->rabbitmqUser, $worker->rabbitmqUser());
        self::assertMatchesRegularExpression('/^worker-[0-9a-f]{32}$/', $result->rabbitmqUser);
        self::assertTrue($hasher->verify($result->plaintextSecret, $worker->secretTokenHash()));
        self::assertTrue($codes->findByCode('ABCD-2345')?->isClaimed());
    }

    public function testShouldThrow404WhenCodeDoesNotExist(): void
    {
        $sut = new ClaimPairingCodeCommandHandler(
            new InMemoryWorkerPairingCodeRepository(),
            new InMemoryWorkerRepository(),
            new WorkerSecretTokenHasher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T15:00:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ZZZZ-9999', name: 'M'));
    }

    public function testShouldRejectReuseOfAlreadyClaimedCode(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $issuedAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, $issuedAt));
        $hasher = new WorkerSecretTokenHasher();
        $sut = new ClaimPairingCodeCommandHandler(
            $codes,
            new InMemoryWorkerRepository(),
            $hasher,
            $this->fixedClock(new DateTimeImmutable('2026-11-18T15:05:00+00:00')),
        );
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M'));

        $this->expectException(DomainException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M2'));
    }

    public function testShouldRejectExpiredCode(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $issuedAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, $issuedAt));
        $sut = new ClaimPairingCodeCommandHandler(
            $codes,
            new InMemoryWorkerRepository(),
            new WorkerSecretTokenHasher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-18T15:20:00+00:00')),
        );

        $this->expectException(DomainException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M'));
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
