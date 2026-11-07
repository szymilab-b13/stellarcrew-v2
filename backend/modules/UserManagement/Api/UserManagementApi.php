<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\Exception\EmailTakenException;

interface UserManagementApi
{
    public function createProfileForUser(UserId $userId, string $email, ?string $displayName = null): UserProfileDto;

    public function getProfile(UserId $userId): ?UserProfileDto;

    /**
     * Creates a brand-new user (Auth entry + UserProfile + audit log).
     *
     * @throws EmailTakenException
     */
    public function createUser(
        string $email,
        string $plaintextPassword,
        bool $isSuperAdmin = false,
        ?string $displayName = null,
    ): UserProfileDto;
}
