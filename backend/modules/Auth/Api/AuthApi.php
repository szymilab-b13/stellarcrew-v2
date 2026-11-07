<?php

declare(strict_types=1);

namespace Modules\Auth\Api;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Auth\Api\Exception\UserNotFoundException;
use Modules\Shared\Kernel\UserId;

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

    /**
     * @throws UserNotFoundException
     */
    public function markDeactivated(UserId $userId): void;

    /**
     * @throws UserNotFoundException
     */
    public function markReactivated(UserId $userId): void;
}
