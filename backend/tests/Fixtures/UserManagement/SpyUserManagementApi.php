<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\UserManagement;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserDetailDto;
use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\UserManagementApi;
use Modules\UserManagement\Application\Query\UserListFilters;
use Modules\UserManagement\Domain\AvatarColorPicker;

final class SpyUserManagementApi implements UserManagementApi
{
    /** @var list<array{userId: UserId, email: string, displayName: ?string}> */
    public array $createProfileCalls = [];

    /** @var list<array{email: string, password: string, isSuperAdmin: bool, displayName: ?string}> */
    public array $createUserCalls = [];

    /** @var list<UserId> */
    public array $deactivateCalls = [];

    /** @var list<UserId> */
    public array $reactivateCalls = [];

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

    public function createUser(
        string $email,
        string $plaintextPassword,
        bool $isSuperAdmin = false,
        ?string $displayName = null,
    ): UserProfileDto {
        $this->createUserCalls[] = [
            'email' => $email,
            'password' => $plaintextPassword,
            'isSuperAdmin' => $isSuperAdmin,
            'displayName' => $displayName,
        ];

        return new UserProfileDto(
            id: 'spy-user-id',
            displayName: $displayName,
            avatarColor: AvatarColorPicker::pick($email),
            isDeactivated: false,
        );
    }

    public function listAllUsers(UserListFilters $filters): UserListPage
    {
        return new UserListPage([], 0, $filters->page, $filters->perPage);
    }

    public function listUsersVisibleTo(UserId $viewerId, ?string $q = null): array
    {
        unset($viewerId, $q);

        return [];
    }

    public function getUserDetail(UserId $userId): ?UserDetailDto
    {
        unset($userId);

        return null;
    }

    public function deactivateUser(UserId $userId): void
    {
        $this->deactivateCalls[] = $userId;
    }

    public function reactivateUser(UserId $userId): void
    {
        $this->reactivateCalls[] = $userId;
    }
}
