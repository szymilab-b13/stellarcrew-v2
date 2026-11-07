<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Application\ReadModel\UserProfileReadModel;

final readonly class ListVisibleUsersQueryHandler
{
    public function __construct(
        private UserProfileReadModel $readModel,
    ) {
    }

    /**
     * @return list<UserListItemDto>
     */
    public function __invoke(ListVisibleUsersQuery $query): array
    {
        return $this->readModel->listVisibleTo($query->viewerId, $query->q);
    }
}
