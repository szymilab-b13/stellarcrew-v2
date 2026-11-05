<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

final readonly class InstallSystemCommand
{
    public function __construct(
        public string $adminEmail,
        public string $adminPlaintextPassword,
    ) {
    }
}
