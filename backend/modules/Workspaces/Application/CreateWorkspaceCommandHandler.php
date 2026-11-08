<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\SlugGenerator;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class CreateWorkspaceCommandHandler
{
    public const string DEFAULT_ICON_COLOR = '#8B5CF6';

    public const string AUDIT_ACTION = 'workspaces.workspace.created';

    public const string AUDIT_RESOURCE_TYPE = 'workspace';

    public function __construct(
        private WorkspaceRepository $workspaces,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateWorkspaceCommand $command): Workspace
    {
        $now = $this->clock->now();
        $id = WorkspaceId::generate();
        $slug = $this->uniqueSlug($command->name);

        $workspace = Workspace::create(
            $id,
            $command->name,
            $slug,
            $command->iconColor ?? self::DEFAULT_ICON_COLOR,
            $command->ownerId,
            $now,
            $command->iconInitials,
        );

        $this->workspaces->saveWorkspace($workspace);
        $this->workspaces->saveMembership(
            WorkspaceMember::join($id, $command->ownerId, WorkspaceRole::Owner, $now),
        );

        $this->audit->log(new AuditEntry(
            userId: $command->ownerId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $id,
            metadata: [
                'after_state' => self::snapshot($workspace),
            ],
        ));

        return $workspace;
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Workspace $workspace): array
    {
        return [
            'id' => (string) $workspace->id(),
            'name' => $workspace->name(),
            'slug' => $workspace->slug(),
            'icon_color' => $workspace->iconColor(),
            'icon_initials' => $workspace->iconInitials(),
            'owner_id' => (string) $workspace->ownerId(),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = SlugGenerator::fromName($name);
        $slug = $base;
        while ($this->workspaces->findBySlug($slug) instanceof Workspace) {
            $slug = SlugGenerator::withRandomSuffix($base);
        }

        return $slug;
    }
}
