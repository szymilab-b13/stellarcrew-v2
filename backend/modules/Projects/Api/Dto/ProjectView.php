<?php

declare(strict_types=1);

namespace Modules\Projects\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class ProjectView
{
    public function __construct(
        public ProjectId $id,
        public WorkspaceId $workspaceId,
        public string $name,
        public string $slug,
        public ?string $description,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
