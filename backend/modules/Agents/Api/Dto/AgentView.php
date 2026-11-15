<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class AgentView
{
    public function __construct(
        public AgentId $id,
        public OrganizationId $organizationId,
        public UserId $ownerUserId,
        public ?AgentTemplateId $sourceTemplateId,
        public string $slug,
        public string $name,
        public string $role,
        public string $roleDescription,
        public string $expertise,
        public string $workflow,
        public string $style,
        public string $outputFormat,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
