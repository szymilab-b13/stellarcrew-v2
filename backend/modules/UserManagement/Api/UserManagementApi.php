<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserProfileDto;

interface UserManagementApi
{
    public function createProfileForUser(UserId $userId, string $email, ?string $displayName = null): UserProfileDto;

    public function getProfile(UserId $userId): ?UserProfileDto;
}
