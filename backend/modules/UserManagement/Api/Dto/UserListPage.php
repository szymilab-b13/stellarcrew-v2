<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Dto;

final readonly class UserListPage
{
    /**
     * @param list<UserListItemDto> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
