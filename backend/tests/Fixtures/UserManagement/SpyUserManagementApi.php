<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\UserManagement;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\UserManagementApi;
use Modules\UserManagement\Domain\AvatarColorPicker;

final class SpyUserManagementApi implements UserManagementApi
{
    /** @var list<array{userId: UserId, email: string, displayName: ?string}> */
    public array $createProfileCalls = [];

    public function createProfileForUser(UserId $userId, string $email, ?string $displayName = null): UserProfileDto
    {
        $this->createProfileCalls[] = [
            'userId' => $userId,
            'email' => $email,
            'displayName' => $displayName,
        ];

        return new UserProfileDto(
            id: (string) $userId,
            displayName: $displayName,
            avatarColor: AvatarColorPicker::pick($email),
            isDeactivated: false,
        );
    }

    public function getProfile(UserId $userId): ?UserProfileDto
    {
        unset($userId);

        return null;
    }
}
