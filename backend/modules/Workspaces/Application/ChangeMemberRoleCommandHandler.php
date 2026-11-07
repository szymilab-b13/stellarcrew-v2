<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Api\Exception\InvalidMemberRoleException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class ChangeMemberRoleCommandHandler
{
    public const string AUDIT_ACTION = 'workspace_member.role_changed';

    public const string AUDIT_RESOURCE_TYPE = 'workspace_member';

    public function __construct(
        private WorkspaceRepository $workspaces,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(ChangeMemberRoleCommand $command): WorkspaceMember
    {
        if ($command->role === WorkspaceRole::Owner) {
            throw InvalidMemberRoleException::ownerNotAssignable();
        }

        $member = $this->workspaces->findMembership($command->workspaceId, $command->userId);
        if (!$member instanceof WorkspaceMember) {
            throw MemberNotFoundException::forPair($command->workspaceId, $command->userId);
        }

        $previousRole = $member->role();
        $member->changeRole($command->role);
        $this->workspaces->saveMembership($member);

        $this->audit->log(new AuditEntry(
            userId: null,
            action: self::AUDIT_ACTION,
            occurredAt: $this->clock->now(),
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $member->id(),
            metadata: [
                'workspace_id' => (string) $command->workspaceId,
                'user_id' => (string) $command->userId,
                'before_state' => ['role' => $previousRole->value],
                'after_state' => ['role' => $command->role->value],
            ],
        ));

        return $member;
    }
}
