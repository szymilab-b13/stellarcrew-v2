<?php

declare(strict_types=1);

namespace Modules\Core\Api;

use Modules\Core\Api\Dto\HealthResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('core.health_check')]
interface HealthCheckInterface
{
    public function name(): string;

    public function check(): HealthResult;
}
