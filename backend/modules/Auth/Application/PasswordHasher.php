<?php

declare(strict_types=1);

namespace Modules\Auth\Application;

interface PasswordHasher
{
    public function hash(string $plaintext): string;
}
