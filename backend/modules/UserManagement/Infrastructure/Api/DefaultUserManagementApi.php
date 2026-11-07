<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Api;

use Modules\Auth\Api\Exception\EmailTakenException as AuthEmailTakenException;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\Exception\EmailTakenException;
use Modules\UserManagement\Api\UserManagementApi;
use Modules\UserManagement\Application\CreateUserCommand;
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Application\Query\ListAllUsersQuery;
use Modules\UserManagement\Application\Query\ListAllUsersQueryHandler;
use Modules\UserManagement\Application\Query\ListVisibleUsersQuery;
use Modules\UserManagement\Application\Query\ListVisibleUsersQueryHandler;
use Modules\UserManagement\Application\Query\UserListFilters;
use Modules\UserManagement\Domain\AvatarColorPicker;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;

final readonly class DefaultUserManagementApi implements UserManagementApi
{
    public function __construct(
        private UserProfileRepository $profiles,
        private CreateUserCommandHandler $createUser,
        private ListAllUsersQueryHandler $listAllUsers,
        private ListVisibleUsersQueryHandler $listVisibleUsers,
        private Clock $clock,
    ) {
    }

    public function listAllUsers(UserListFilters $filters): UserListPage
    {
        return ($this->listAllUsers)(new ListAllUsersQuery($filters));
    }

    public function listUsersVisibleTo(UserId $viewerId, ?string $q = null): array
    {
        return ($this->listVisibleUsers)(new ListVisibleUsersQuery($viewerId, $q));
    }

    public function createUser(
        string $email,
        string $plaintextPassword,
        bool $isSuperAdmin = false,
        ?string $displayName = null,
    ): UserProfileDto {
        try {
            return ($this->createUser)(new CreateUserCommand(
                $email,
                $plaintextPassword,
                $isSuperAdmin,
                $displayName,
            ));
        } catch (AuthEmailTakenException) {
            throw EmailTakenException::forEmail($email);
        }
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
