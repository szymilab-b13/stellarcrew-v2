<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class AgentView
{
    public function __construct(
        public AgentId $id,
        public OrganizationId $organizationId,
        public UserId $ownerUserId,
        public ?AgentId $sourceTemplateId,
        public string $slug,
        public string $name,
        public string $role,
        public string $instructions,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
