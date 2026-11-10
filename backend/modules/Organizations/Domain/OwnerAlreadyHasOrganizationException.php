<?php

declare(strict_types=1);

namespace Modules\Organizations\Domain;

use Modules\Shared\Kernel\UserId;
use RuntimeException;

use function sprintf;

final class OwnerAlreadyHasOrganizationException extends RuntimeException
{
    public static function forOwner(UserId $ownerUserId): self
    {
        return new self(sprintf('User "%s" already owns an organization.', (string) $ownerUserId));
    }
}
