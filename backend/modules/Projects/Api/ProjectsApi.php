<?php

declare(strict_types=1);

namespace Modules\Projects\Api;

use Modules\Projects\Api\Dto\ProjectView;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

interface ProjectsApi
{
    public function findById(ProjectId $id): ?ProjectView;

    /**
     * @return list<ProjectView>
     */
    public function findByWorkspace(WorkspaceId $workspaceId): array;

    public function findBySlug(WorkspaceId $workspaceId, string $slug): ?ProjectView;

    public function exists(ProjectId $id): bool;

    public function existsInWorkspace(WorkspaceId $workspaceId, string $slug): bool;
}
