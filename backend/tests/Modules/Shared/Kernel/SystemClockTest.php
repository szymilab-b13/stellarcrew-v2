<?php

declare(strict_types=1);

namespace App\Tests\Modules\Shared\Kernel;

use DateTimeImmutable;
use Modules\Shared\Kernel\SystemClock;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    public function testShouldReturnCurrentMomentWhenCallingNow(): void
    {
        $sut = new SystemClock();

        $before = new DateTimeImmutable();
        $result = $sut->now();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $result);
        self::assertLessThanOrEqual($after, $result);
    }
}
