<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\HealthCheck;

use RuntimeException;

interface RabbitConnectionProbe
{
    /**
     * @throws RuntimeException when the broker cannot be reached
     */
    public function ping(): void;
}
