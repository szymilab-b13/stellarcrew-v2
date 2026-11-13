<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Plans;

use Modules\Shared\Api\LimitType;
use Modules\Shared\Api\PlanLimitsService;
use Modules\Shared\Kernel\OrganizationId;

final readonly class ConfigBackedPlanLimitsService implements PlanLimitsService
{
    /**
     * @param array<string, int> $limits map LimitType->value => max count
     */
    public function __construct(private array $limits)
    {
    }

    public function exceeded(LimitType $type, OrganizationId $organizationId, int $currentUsage): bool
    {
        return $currentUsage >= $this->maxFor($type, $organizationId);
    }

    public function maxFor(LimitType $type, OrganizationId $organizationId): int
    {
        return $this->limits[$type->value] ?? PHP_INT_MAX;
    }
}
