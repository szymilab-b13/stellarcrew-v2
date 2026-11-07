<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Workspaces;

use function array_map;
use function array_values;

use DateTimeImmutable;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Application\ReadModel\WorkspaceMemberListItem;
use Modules\Workspaces\Application\ReadModel\WorkspaceMembersReadModel;

use function usort;

final class InMemoryWorkspaceMembersReadModel implements WorkspaceMembersReadModel
{
    /** @var list<array{workspaceId: string, userId: string, email: string, role: string, joinedAt: DateTimeImmutable}> */
    private array $rows = [];

    public function seedMember(
        string $workspaceId,
        string $userId,
        string $email,
        string $role,
        DateTimeImmutable $joinedAt,
    ): void {
        $this->rows[] = [
            'workspaceId' => $workspaceId,
            'userId' => $userId,
            'email' => $email,
            'role' => $role,
            'joinedAt' => $joinedAt,
        ];
    }

    public function listForWorkspace(WorkspaceId $workspaceId): array
    {
        $matched = [];
        foreach ($this->rows as $row) {
            if ($row['workspaceId'] === (string) $workspaceId) {
                $matched[] = $row;
            }
        }

        usort($matched, static function (array $a, array $b): int {
            $byJoined = $a['joinedAt']->getTimestamp() <=> $b['joinedAt']->getTimestamp();
            if ($byJoined !== 0) {
                return $byJoined;
            }

            return $a['email'] <=> $b['email'];
        });

        return array_values(array_map(
            static fn (array $r): WorkspaceMemberListItem => new WorkspaceMemberListItem(
                userId: $r['userId'],
                email: $r['email'],
                role: $r['role'],
                joinedAt: $r['joinedAt'],
            ),
            $matched,
        ));
    }
}
