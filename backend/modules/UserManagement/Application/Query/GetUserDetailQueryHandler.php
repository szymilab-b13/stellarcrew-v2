<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

use Modules\UserManagement\Api\Dto\UserDetailDto;
use Modules\UserManagement\Application\ReadModel\UserProfileReadModel;

final readonly class GetUserDetailQueryHandler
{
    public function __construct(
        private UserProfileReadModel $readModel,
    ) {
    }

    public function __invoke(GetUserDetailQuery $query): ?UserDetailDto
    {
        return $this->readModel->findDetail($query->userId);
    }
}
