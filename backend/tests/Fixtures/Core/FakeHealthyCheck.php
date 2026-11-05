<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use Modules\Core\Api\Dto\HealthResult;
use Modules\Core\Api\HealthCheckInterface;

final readonly class FakeHealthyCheck implements HealthCheckInterface
{
    public function __construct(private string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function check(): HealthResult
    {
        return HealthResult::ok($this->name);
    }
}
