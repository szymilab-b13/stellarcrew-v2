<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\HealthCheck;

use Modules\Core\Api\Dto\HealthResult;
use Modules\Core\Api\HealthCheckInterface;
use Throwable;

final readonly class RabbitHealthCheck implements HealthCheckInterface
{
    public function __construct(private RabbitConnectionProbe $probe)
    {
    }

    public function name(): string
    {
        return 'rabbitmq';
    }

    public function check(): HealthResult
    {
        try {
            $this->probe->ping();

            return HealthResult::ok($this->name());
        } catch (Throwable $throwable) {
            return HealthResult::fail($this->name(), $throwable->getMessage());
        }
    }
}
