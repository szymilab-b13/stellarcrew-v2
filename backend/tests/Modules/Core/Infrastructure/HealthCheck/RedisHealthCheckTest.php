<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\HealthCheck;

use Modules\Core\Infrastructure\HealthCheck\RedisHealthCheck;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisException;

final class RedisHealthCheckTest extends TestCase
{
    public function testShouldReturnOkWhenRedisPingResponds(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('ping')->willReturn(true);
        $sut = new RedisHealthCheck($redis);

        $result = $sut->check();

        self::assertTrue($result->isOk());
        self::assertSame('redis', $result->name);
    }

    public function testShouldReturnFailWithReasonWhenRedisPingThrows(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('ping')->willThrowException(new RedisException('connection refused'));
        $sut = new RedisHealthCheck($redis);

        $result = $sut->check();

        self::assertFalse($result->isOk());
        self::assertSame('redis', $result->name);
        self::assertSame('connection refused', $result->reason);
    }

    public function testShouldReturnFailWhenRedisPingReturnsFalse(): void
    {
        $redis = $this->createMock(Redis::class);
        $redis->method('ping')->willReturn(false);
        $sut = new RedisHealthCheck($redis);

        $result = $sut->check();

        self::assertFalse($result->isOk());
        self::assertSame('redis', $result->name);
        self::assertSame('ping returned false', $result->reason);
    }
}
