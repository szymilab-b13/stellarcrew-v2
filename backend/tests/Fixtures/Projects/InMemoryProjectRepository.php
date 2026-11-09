<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Projects;

use function count;

use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

final class InMemoryProjectRepository implements ProjectRepository
{
    /** @var array<string, Project> keyed by project id */
    private array $projects = [];

    public function save(Project $project): void
    {
        $this->projects[(string) $project->id()] = $project;
    }

    public function findById(ProjectId $id): ?Project
    {
        return $this->projects[(string) $id] ?? null;
    }

    public function findByWorkspace(WorkspaceId $workspaceId): array
    {
        $result = [];
        foreach ($this->projects as $project) {
            if (!$project->workspaceId()->equals($workspaceId)) {
                continue;
            }

            if ($project->isDeleted()) {
                continue;
            }

            $result[] = $project;
        }

        return $result;
    }

    public function existsSlugInWorkspace(WorkspaceId $workspaceId, string $slug): bool
    {
        foreach ($this->projects as $project) {
            if (!$project->workspaceId()->equals($workspaceId)) {
                continue;
            }

            if ($project->isDeleted()) {
                continue;
            }

            if ($project->slug() === $slug) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return count($this->projects);
    }
}
