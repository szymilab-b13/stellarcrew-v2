<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

interface PasswordHasher
{
    public function hash(string $plaintext): string;
}
