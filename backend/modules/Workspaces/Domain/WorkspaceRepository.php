<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

interface WorkspaceRepository
{
    public function saveWorkspace(Workspace $workspace): void;

    public function findById(WorkspaceId $id): ?Workspace;

    public function findBySlug(string $slug): ?Workspace;

    public function saveMembership(WorkspaceMember $member): void;

    public function removeMembership(WorkspaceMember $member): void;

    public function removeAllMembershipsOf(UserId $userId): void;

    public function findMembership(WorkspaceId $workspaceId, UserId $userId): ?WorkspaceMember;

    /**
     * @return list<Workspace>
     */
    public function listForUser(UserId $userId): array;

    /**
     * @return list<array{workspace: Workspace, role: WorkspaceRole}>
     */
    public function listForUserWithRoles(UserId $userId): array;

    public function count(): int;
}
