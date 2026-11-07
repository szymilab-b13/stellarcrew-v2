<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Api\Exception\AdminMustBeDowngradedFirstException;
use Modules\Workspaces\Api\Exception\CannotRemoveOwnerException;
use Modules\Workspaces\Api\Exception\MemberNotFoundException;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class RemoveMemberCommandHandler
{
    public const string AUDIT_ACTION = 'workspace_member.removed';

    public const string AUDIT_RESOURCE_TYPE = 'workspace_member';

    public function __construct(
        private WorkspaceRepository $workspaces,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(RemoveMemberCommand $command): void
    {
        $member = $this->workspaces->findMembership($command->workspaceId, $command->userId);
        if (!$member instanceof WorkspaceMember) {
            throw MemberNotFoundException::forPair($command->workspaceId, $command->userId);
        }

        $role = $member->role();
        if ($role === WorkspaceRole::Owner) {
            throw CannotRemoveOwnerException::forPair($command->workspaceId, $command->userId);
        }

        if ($role === WorkspaceRole::Admin) {
            throw AdminMustBeDowngradedFirstException::forPair($command->workspaceId, $command->userId);
        }

        $this->workspaces->removeMembership($member);

        $this->audit->log(new AuditEntry(
            userId: null,
            action: self::AUDIT_ACTION,
            occurredAt: $this->clock->now(),
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $member->id(),
            metadata: [
                'before_state' => [
                    'workspace_id' => (string) $command->workspaceId,
                    'user_id' => (string) $command->userId,
                    'role' => $role->value,
                ],
            ],
        ));
    }
}
