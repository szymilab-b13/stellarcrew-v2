<?php

declare(strict_types=1);

namespace Modules\Projects\Domain;

use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

interface ProjectRepository
{
    public function save(Project $project): void;

    public function findById(ProjectId $id): ?Project;

    /**
     * @return list<Project>
     */
    public function findByWorkspace(WorkspaceId $workspaceId): array;

    public function existsSlugInWorkspace(WorkspaceId $workspaceId, string $slug): bool;
}
