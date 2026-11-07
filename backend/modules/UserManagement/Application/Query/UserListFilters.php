<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

use Modules\Shared\Kernel\WorkspaceId;

final readonly class UserListFilters
{
    public function __construct(
        public ?string $q = null,
        public ?WorkspaceId $workspaceId = null,
        public ?bool $hasSuperAdmin = null,
        public ?bool $onlyDeactivated = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
