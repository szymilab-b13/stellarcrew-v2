<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerPairingCodeRepository;
use App\Tests\Fixtures\Workers\InMemoryWorkerRefreshTokenRepository;
use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use DomainException;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Workers\Application\ClaimPairingCodeCommand;
use Modules\Workers\Application\ClaimPairingCodeCommandHandler;
use Modules\Workers\Domain\RefreshTokenGenerator;
use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Infrastructure\Support\BcryptWorkerSecretTokenHasher;
use PHPUnit\Framework\TestCase;

use function hash;
use function str_repeat;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class ClaimPairingCodeCommandHandlerTest extends TestCase
{
    public function testShouldCreateWorkerAndReturnCredentialsWhenCodeValid(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $hasher = new BcryptWorkerSecretTokenHasher();
        $owner = new UserId(Uuid::v7());
        $issuedAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, $issuedAt));
        $claimAt = new DateTimeImmutable('2026-11-18T15:05:00+00:00');
        $sut = $this->makeHandler($codes, $workers, $refreshTokens, $hasher, $claimAt);

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
        self::assertSame('refresh-plain', $result->refreshToken);
        self::assertNotNull($refreshTokens->findByTokenHash(hash('sha256', 'refresh-plain')));
    }

    public function testShouldThrow404WhenCodeDoesNotExist(): void
    {
        $sut = $this->makeHandler(
            new InMemoryWorkerPairingCodeRepository(),
            new InMemoryWorkerRepository(),
            new InMemoryWorkerRefreshTokenRepository(),
            new BcryptWorkerSecretTokenHasher(),
            new DateTimeImmutable('2026-11-18T15:00:00+00:00'),
        );

        $this->expectException(NotFoundHttpException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ZZZZ-9999', name: 'M'));
    }

    public function testShouldRejectReuseOfAlreadyClaimedCode(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, new DateTimeImmutable('2026-11-18T15:00:00+00:00')));
        $sut = $this->makeHandler(
            $codes,
            new InMemoryWorkerRepository(),
            new InMemoryWorkerRefreshTokenRepository(),
            new BcryptWorkerSecretTokenHasher(),
            new DateTimeImmutable('2026-11-18T15:05:00+00:00'),
        );
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M'));

        $this->expectException(DomainException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M2'));
    }

    public function testShouldPersistFingerprintWhenProvided(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, new DateTimeImmutable('2026-11-18T15:00:00+00:00')));
        $sut = $this->makeHandler(
            $codes,
            $workers,
            new InMemoryWorkerRefreshTokenRepository(),
            new BcryptWorkerSecretTokenHasher(),
            new DateTimeImmutable('2026-11-18T15:05:00+00:00'),
        );
        $hash = str_repeat('f', 64);
        $meta = ['hostname' => 'macbook', 'machineId' => 'id-1', 'cpuModel' => 'Apple M2'];

        $result = $sut(new ClaimPairingCodeCommand(
            code: 'ABCD-2345',
            name: 'M',
            fingerprintHash: $hash,
            fingerprintMeta: $meta,
        ));

        $worker = $workers->findById($result->workerId);
        self::assertNotNull($worker);
        self::assertSame($hash, $worker->fingerprintHash());
        self::assertSame($meta, $worker->fingerprintMeta());
    }

    public function testShouldRejectExpiredCode(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, new DateTimeImmutable('2026-11-18T15:00:00+00:00')));
        $sut = $this->makeHandler(
            $codes,
            new InMemoryWorkerRepository(),
            new InMemoryWorkerRefreshTokenRepository(),
            new BcryptWorkerSecretTokenHasher(),
            new DateTimeImmutable('2026-11-18T15:20:00+00:00'),
        );

        $this->expectException(DomainException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M'));
    }

    private function makeHandler(
        InMemoryWorkerPairingCodeRepository $codes,
        InMemoryWorkerRepository $workers,
        InMemoryWorkerRefreshTokenRepository $refreshTokens,
        BcryptWorkerSecretTokenHasher $hasher,
        DateTimeImmutable $now,
    ): ClaimPairingCodeCommandHandler {
        return new ClaimPairingCodeCommandHandler(
            $codes,
            $workers,
            $refreshTokens,
            $hasher,
            $this->deterministicGenerator('refresh-plain'),
            $this->fixedClock($now),
        );
    }

    private function deterministicGenerator(string $plaintext): RefreshTokenGenerator
    {
        return new readonly class ($plaintext) implements RefreshTokenGenerator {
            public function __construct(private string $plaintext)
            {
            }

            public function generate(): string
            {
                return $this->plaintext;
            }

            public function hash(string $plaintext): string
            {
                return hash('sha256', $plaintext);
            }
        };
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
