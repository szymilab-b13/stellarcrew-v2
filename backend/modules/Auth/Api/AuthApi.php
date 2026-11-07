<?php

declare(strict_types=1);

namespace Modules\Auth\Api;

use Modules\Auth\Api\Dto\AuthUser;

interface AuthApi
{
    public function createAdmin(
        string $email,
        string $plaintextPassword,
        ?string $displayName = null,
    ): AuthUser;

    public function createUser(
        string $email,
        string $plaintextPassword,
        bool $isSuperAdmin = false,
        ?string $displayName = null,
    ): AuthUser;
}
