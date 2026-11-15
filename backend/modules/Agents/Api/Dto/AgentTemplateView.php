<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentTemplateId;

final readonly class AgentTemplateView
{
    public function __construct(
        public AgentTemplateId $id,
        public string $slug,
        public string $name,
        public string $role,
        public string $roleDescription,
        public string $expertise,
        public string $workflow,
        public string $style,
        public string $outputFormat,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
