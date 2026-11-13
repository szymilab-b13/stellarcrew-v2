<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use Modules\Shared\Kernel\OrganizationId;
use RuntimeException;

use function sprintf;

final class AgentSlugAlreadyTakenException extends RuntimeException
{
    public static function forOrganization(OrganizationId $organizationId, string $slug): self
    {
        return new self(sprintf(
            'Agent slug "%s" is already taken in organization "%s".',
            $slug,
            (string) $organizationId,
        ));
    }
}
