<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Core\SpyEventDispatcher;
use App\Tests\Fixtures\Workers\InMemoryWorkerPairingCodeRepository;
use App\Tests\Fixtures\Workers\InMemoryWorkerRefreshTokenRepository;
use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;
use DomainException;

use function hash;

use LogicException;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Workers\Api\Event\WorkerPaired;
use Modules\Workers\Application\ClaimPairingCodeCommand;
use Modules\Workers\Application\ClaimPairingCodeCommandHandler;
use Modules\Workers\Domain\RefreshTokenGenerator;
use Modules\Workers\Domain\WorkerPairingCode;
use PHPUnit\Framework\TestCase;

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
        $owner = new UserId(Uuid::v7());
        $issuedAt = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, $issuedAt));
        $claimAt = new DateTimeImmutable('2026-11-18T15:05:00+00:00');
        $sut = $this->makeHandler($codes, $workers, $refreshTokens, $claimAt);

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
        self::assertSame('amqp-pass', $result->plaintextSecret);
        self::assertNotSame($result->plaintextSecret, $result->refreshToken);
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
            new DateTimeImmutable('2026-11-18T15:05:00+00:00'),
        );
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M'));

        $this->expectException(DomainException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M2'));
    }

    public function testShouldGenerateAutoNameWhenNamesIsNull(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('WXYZ-4567', $owner, new DateTimeImmutable('2026-11-21T14:00:00+00:00')));
        $sut = $this->makeHandler(
            $codes,
            $workers,
            new InMemoryWorkerRefreshTokenRepository(),
            new DateTimeImmutable('2026-11-21T14:05:00+00:00'),
        );

        $result = $sut(new ClaimPairingCodeCommand(code: 'WXYZ-4567'));

        $worker = $workers->findById($result->workerId);
        self::assertNotNull($worker);
        self::assertMatchesRegularExpression('/^worker-[0-9a-f]{6}$/', $worker->name());
    }

    public function testShouldGenerateAutoNameWhenNameIsBlank(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('XYZZ-5678', $owner, new DateTimeImmutable('2026-11-21T14:00:00+00:00')));
        $sut = $this->makeHandler(
            $codes,
            $workers,
            new InMemoryWorkerRefreshTokenRepository(),
            new DateTimeImmutable('2026-11-21T14:05:00+00:00'),
        );

        $result = $sut(new ClaimPairingCodeCommand(code: 'XYZZ-5678', name: '   '));

        $worker = $workers->findById($result->workerId);
        self::assertNotNull($worker);
        self::assertMatchesRegularExpression('/^worker-[0-9a-f]{6}$/', $worker->name());
    }

    public function testShouldPersistHostInfoWhenProvided(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('BCDE-3456', $owner, new DateTimeImmutable('2026-11-21T13:00:00+00:00')));
        $sut = $this->makeHandler(
            $codes,
            $workers,
            new InMemoryWorkerRefreshTokenRepository(),
            new DateTimeImmutable('2026-11-21T13:05:00+00:00'),
        );
        $hostInfo = ['os' => 'Linux', 'osRelease' => '6.1.0', 'cpuModel' => 'AMD Ryzen 9', 'cpuCount' => 16, 'memoryBytes' => 32 * 1024 * 1024 * 1024];

        $result = $sut(new ClaimPairingCodeCommand(
            code: 'BCDE-3456',
            name: 'Prod',
            hostInfo: $hostInfo,
        ));

        $worker = $workers->findById($result->workerId);
        self::assertNotNull($worker);
        self::assertSame($hostInfo, $worker->hostInfo());
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

    public function testShouldDispatchWorkerPairedWithRabbitMqCredentials(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $workers = new InMemoryWorkerRepository();
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('ABCD-2345', $owner, new DateTimeImmutable('2026-11-23T10:00:00+00:00')));
        $events = new SpyEventDispatcher();
        $sut = $this->makeHandler(
            $codes,
            $workers,
            $refreshTokens,
            new DateTimeImmutable('2026-11-23T10:05:00+00:00'),
            $events,
        );

        $result = $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'Host'));

        $paired = null;
        foreach ($events->dispatched as $dispatched) {
            if ($dispatched instanceof WorkerPaired) {
                $paired = $dispatched;
            }
        }

        self::assertInstanceOf(WorkerPaired::class, $paired);
        self::assertTrue($paired->workerId->equals($result->workerId));
        self::assertTrue($paired->ownerUserId->equals($owner));
        self::assertSame($result->rabbitmqUser, $paired->rabbitmqUser);
        self::assertSame('amqp-pass', $paired->rabbitmqPassword);
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
            new DateTimeImmutable('2026-11-18T15:20:00+00:00'),
        );

        $this->expectException(DomainException::class);
        $sut(new ClaimPairingCodeCommand(code: 'ABCD-2345', name: 'M'));
    }

    private function makeHandler(
        InMemoryWorkerPairingCodeRepository $codes,
        InMemoryWorkerRepository $workers,
        InMemoryWorkerRefreshTokenRepository $refreshTokens,
        DateTimeImmutable $now,
        ?SpyEventDispatcher $events = null,
    ): ClaimPairingCodeCommandHandler {
        return new ClaimPairingCodeCommandHandler(
            $codes,
            $workers,
            $refreshTokens,
            $this->sequencedGenerator(['amqp-pass', 'refresh-plain']),
            $this->fixedClock($now),
            $events ?? new SpyEventDispatcher(),
        );
    }

    /**
     * @param list<string> $plaintexts
     */
    private function sequencedGenerator(array $plaintexts): RefreshTokenGenerator
    {
        return new class ($plaintexts) implements RefreshTokenGenerator {
            /**
             * @param list<string> $plaintexts
             */
            public function __construct(private array $plaintexts)
            {
            }

            public function generate(): string
            {
                $next = array_shift($this->plaintexts);
                if ($next === null) {
                    throw new LogicException('sequencedGenerator exhausted');
                }

                return $next;
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
