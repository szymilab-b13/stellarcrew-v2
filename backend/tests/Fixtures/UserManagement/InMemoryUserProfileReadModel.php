<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\UserManagement;

use function array_filter;
use function array_map;
use function array_slice;
use function array_values;
use function count;

use DateTimeImmutable;

use function in_array;
use function max;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\UserManagement\Api\Dto\UserDetailDto;
use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Api\Dto\UserWorkspaceAssignmentDto;
use Modules\UserManagement\Application\Query\UserListFilters;
use Modules\UserManagement\Application\ReadModel\UserProfileReadModel;

use function str_contains;
use function strtolower;
use function usort;

final class InMemoryUserProfileReadModel implements UserProfileReadModel
{
    /** @var list<array{id: string, email: string, isSuperAdmin: bool, createdAt: DateTimeImmutable, deactivatedAt: ?DateTimeImmutable, workspaceIds: list<string>}> */
    private array $users = [];

    /**
     * @param list<string> $workspaceIds
     */
    public function seedUser(
        string $id,
        string $email,
        bool $isSuperAdmin,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $deactivatedAt = null,
        array $workspaceIds = [],
    ): void {
        $this->users[] = [
            'id' => $id,
            'email' => $email,
            'isSuperAdmin' => $isSuperAdmin,
            'createdAt' => $createdAt,
            'deactivatedAt' => $deactivatedAt,
            'workspaceIds' => $workspaceIds,
        ];
    }

    public function listAll(UserListFilters $filters): UserListPage
    {
        $matched = array_values(array_filter(
            $this->users,
            function (array $u) use ($filters): bool {
                if ($filters->q !== null && $filters->q !== '' && !str_contains(strtolower($u['email']), strtolower($filters->q))) {
                    return false;
                }

                if ($filters->workspaceId instanceof WorkspaceId && !in_array((string) $filters->workspaceId, $u['workspaceIds'], true)) {
                    return false;
                }

                if ($filters->hasSuperAdmin !== null && $u['isSuperAdmin'] !== $filters->hasSuperAdmin) {
                    return false;
                }

                if ($filters->onlyDeactivated !== null) {
                    $isDeactivated = $u['deactivatedAt'] !== null;
                    if ($isDeactivated !== $filters->onlyDeactivated) {
                        return false;
                    }
                }

                return true;
            },
        ));

        usort($matched, static fn (array $a, array $b): int => $b['createdAt']->getTimestamp() <=> $a['createdAt']->getTimestamp()
            ?: ($b['id'] <=> $a['id']));

        $total = count($matched);
        $page = max(1, $filters->page);
        $perPage = max(1, $filters->perPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($matched, $offset, $perPage);

        return new UserListPage(
            items: array_values(array_map($this->hydrate(...), $slice)),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    public function listVisibleTo(UserId $viewerId, ?string $q = null): array
    {
        $viewer = null;
        foreach ($this->users as $u) {
            if ($u['id'] === (string) $viewerId) {
                $viewer = $u;
                break;
            }
        }

        $isSuperAdmin = $viewer !== null && $viewer['isSuperAdmin'];

        if (!$isSuperAdmin && ($viewer === null || $viewer['workspaceIds'] === [])) {
            return [];
        }

        $viewerWorkspaces = $viewer['workspaceIds'];

        $matched = array_values(array_filter(
            $this->users,
            static function (array $u) use ($viewerId, $viewerWorkspaces, $isSuperAdmin, $q): bool {
                if ($u['id'] === (string) $viewerId) {
                    return false;
                }

                if (!$isSuperAdmin) {
                    $shared = false;
                    foreach ($u['workspaceIds'] as $wsId) {
                        if (in_array($wsId, $viewerWorkspaces, true)) {
                            $shared = true;
                            break;
                        }
                    }

                    if (!$shared) {
                        return false;
                    }
                }

                if ($q !== null && $q !== '' && !str_contains(strtolower($u['email']), strtolower($q))) {
                    return false;
                }

                return true;
            },
        ));

        usort($matched, static fn (array $a, array $b): int => $a['email'] <=> $b['email']);

        return array_values(array_map($this->hydrate(...), $matched));
    }

    public function findDetail(UserId $userId): ?UserDetailDto
    {
        foreach ($this->users as $u) {
            if ($u['id'] !== (string) $userId) {
                continue;
            }

            $assignments = [];
            foreach ($u['workspaceIds'] as $wsId) {
                $assignments[] = new UserWorkspaceAssignmentDto(
                    workspaceId: $wsId,
                    workspaceName: 'Workspace ' . $wsId,
                    workspaceSlug: 'workspace-' . $wsId,
                    iconColor: '#8B5CF6',
                    iconInitials: 'WS',
                    role: 'member',
                    joinedAt: $u['createdAt'],
                );
            }

            return new UserDetailDto(
                id: $u['id'],
                email: $u['email'],
                isSuperAdmin: $u['isSuperAdmin'],
                displayName: null,
                avatarColor: '#8B5CF6',
                createdAt: $u['createdAt'],
                deactivatedAt: $u['deactivatedAt'],
                workspaceAssignments: $assignments,
            );
        }

        return null;
    }

    /**
     * @param array{id: string, email: string, isSuperAdmin: bool, createdAt: DateTimeImmutable, deactivatedAt: ?DateTimeImmutable, workspaceIds: list<string>} $u
     */
    private function hydrate(array $u): UserListItemDto
    {
        return new UserListItemDto(
            id: $u['id'],
            email: $u['email'],
            isSuperAdmin: $u['isSuperAdmin'],
            workspaceCount: count($u['workspaceIds']),
            createdAt: $u['createdAt'],
            deactivatedAt: $u['deactivatedAt'],
        );
    }
}
