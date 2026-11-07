<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Exception;

use RuntimeException;

use function sprintf;

final class PasswordTooShortException extends RuntimeException
{
    public static function minimumLength(int $minLength): self
    {
        return new self(sprintf('Password must be at least %d characters long.', $minLength));
    }
}
