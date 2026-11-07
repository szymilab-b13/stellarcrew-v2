<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

final readonly class ListAllUsersQuery
{
    public function __construct(
        public UserListFilters $filters,
    ) {
    }
}
