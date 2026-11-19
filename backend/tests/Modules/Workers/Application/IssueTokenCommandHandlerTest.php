<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Core\SpyEventDispatcher;
use App\Tests\Fixtures\Workers\InMemoryWorkerRefreshTokenRepository;
use App\Tests\Fixtures\Workers\InMemoryWorkerRepository;
use DateTimeImmutable;

use function hash;

use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Api\Event\WorkerFingerprintChanged;
use Modules\Workers\Application\IssueTokenCommand;
use Modules\Workers\Application\IssueTokenCommandHandler;
use Modules\Workers\Domain\RefreshTokenGenerator;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerAccessTokenEncoder;
use Modules\Workers\Domain\WorkerRefreshToken;
use PHPUnit\Framework\TestCase;

use function str_repeat;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Uuid;

final class IssueTokenCommandHandlerTest extends TestCase
{
    public function testShouldIssueAccessAndRotateRefreshWhenValid(): void
    {
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $worker = $this->createWorker($owner);
        $workers->save($worker);
        $plaintext = 'valid-plaintext-token';
        $issuedAt = new DateTimeImmutable('2026-11-19T12:00:00+00:00');
        $refreshTokens->save(WorkerRefreshToken::issue(hash('sha256', $plaintext), $worker->id(), $issuedAt));
        $exchangeAt = new DateTimeImmutable('2026-11-19T12:05:00+00:00');
        $sut = new IssueTokenCommandHandler(
            $refreshTokens,
            $workers,
            $this->deterministicGenerator('rotated-plain'),
            $this->fakeEncoder('access-jwt-xyz'),
            new SpyEventDispatcher(),
            $this->fixedClock($exchangeAt),
        );

        $result = $sut(new IssueTokenCommand(refreshToken: $plaintext));

        self::assertSame('access-jwt-xyz', $result->accessToken);
        self::assertSame('rotated-plain', $result->refreshToken);
        self::assertSame(IssueTokenCommandHandler::ACCESS_TOKEN_TTL_SECONDS, $result->expiresIn);
        self::assertTrue($refreshTokens->findByTokenHash(hash('sha256', $plaintext))?->isConsumed());
        self::assertNotNull($refreshTokens->findByTokenHash(hash('sha256', 'rotated-plain')));
    }

    public function testShouldRejectUnknownRefreshToken(): void
    {
        $sut = new IssueTokenCommandHandler(
            new InMemoryWorkerRefreshTokenRepository(),
            new InMemoryWorkerRepository(),
            $this->deterministicGenerator('unused'),
            $this->fakeEncoder('unused'),
            new SpyEventDispatcher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:00:00+00:00')),
        );

        $this->expectException(UnauthorizedHttpException::class);
        $sut(new IssueTokenCommand(refreshToken: 'no-such-token'));
    }

    public function testShouldRejectExpiredRefreshToken(): void
    {
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $workers = new InMemoryWorkerRepository();
        $worker = $this->createWorker(new UserId(Uuid::v7()));
        $workers->save($worker);
        $plaintext = 'expired-token';
        $refreshTokens->save(WorkerRefreshToken::issue(
            hash('sha256', $plaintext),
            $worker->id(),
            new DateTimeImmutable('2026-11-19T10:00:00+00:00'),
            ttlSeconds: 60,
        ));
        $sut = new IssueTokenCommandHandler(
            $refreshTokens,
            $workers,
            $this->deterministicGenerator('unused'),
            $this->fakeEncoder('unused'),
            new SpyEventDispatcher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:00:00+00:00')),
        );

        $this->expectException(UnauthorizedHttpException::class);
        $sut(new IssueTokenCommand(refreshToken: $plaintext));
    }

    public function testShouldFlagFingerprintWarningWhenMismatch(): void
    {
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $workers = new InMemoryWorkerRepository();
        $owner = new UserId(Uuid::v7());
        $worker = $this->createWorker($owner);
        $storedHash = str_repeat('a', 64);
        $worker->updateFingerprint($storedHash, ['hostname' => 'old-host'], new DateTimeImmutable('2026-11-19T11:00:00+00:00'));
        $workers->save($worker);
        $plaintext = 'fp-test';
        $refreshTokens->save(WorkerRefreshToken::issue(hash('sha256', $plaintext), $worker->id(), new DateTimeImmutable('2026-11-19T12:00:00+00:00')));
        $events = new SpyEventDispatcher();
        $sut = new IssueTokenCommandHandler(
            $refreshTokens,
            $workers,
            $this->deterministicGenerator('rotated'),
            $this->fakeEncoder('jwt'),
            $events,
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:05:00+00:00')),
        );

        $result = $sut(new IssueTokenCommand(
            refreshToken: $plaintext,
            fingerprintHash: str_repeat('b', 64),
            fingerprintMeta: ['hostname' => 'new-host'],
        ));

        self::assertTrue($result->fingerprintWarning);
        self::assertSame(['hostname' => 'old-host'], $result->previousFingerprintMeta);
        self::assertSame(['hostname' => 'new-host'], $workers->findById($worker->id())?->fingerprintMeta());
        self::assertSame($storedHash, $workers->findById($worker->id())?->fingerprintHash());
        self::assertCount(1, $events->dispatched);
        self::assertInstanceOf(WorkerFingerprintChanged::class, $events->dispatched[0]);
    }

    public function testShouldNotFlagWarningWhenFingerprintMatches(): void
    {
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $workers = new InMemoryWorkerRepository();
        $worker = $this->createWorker(new UserId(Uuid::v7()));
        $storedHash = str_repeat('a', 64);
        $worker->updateFingerprint($storedHash, ['hostname' => 'host'], new DateTimeImmutable('2026-11-19T11:00:00+00:00'));
        $workers->save($worker);
        $plaintext = 'match-test';
        $refreshTokens->save(WorkerRefreshToken::issue(hash('sha256', $plaintext), $worker->id(), new DateTimeImmutable('2026-11-19T12:00:00+00:00')));
        $sut = new IssueTokenCommandHandler(
            $refreshTokens,
            $workers,
            $this->deterministicGenerator('rotated'),
            $this->fakeEncoder('jwt'),
            new SpyEventDispatcher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:05:00+00:00')),
        );

        $result = $sut(new IssueTokenCommand(
            refreshToken: $plaintext,
            fingerprintHash: $storedHash,
            fingerprintMeta: ['hostname' => 'host'],
        ));

        self::assertFalse($result->fingerprintWarning);
        self::assertNull($result->previousFingerprintMeta);
    }

    public function testShouldRejectConsumedRefreshToken(): void
    {
        $refreshTokens = new InMemoryWorkerRefreshTokenRepository();
        $workers = new InMemoryWorkerRepository();
        $worker = $this->createWorker(new UserId(Uuid::v7()));
        $workers->save($worker);
        $plaintext = 'consumed-token';
        $token = WorkerRefreshToken::issue(hash('sha256', $plaintext), $worker->id(), new DateTimeImmutable('2026-11-19T12:00:00+00:00'));
        $token->markConsumed(new DateTimeImmutable('2026-11-19T12:05:00+00:00'));

        $refreshTokens->save($token);
        $sut = new IssueTokenCommandHandler(
            $refreshTokens,
            $workers,
            $this->deterministicGenerator('unused'),
            $this->fakeEncoder('unused'),
            new SpyEventDispatcher(),
            $this->fixedClock(new DateTimeImmutable('2026-11-19T12:10:00+00:00')),
        );

        $this->expectException(UnauthorizedHttpException::class);
        $sut(new IssueTokenCommand(refreshToken: $plaintext));
    }

    private function createWorker(UserId $owner): Worker
    {
        return Worker::create(
            WorkerId::generate(),
            $owner,
            'Test Worker',
            'worker-test-01',
            'secret-hash',
            new DateTimeImmutable('2026-11-19T11:00:00+00:00'),
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

    private function fakeEncoder(string $jwt): WorkerAccessTokenEncoder
    {
        return new readonly class ($jwt) implements WorkerAccessTokenEncoder {
            public function __construct(private string $jwt)
            {
            }

            public function encode(
                WorkerId $workerId,
                UserId $ownerUserId,
                DateTimeImmutable $issuedAt,
                int $ttlSeconds,
            ): string {
                return $this->jwt;
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
