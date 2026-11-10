<?php

declare(strict_types=1);

namespace Modules\Organizations\Domain;

use Modules\Shared\Kernel\OrganizationId;
use RuntimeException;

use function sprintf;

final class OrganizationNotFoundException extends RuntimeException
{
    public static function byId(OrganizationId $id): self
    {
        return new self(sprintf('Organization "%s" not found.', (string) $id));
    }
}
