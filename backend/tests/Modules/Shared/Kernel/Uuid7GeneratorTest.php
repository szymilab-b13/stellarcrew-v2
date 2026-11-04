<?php

declare(strict_types=1);

namespace App\Tests\Modules\Shared\Kernel;

use DateTimeImmutable;
use Modules\Shared\Kernel\Uuid7Generator;
use PHPUnit\Framework\TestCase;

final class Uuid7GeneratorTest extends TestCase
{
    public function testShouldEmbedCurrentTimestampWhenCallingGenerate(): void
    {
        $sut = new Uuid7Generator();

        $before = new DateTimeImmutable();
        $result = $sut->generate();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $result->getDateTime()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $result->getDateTime()->getTimestamp());
    }

    public function testShouldReturnDistinctUuidsOnConsecutiveCalls(): void
    {
        $sut = new Uuid7Generator();

        $first = $sut->generate();
        $second = $sut->generate();

        self::assertFalse($first->equals($second));
    }
}
