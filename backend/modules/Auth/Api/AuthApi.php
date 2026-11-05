<?php

declare(strict_types=1);

namespace Modules\Auth\Api;

use Modules\Auth\Api\Dto\AuthUser;

interface AuthApi
{
    public function createAdmin(string $email, string $plaintextPassword): AuthUser;
}
