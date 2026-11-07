<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Api;

use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\UserManagementApi;
use Modules\UserManagement\Domain\AvatarColorPicker;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;

final readonly class DefaultUserManagementApi implements UserManagementApi
{
    public function __construct(
        private UserProfileRepository $profiles,
        private Clock $clock,
    ) {
    }

    public function createProfileForUser(UserId $userId, string $email, ?string $displayName = null): UserProfileDto
    {
        $profile = UserProfile::create(
            $userId,
            $displayName,
            AvatarColorPicker::pick($email),
            $this->clock->now(),
        );

        $this->profiles->save($profile);

        return $this->toDto($profile);
    }

    public function getProfile(UserId $userId): ?UserProfileDto
    {
        $profile = $this->profiles->findById($userId);
        if (!$profile instanceof UserProfile) {
            return null;
        }

        return $this->toDto($profile);
    }

    private function toDto(UserProfile $profile): UserProfileDto
    {
        return new UserProfileDto(
            id: (string) $profile->id(),
            displayName: $profile->displayName(),
            avatarColor: $profile->avatarColor(),
            isDeactivated: $profile->isDeactivated(),
        );
    }
}
