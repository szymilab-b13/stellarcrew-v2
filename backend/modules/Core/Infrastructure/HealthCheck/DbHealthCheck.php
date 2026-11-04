<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\HealthCheck;

use Doctrine\DBAL\Connection;
use Modules\Core\Api\Dto\HealthResult;
use Modules\Core\Api\HealthCheckInterface;
use Throwable;

final readonly class DbHealthCheck implements HealthCheckInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function name(): string
    {
        return 'db';
    }

    public function check(): HealthResult
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return HealthResult::ok($this->name());
        } catch (Throwable $throwable) {
            return HealthResult::fail($this->name(), $throwable->getMessage());
        }
    }
}
