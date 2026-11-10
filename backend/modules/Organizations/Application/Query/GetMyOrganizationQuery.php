<?php

declare(strict_types=1);

namespace Modules\Organizations\Application\Query;

use Modules\Shared\Kernel\UserId;

final readonly class GetMyOrganizationQuery
{
    public function __construct(
        public UserId $actorId,
    ) {
    }
}
