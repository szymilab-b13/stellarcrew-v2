<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workspaces;

use function count;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;

final class InMemoryWorkspaceRepository implements WorkspaceRepository
{
    /** @var array<string, Workspace> keyed by workspace id */
    private array $workspaces = [];

    /** @var list<WorkspaceMember> */
    private array $members = [];

    public function saveWorkspace(Workspace $workspace): void
    {
        $this->workspaces[(string) $workspace->id()] = $workspace;
    }

    public function findById(WorkspaceId $id): ?Workspace
    {
        return $this->workspaces[(string) $id] ?? null;
    }

    public function findBySlug(string $slug): ?Workspace
    {
        foreach ($this->workspaces as $workspace) {
            if ($workspace->slug() === $slug) {
                return $workspace;
            }
        }

        return null;
    }

    public function saveMembership(WorkspaceMember $member): void
    {
        foreach ($this->members as $index => $existing) {
            if (
                $existing->workspaceId()->equals($member->workspaceId())
                && $existing->userId()->equals($member->userId())
            ) {
                $this->members[$index] = $member;

                return;
            }
        }

        $this->members[] = $member;
    }

    public function removeMembership(WorkspaceMember $member): void
    {
        $kept = [];
        foreach ($this->members as $existing) {
            if (
                $existing->workspaceId()->equals($member->workspaceId())
                && $existing->userId()->equals($member->userId())
            ) {
                continue;
            }

            $kept[] = $existing;
        }

        $this->members = $kept;
    }

    public function findMembership(WorkspaceId $workspaceId, UserId $userId): ?WorkspaceMember
    {
        foreach ($this->members as $member) {
            if ($member->workspaceId()->equals($workspaceId) && $member->userId()->equals($userId)) {
                return $member;
            }
        }

        return null;
    }

    public function listForUser(UserId $userId): array
    {
        $ids = [];
        foreach ($this->members as $member) {
            if ($member->userId()->equals($userId)) {
                $ids[(string) $member->workspaceId()] = true;
            }
        }

        $result = [];
        foreach ($this->workspaces as $workspace) {
            if (!isset($ids[(string) $workspace->id()])) {
                continue;
            }

            if ($workspace->isDeleted()) {
                continue;
            }

            $result[] = $workspace;
        }

        return $result;
    }

    public function listForUserWithRoles(UserId $userId): array
    {
        $rolesByWorkspaceId = [];
        foreach ($this->members as $member) {
            if ($member->userId()->equals($userId)) {
                $rolesByWorkspaceId[(string) $member->workspaceId()] = $member->role();
            }
        }

        $result = [];
        foreach ($this->workspaces as $workspace) {
            $key = (string) $workspace->id();
            if (!isset($rolesByWorkspaceId[$key])) {
                continue;
            }

            if ($workspace->isDeleted()) {
                continue;
            }

            $result[] = [
                'workspace' => $workspace,
                'role' => $rolesByWorkspaceId[$key],
            ];
        }

        return $result;
    }

    public function count(): int
    {
        return count($this->workspaces);
    }
}
