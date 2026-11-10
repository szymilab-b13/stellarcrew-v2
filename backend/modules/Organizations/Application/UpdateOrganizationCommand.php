<?php

declare(strict_types=1);

namespace Modules\Organizations\Application;

use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class UpdateOrganizationCommand
{
    public function __construct(
        public UserId $actorId,
        public OrganizationId $organizationId,
        public ?string $name = null,
        public ?string $slug = null,
    ) {
    }
}
