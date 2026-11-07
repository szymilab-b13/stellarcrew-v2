<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\ReadModel;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Application\Query\UserListFilters;

interface UserProfileReadModel
{
    public function listAll(UserListFilters $filters): UserListPage;

    /**
     * @return list<UserListItemDto>
     */
    public function listVisibleTo(UserId $viewerId, ?string $q = null): array;
}
