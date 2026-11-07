<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\ReadModel;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Application\ReadModel\WorkspaceMemberListItem;
use Modules\Workspaces\Application\ReadModel\WorkspaceMembersReadModel;

final readonly class DbalWorkspaceMembersReadModel implements WorkspaceMembersReadModel
{
    public function __construct(private Connection $connection)
    {
    }

    public function listForWorkspace(WorkspaceId $workspaceId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT wm.user_id, u.email, wm.role, wm.joined_at '
            . 'FROM workspaces.workspace_members wm '
            . 'INNER JOIN auth.users u ON u.id = wm.user_id '
            . 'WHERE wm.workspace_id = :workspace_id '
            . 'ORDER BY wm.joined_at ASC, u.email ASC',
            ['workspace_id' => (string) $workspaceId],
            ['workspace_id' => ParameterType::STRING],
        );

        $items = [];
        foreach ($rows as $row) {
            $joinedAt = $row['joined_at'];
            if (!$joinedAt instanceof DateTimeImmutable) {
                $joinedAt = new DateTimeImmutable((string) $joinedAt);
            }

            $items[] = new WorkspaceMemberListItem(
                userId: (string) $row['user_id'],
                email: (string) $row['email'],
                role: (string) $row['role'],
                joinedAt: $joinedAt,
            );
        }

        return $items;
    }
}
