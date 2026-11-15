<?php

declare(strict_types=1);

namespace Modules\Shared\Api;

use Modules\Shared\Kernel\OrganizationId;

interface PlanLimitsService
{
    public function exceeded(LimitType $type, OrganizationId $organizationId, int $currentUsage): bool;

    public function maxFor(LimitType $type, OrganizationId $organizationId): int;
}
