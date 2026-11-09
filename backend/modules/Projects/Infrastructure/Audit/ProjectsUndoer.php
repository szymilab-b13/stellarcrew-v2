<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Audit;

use LogicException;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\UndoerInterface;
use Modules\Projects\Application\CreateProjectCommandHandler;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Application\RestoreProjectCommandHandler;
use Modules\Projects\Application\UpdateProjectCommandHandler;
use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;

final readonly class ProjectsUndoer implements UndoerInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private Clock $clock,
    ) {
    }

    public function supports(string $action): bool
    {
        return match ($action) {
            CreateProjectCommandHandler::AUDIT_ACTION,
            UpdateProjectCommandHandler::AUDIT_ACTION,
            DeleteProjectCommandHandler::AUDIT_ACTION,
            RestoreProjectCommandHandler::AUDIT_ACTION => true,
            default => false,
        };
    }

    public function undo(AuditEntryDto $entry, UserId $performedBy): void
    {
        unset($performedBy);

        match ($entry->action) {
            CreateProjectCommandHandler::AUDIT_ACTION => $this->undoCreated($entry),
            UpdateProjectCommandHandler::AUDIT_ACTION => $this->undoUpdated($entry),
            DeleteProjectCommandHandler::AUDIT_ACTION => $this->undoDeleted($entry),
            RestoreProjectCommandHandler::AUDIT_ACTION => $this->undoRestored($entry),
            default => throw new LogicException(
                'ProjectsUndoer received unsupported action: ' . $entry->action,
            ),
        };
    }

    private function undoCreated(AuditEntryDto $entry): void
    {
        $project = $this->requireProject($entry);

        if ($project->isDeleted()) {
            throw new UndoConflictException('already_deleted');
        }

        $project->softDelete($this->clock->now());
        $this->projects->save($project);
    }

    private function undoUpdated(AuditEntryDto $entry): void
    {
        $project = $this->requireProject($entry);

        if ($project->isDeleted()) {
            throw new UndoConflictException('project_deleted');
        }

        $before = $entry->beforeState ?? throw new UndoConflictException('missing_state');
        $name = $before['name'] ?? throw new UndoConflictException('missing_name');
        $slug = $before['slug'] ?? throw new UndoConflictException('missing_slug');
        $description = $before['description'] ?? null;

        $now = $this->clock->now();
        $project->rename((string) $name, $now);
        $project->changeSlug((string) $slug, $now);
        $project->changeDescription($description === null ? null : (string) $description, $now);

        $this->projects->save($project);
    }

    private function undoDeleted(AuditEntryDto $entry): void
    {
        $project = $this->requireProject($entry);

        if (!$project->isDeleted()) {
            throw new UndoConflictException('not_deleted');
        }

        $project->restore($this->clock->now());
        $this->projects->save($project);
    }

    private function undoRestored(AuditEntryDto $entry): void
    {
        $project = $this->requireProject($entry);

        if ($project->isDeleted()) {
            throw new UndoConflictException('already_deleted');
        }

        $project->softDelete($this->clock->now());
        $this->projects->save($project);
    }

    private function requireProject(AuditEntryDto $entry): Project
    {
        $resourceId = $entry->resourceId ?? throw new UndoConflictException('missing_resource_id');
        $project = $this->projects->findById(ProjectId::fromString($resourceId));

        if (!$project instanceof Project) {
            throw new UndoConflictException('project_not_found');
        }

        return $project;
    }
}
