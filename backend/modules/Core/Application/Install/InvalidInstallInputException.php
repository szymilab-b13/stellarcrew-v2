<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

use InvalidArgumentException;

use function sprintf;

final class InvalidInstallInputException extends InvalidArgumentException
{
    public static function invalidEmail(string $email): self
    {
        return new self(sprintf('Invalid admin email address "%s".', $email));
    }

    public static function passwordTooShort(int $minLength): self
    {
        return new self(sprintf('Admin password must be at least %d characters long.', $minLength));
    }
}
