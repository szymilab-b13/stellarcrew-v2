<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

use RuntimeException;

final class SystemAlreadyInstalledException extends RuntimeException
{
    public static function create(): self
    {
        return new self('System is already installed.');
    }
}
