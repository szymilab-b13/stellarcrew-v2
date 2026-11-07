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

    public function findMembership(WorkspaceId $workspaceId, UserId $userId): ?WorkspaceMember;

    /**
     * @return list<Workspace>
     */
    public function listForUser(UserId $userId): array;

    public function count(): int;
}
