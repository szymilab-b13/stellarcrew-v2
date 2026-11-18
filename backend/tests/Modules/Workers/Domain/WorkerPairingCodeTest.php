<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Domain;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;
use Modules\Workers\Domain\WorkerPairingCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkerPairingCodeTest extends TestCase
{
    public function testShouldIssueCodeWithTtlWhenCalled(): void
    {
        $owner = new UserId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-18T15:00:00+00:00');

        $sut = WorkerPairingCode::issue('ABCD-2345', $owner, $now, 600);

        self::assertSame('ABCD-2345', $sut->code());
        self::assertTrue($sut->ownerUserId()->equals($owner));
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now->modify('+600 seconds'), $sut->expiresAt());
        self::assertFalse($sut->isClaimed());
        self::assertFalse($sut->isExpired($now));
    }

    public function testShouldRejectInvalidCodeFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkerPairingCode::issue('abcd-1234', new UserId(Uuid::v7()), new DateTimeImmutable('2026-11-18T15:00:00+00:00'));
    }

    public function testShouldRejectCodeWithAmbiguousChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WorkerPairingCode::issue('A0BC-D1E2', new UserId(Uuid::v7()), new DateTimeImmutable('2026-11-18T15:00:00+00:00'));
    }

    public function testShouldClaimCodeAndMarkClaimedAtWhenValid(): void
    {
        $sut = $this->makeCode();
        $claimAt = new DateTimeImmutable('2026-11-18T15:05:00+00:00');

        $sut->claim($claimAt);

        self::assertTrue($sut->isClaimed());
        self::assertEquals($claimAt, $sut->claimedAt());
    }

    public function testShouldRejectClaimWhenAlreadyClaimed(): void
    {
        $sut = $this->makeCode();
        $sut->claim(new DateTimeImmutable('2026-11-18T15:05:00+00:00'));

        $this->expectException(DomainException::class);
        $sut->claim(new DateTimeImmutable('2026-11-18T15:06:00+00:00'));
    }

    public function testShouldRejectClaimWhenExpired(): void
    {
        $sut = $this->makeCode();
        $afterExpiry = new DateTimeImmutable('2026-11-18T15:20:00+00:00');

        $this->expectException(DomainException::class);
        $sut->claim($afterExpiry);
    }

    public function testShouldReportExpiredAfterTtl(): void
    {
        $sut = $this->makeCode();

        self::assertFalse($sut->isExpired(new DateTimeImmutable('2026-11-18T15:09:59+00:00')));
        self::assertTrue($sut->isExpired(new DateTimeImmutable('2026-11-18T15:10:01+00:00')));
    }

    private function makeCode(): WorkerPairingCode
    {
        return WorkerPairingCode::issue(
            'ABCD-2345',
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-18T15:00:00+00:00'),
            600,
        );
    }
}
