<?php

declare(strict_types=1);

namespace Modules\Auth\Api\Exception;

use Modules\Shared\Kernel\UserId;
use RuntimeException;

use function sprintf;

final class UserNotFoundException extends RuntimeException
{
    public static function forId(UserId $id): self
    {
        return new self(sprintf('Auth user with id "%s" not found.', (string) $id));
    }
}
