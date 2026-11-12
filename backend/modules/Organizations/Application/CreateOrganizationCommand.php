<?php

declare(strict_types=1);

namespace Modules\Organizations\Application;

use Modules\Shared\Kernel\UserId;

final readonly class CreateOrganizationCommand
{
    public function __construct(
        public UserId $ownerUserId,
        public string $name,
        public ?string $slug = null,
    ) {
    }
}
