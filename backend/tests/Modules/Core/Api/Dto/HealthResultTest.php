<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Api\Dto;

use Modules\Core\Api\Dto\HealthResult;
use PHPUnit\Framework\TestCase;

final class HealthResultTest extends TestCase
{
    public function testShouldBuildOkResultWhenFactoryOkCalled(): void
    {
        $sut = HealthResult::ok('db');

        self::assertTrue($sut->isOk());
        self::assertSame('db', $sut->name);
        self::assertNull($sut->reason);
    }

    public function testShouldBuildFailResultWhenFactoryFailCalled(): void
    {
        $sut = HealthResult::fail('redis', 'connection refused');

        self::assertFalse($sut->isOk());
        self::assertSame('redis', $sut->name);
        self::assertSame('connection refused', $sut->reason);
    }
}
