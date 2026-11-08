<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\ReadModel;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

use function max;
use function min;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\UserManagement\Api\Dto\UserDetailDto;
use Modules\UserManagement\Api\Dto\UserListItemDto;
use Modules\UserManagement\Api\Dto\UserListPage;
use Modules\UserManagement\Api\Dto\UserWorkspaceAssignmentDto;
use Modules\UserManagement\Application\Query\UserListFilters;
use Modules\UserManagement\Application\ReadModel\UserProfileReadModel;

final readonly class DbalUserProfileReadModel implements UserProfileReadModel
{
    private const int MAX_PER_PAGE = 100;

    private const string ROLE_ADMIN = 'admin';

    public function __construct(private Connection $connection)
    {
    }

    public function listAll(UserListFilters $filters): UserListPage
    {
        $page = max(1, $filters->page);
        $perPage = min(self::MAX_PER_PAGE, max(1, $filters->perPage));

        $conditions = ['1 = 1'];
        $params = [];
        $types = [];

        if ($filters->q !== null && $filters->q !== '') {
            $conditions[] = 'u.email ILIKE :q';
            $params['q'] = '%' . $filters->q . '%';
            $types['q'] = ParameterType::STRING;
        }

        if ($filters->workspaceId instanceof WorkspaceId) {
            $conditions[] = 'u.id IN (SELECT wm.user_id FROM workspaces.workspace_members wm WHERE wm.workspace_id = :workspace_id)';
            $params['workspace_id'] = (string) $filters->workspaceId;
            $types['workspace_id'] = ParameterType::STRING;
        }

        if ($filters->hasSuperAdmin !== null) {
            $conditions[] = $filters->hasSuperAdmin
                ? 'u.role = :admin_role'
                : 'u.role <> :admin_role';
            $params['admin_role'] = self::ROLE_ADMIN;
            $types['admin_role'] = ParameterType::STRING;
        }

        if ($filters->onlyDeactivated !== null) {
            $conditions[] = $filters->onlyDeactivated
                ? 'p.deactivated_at IS NOT NULL'
                : 'p.deactivated_at IS NULL';
        }

        $where = implode(' AND ', $conditions);

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM user_management.user_profiles p '
            . 'INNER JOIN auth.users u ON u.id = p.id '
            . 'WHERE ' . $where,
            $params,
            $types,
        );

        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT '
            . 'u.id, u.email, u.role, u.created_at AS u_created_at, '
            . 'p.deactivated_at, '
            . '(SELECT COUNT(*) FROM workspaces.workspace_members wm WHERE wm.user_id = u.id) AS workspace_count '
            . 'FROM user_management.user_profiles p '
            . 'INNER JOIN auth.users u ON u.id = p.id '
            . 'WHERE ' . $where . ' '
            . 'ORDER BY u.created_at DESC, u.id DESC '
            . 'LIMIT :limit OFFSET :offset';

        $params['limit'] = $perPage;
        $params['offset'] = $offset;
        $types['limit'] = ParameterType::INTEGER;
        $types['offset'] = ParameterType::INTEGER;

        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrate($row);
        }

        return new UserListPage($items, $total, $page, $perPage);
    }

    public function listVisibleTo(UserId $viewerId, ?string $q = null): array
    {
        $viewerRole = $this->connection->fetchOne(
            'SELECT role FROM auth.users WHERE id = :viewer_id',
            ['viewer_id' => (string) $viewerId],
            ['viewer_id' => ParameterType::STRING],
        );
        $isSuperAdmin = $viewerRole === self::ROLE_ADMIN;

        $conditions = ['u.id <> :viewer_id'];
        $params = ['viewer_id' => (string) $viewerId];
        $types = ['viewer_id' => ParameterType::STRING];

        if (!$isSuperAdmin) {
            $viewerWorkspaceIds = $this->connection->fetchFirstColumn(
                'SELECT workspace_id FROM workspaces.workspace_members WHERE user_id = :viewer_id',
                ['viewer_id' => (string) $viewerId],
                ['viewer_id' => ParameterType::STRING],
            );

            if ($viewerWorkspaceIds === []) {
                return [];
            }

            $params['workspace_ids'] = $viewerWorkspaceIds;
            $types['workspace_ids'] = ArrayParameterType::STRING;
            $conditions[] = 'u.id IN (SELECT wm.user_id FROM workspaces.workspace_members wm WHERE wm.workspace_id IN (:workspace_ids))';
        }

        if ($q !== null && $q !== '') {
            $conditions[] = 'u.email ILIKE :q';
            $params['q'] = '%' . $q . '%';
            $types['q'] = ParameterType::STRING;
        }

        $sql = 'SELECT DISTINCT '
            . 'u.id, u.email, u.role, u.created_at AS u_created_at, '
            . 'p.deactivated_at, '
            . '(SELECT COUNT(*) FROM workspaces.workspace_members wm WHERE wm.user_id = u.id) AS workspace_count '
            . 'FROM user_management.user_profiles p '
            . 'INNER JOIN auth.users u ON u.id = p.id '
            . 'WHERE ' . implode(' AND ', $conditions) . ' '
            . 'ORDER BY u.email ASC';

        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrate($row);
        }

        return $items;
    }

    public function findDetail(UserId $userId): ?UserDetailDto
    {
        $row = $this->connection->fetchAssociative(
            'SELECT u.id, u.email, u.role, u.created_at AS u_created_at, '
            . 'p.display_name, p.avatar_color, p.deactivated_at '
            . 'FROM user_management.user_profiles p '
            . 'INNER JOIN auth.users u ON u.id = p.id '
            . 'WHERE u.id = :id',
            ['id' => (string) $userId],
            ['id' => ParameterType::STRING],
        );
        if ($row === false) {
            return null;
        }

        $assignments = $this->connection->fetchAllAssociative(
            'SELECT w.id AS workspace_id, w.name, w.slug, w.icon_color, w.icon_initials, '
            . 'wm.role, wm.joined_at '
            . 'FROM workspaces.workspace_members wm '
            . 'INNER JOIN workspaces.workspaces w ON w.id = wm.workspace_id '
            . 'WHERE wm.user_id = :id AND w.deleted_at IS NULL '
            . 'ORDER BY wm.joined_at ASC, w.name ASC',
            ['id' => (string) $userId],
            ['id' => ParameterType::STRING],
        );

        $workspaceAssignments = [];
        foreach ($assignments as $assignment) {
            $joinedAt = $assignment['joined_at'];
            if (!$joinedAt instanceof DateTimeImmutable) {
                $joinedAt = new DateTimeImmutable((string) $joinedAt);
            }

            $workspaceAssignments[] = new UserWorkspaceAssignmentDto(
                workspaceId: (string) $assignment['workspace_id'],
                workspaceName: (string) $assignment['name'],
                workspaceSlug: (string) $assignment['slug'],
                iconColor: (string) $assignment['icon_color'],
                iconInitials: (string) $assignment['icon_initials'],
                role: (string) $assignment['role'],
                joinedAt: $joinedAt,
            );
        }

        $createdAt = $row['u_created_at'];
        if (!$createdAt instanceof DateTimeImmutable) {
            $createdAt = new DateTimeImmutable((string) $createdAt);
        }

        $deactivatedAt = $row['deactivated_at'] ?? null;
        if ($deactivatedAt !== null && !$deactivatedAt instanceof DateTimeImmutable) {
            $deactivatedAt = new DateTimeImmutable((string) $deactivatedAt);
        }

        return new UserDetailDto(
            id: (string) $row['id'],
            email: (string) $row['email'],
            isSuperAdmin: (string) $row['role'] === self::ROLE_ADMIN,
            displayName: $row['display_name'] === null ? null : (string) $row['display_name'],
            avatarColor: (string) $row['avatar_color'],
            createdAt: $createdAt,
            deactivatedAt: $deactivatedAt,
            workspaceAssignments: $workspaceAssignments,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): UserListItemDto
    {
        $createdAt = $row['u_created_at'];
        if (!$createdAt instanceof DateTimeImmutable) {
            $createdAt = new DateTimeImmutable((string) $createdAt);
        }

        $deactivatedAt = $row['deactivated_at'] ?? null;
        if ($deactivatedAt !== null && !$deactivatedAt instanceof DateTimeImmutable) {
            $deactivatedAt = new DateTimeImmutable((string) $deactivatedAt);
        }

        return new UserListItemDto(
            id: (string) $row['id'],
            email: (string) $row['email'],
            isSuperAdmin: (string) $row['role'] === self::ROLE_ADMIN,
            workspaceCount: (int) $row['workspace_count'],
            createdAt: $createdAt,
            deactivatedAt: $deactivatedAt,
        );
    }
}
