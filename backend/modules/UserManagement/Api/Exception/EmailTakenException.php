<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Exception;

use RuntimeException;

use function sprintf;

final class EmailTakenException extends RuntimeException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('Email "%s" is already registered.', $email));
    }
}
