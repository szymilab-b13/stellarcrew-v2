<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Application\ReadModel\UserProfileReadModel;

final readonly class ListAllUsersQueryHandler
{
    public function __construct(
        private UserProfileReadModel $readModel,
    ) {
    }

    public function __invoke(ListAllUsersQuery $query): UserListPage
    {
        return $this->readModel->listAll($query->filters);
    }
}
