<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Domain;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\WorkerRefreshToken;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class WorkerRefreshTokenTest extends TestCase
{
    public function testShouldIssueTokenWithExpiryWhenFactoryCalled(): void
    {
        $hash = str_repeat('a', 64);
        $workerId = WorkerId::generate();
        $now = new DateTimeImmutable('2026-11-19T12:00:00+00:00');

        $sut = WorkerRefreshToken::issue($hash, $workerId, $now, ttlSeconds: 3600);

        self::assertSame($hash, $sut->tokenHash());
        self::assertTrue($sut->workerId()->equals($workerId));
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now->modify('+3600 seconds'), $sut->expiresAt());
        self::assertFalse($sut->isConsumed());
        self::assertNull($sut->consumedAt());
    }

    public function testShouldRejectInvalidTokenHashWhenConstructed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkerRefreshToken::issue('not-a-sha256', WorkerId::generate(), new DateTimeImmutable('2026-11-19T12:00:00+00:00'));
    }

    public function testShouldMarkConsumedWhenCalled(): void
    {
        $sut = WorkerRefreshToken::issue(str_repeat('b', 64), WorkerId::generate(), new DateTimeImmutable('2026-11-19T12:00:00+00:00'));
        $consumedAt = new DateTimeImmutable('2026-11-19T12:05:00+00:00');

        $sut->markConsumed($consumedAt);

        self::assertTrue($sut->isConsumed());
        self::assertEquals($consumedAt, $sut->consumedAt());
    }

    public function testShouldRejectDoubleConsumeWhenMarkedTwice(): void
    {
        $sut = WorkerRefreshToken::issue(str_repeat('c', 64), WorkerId::generate(), new DateTimeImmutable('2026-11-19T12:00:00+00:00'));
        $sut->markConsumed(new DateTimeImmutable('2026-11-19T12:05:00+00:00'));

        $this->expectException(DomainException::class);
        $sut->markConsumed(new DateTimeImmutable('2026-11-19T12:10:00+00:00'));
    }

    public function testShouldReportExpiredWhenNowPastExpiry(): void
    {
        $sut = WorkerRefreshToken::issue(
            str_repeat('d', 64),
            WorkerId::generate(),
            new DateTimeImmutable('2026-11-19T12:00:00+00:00'),
            ttlSeconds: 60,
        );

        self::assertTrue($sut->isExpired(new DateTimeImmutable('2026-11-19T12:02:00+00:00')));
        self::assertFalse($sut->isExpired(new DateTimeImmutable('2026-11-19T12:00:30+00:00')));
    }
}
