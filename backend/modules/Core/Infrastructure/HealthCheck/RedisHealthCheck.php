<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\HealthCheck;

use Modules\Core\Api\Dto\HealthResult;
use Modules\Core\Api\HealthCheckInterface;
use Redis;
use Throwable;

final readonly class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(private Redis $redis)
    {
    }

    public function name(): string
    {
        return 'redis';
    }

    public function check(): HealthResult
    {
        try {
            $response = $this->redis->ping();

            if ($response === false) {
                return HealthResult::fail($this->name(), 'ping returned false');
            }

            return HealthResult::ok($this->name());
        } catch (Throwable $throwable) {
            return HealthResult::fail($this->name(), $throwable->getMessage());
        }
    }
}
