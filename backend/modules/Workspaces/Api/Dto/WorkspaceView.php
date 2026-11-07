<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Dto;

final readonly class WorkspaceView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $iconColor,
        public string $iconInitials,
        public string $ownerId,
    ) {
    }
}
