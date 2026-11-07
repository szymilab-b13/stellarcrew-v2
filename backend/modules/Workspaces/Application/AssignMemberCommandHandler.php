<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Api\Exception\InvalidMemberRoleException;
use Modules\Workspaces\Api\Exception\MemberAlreadyExistsException;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class AssignMemberCommandHandler
{
    public const string AUDIT_ACTION = 'workspace_member.assigned';

    public const string AUDIT_RESOURCE_TYPE = 'workspace_member';

    public function __construct(
        private WorkspaceRepository $workspaces,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(AssignMemberCommand $command): WorkspaceMember
    {
        if ($command->role === WorkspaceRole::Owner) {
            throw InvalidMemberRoleException::ownerNotAssignable();
        }

        if ($this->workspaces->findMembership($command->workspaceId, $command->userId) instanceof WorkspaceMember) {
            throw MemberAlreadyExistsException::forPair($command->workspaceId, $command->userId);
        }

        $now = $this->clock->now();
        $member = WorkspaceMember::join(
            $command->workspaceId,
            $command->userId,
            $command->role,
            $now,
            $command->actorUserId,
        );
        $this->workspaces->saveMembership($member);

        $this->audit->log(new AuditEntry(
            userId: $command->actorUserId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $member->id(),
            metadata: [
                'after_state' => [
                    'workspace_id' => (string) $command->workspaceId,
                    'user_id' => (string) $command->userId,
                    'role' => $command->role->value,
                ],
            ],
        ));

        return $member;
    }
}
