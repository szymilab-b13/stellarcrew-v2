<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\HealthCheck;

use Doctrine\DBAL\Connection;
use Modules\Core\Infrastructure\HealthCheck\DbHealthCheck;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DbHealthCheckTest extends TestCase
{
    public function testShouldReturnOkWhenConnectionExecutesSelectOne(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->with('SELECT 1');
        $sut = new DbHealthCheck($connection);

        $result = $sut->check();

        self::assertTrue($result->isOk());
        self::assertSame('db', $result->name);
    }

    public function testShouldReturnFailWithReasonWhenConnectionThrows(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')
            ->willThrowException(new RuntimeException('connection refused'));
        $sut = new DbHealthCheck($connection);

        $result = $sut->check();

        self::assertFalse($result->isOk());
        self::assertSame('db', $result->name);
        self::assertSame('connection refused', $result->reason);
    }
}
