<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Exception;

use Modules\Shared\Kernel\UserId;
use RuntimeException;

use function sprintf;

final class OwnerHasNoOrganizationException extends RuntimeException
{
    public static function forOwner(UserId $ownerId): self
    {
        return new self(sprintf(
            'User "%s" has no organization; a workspace cannot be created without one.',
            (string) $ownerId,
        ));
    }
}
