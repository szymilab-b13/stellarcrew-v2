<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Exception;

use Modules\Shared\Kernel\UserId;
use RuntimeException;

use function sprintf;

final class UserProfileNotFoundException extends RuntimeException
{
    public static function forId(UserId $id): self
    {
        return new self(sprintf('User profile with id "%s" not found.', (string) $id));
    }
}
