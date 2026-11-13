<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use Modules\Shared\Kernel\OrganizationId;
use RuntimeException;

use function sprintf;

final class AgentLimitReachedException extends RuntimeException
{
    public static function forOrganization(OrganizationId $organizationId, int $max): self
    {
        return new self(sprintf(
            'Organization "%s" has reached its agent limit of %d.',
            (string) $organizationId,
            $max,
        ));
    }
}
