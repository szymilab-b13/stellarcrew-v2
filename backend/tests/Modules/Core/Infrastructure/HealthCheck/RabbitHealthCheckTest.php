<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\HealthCheck;

use Modules\Core\Infrastructure\HealthCheck\RabbitConnectionProbe;
use Modules\Core\Infrastructure\HealthCheck\RabbitHealthCheck;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RabbitHealthCheckTest extends TestCase
{
    public function testShouldReturnOkWhenProbeSucceeds(): void
    {
        $probe = $this->createMock(RabbitConnectionProbe::class);
        $probe->expects(self::once())->method('ping');
        $sut = new RabbitHealthCheck($probe);

        $result = $sut->check();

        self::assertTrue($result->isOk());
        self::assertSame('rabbitmq', $result->name);
    }

    public function testShouldReturnFailWithReasonWhenProbeThrows(): void
    {
        $probe = $this->createMock(RabbitConnectionProbe::class);
        $probe->method('ping')->willThrowException(new RuntimeException('connection refused'));
        $sut = new RabbitHealthCheck($probe);

        $result = $sut->check();

        self::assertFalse($result->isOk());
        self::assertSame('rabbitmq', $result->name);
        self::assertSame('connection refused', $result->reason);
    }
}
