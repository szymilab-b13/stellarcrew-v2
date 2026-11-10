<?php

declare(strict_types=1);

namespace Modules\Organizations\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class OrganizationView
{
    public function __construct(
        public OrganizationId $id,
        public string $name,
        public string $slug,
        public UserId $ownerUserId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
