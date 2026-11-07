<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserDetailDto;
use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\Exception\EmailTakenException;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Application\Query\UserListFilters;

interface UserManagementApi
{
    public function createProfileForUser(UserId $userId, string $email, ?string $displayName = null): UserProfileDto;

    public function getProfile(UserId $userId): ?UserProfileDto;

    public function getUserDetail(UserId $userId): ?UserDetailDto;

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

    public function listAllUsers(UserListFilters $filters): UserListPage;

    /**
     * @return list<UserListItemDto>
     */
    public function listUsersVisibleTo(UserId $viewerId, ?string $q = null): array;

    /**
     * Idempotent: no-op when the profile is already deactivated.
     *
     * @throws UserProfileNotFoundException
     */
    public function deactivateUser(UserId $userId): void;

    /**
     * Idempotent: no-op when the profile is already active.
     *
     * @throws UserProfileNotFoundException
     */
    public function reactivateUser(UserId $userId): void;
}
