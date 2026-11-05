<?php

declare(strict_types=1);

namespace App\Tests\Modules\Shared\Kernel;

use DateInterval;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use PHPUnit\Framework\TestCase;

final class FrozenClockTest extends TestCase
{
    public function testShouldReturnFrozenMomentWhenCallingNow(): void
    {
        $frozen = new DateTimeImmutable('2026-01-01 12:00:00');
        $sut = new FrozenClock($frozen);

        $first = $sut->now();
        usleep(1000);
        $second = $sut->now();

        self::assertEquals($frozen, $first);
        self::assertEquals($frozen, $second);
    }

    public function testShouldMoveForwardInTimeWhenCallingAdvance(): void
    {
        $sut = new FrozenClock(new DateTimeImmutable('2026-01-01 12:00:00'));

        $sut->advance(new DateInterval('PT1H'));

        self::assertEquals(new DateTimeImmutable('2026-01-01 13:00:00'), $sut->now());
    }

    public function testShouldNotMutateProvidedDateTimeWhenAdvancing(): void
    {
        $original = new DateTimeImmutable('2026-01-01 12:00:00');
        $sut = new FrozenClock($original);

        $sut->advance(new DateInterval('PT1H'));

        self::assertEquals(new DateTimeImmutable('2026-01-01 12:00:00'), $original);
    }
}
