<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Agents;

use Modules\Shared\Api\LimitType;
use Modules\Shared\Api\PlanLimitsService;
use Modules\Shared\Kernel\OrganizationId;

final class FakePlanLimitsService implements PlanLimitsService
{
    /**
     * @param array<string, int> $limits
     */
    public function __construct(private array $limits = [])
    {
    }

    public function withLimit(LimitType $type, int $max): self
    {
        $this->limits[$type->value] = $max;

        return $this;
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
