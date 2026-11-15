<?php

declare(strict_types=1);

namespace Modules\Agents\Application;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentRepository;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class DeleteAgentCommandHandler
{
    public const string AUDIT_ACTION = 'agents.agent.deleted';

    public function __construct(
        private AgentRepository $agents,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(DeleteAgentCommand $command): void
    {
        $agent = $this->agents->findById($command->agentId);
        if (!$agent instanceof Agent) {
            throw new NotFoundHttpException(
                sprintf('Agent "%s" not found.', (string) $command->agentId),
            );
        }

        if (!$agent->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedException(
                sprintf('Cannot delete agent "%s".', (string) $command->agentId),
            );
        }

        if ($agent->isDeleted()) {
            return;
        }

        $before = CreateAgentCommandHandler::snapshot($agent);
        $now = $this->clock->now();
        $agent->softDelete($now);
        $this->agents->save($agent);

        $this->audit->log(new AuditEntry(
            userId: $command->actorId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: CreateAgentCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $agent->id(),
            metadata: [
                'before_state' => $before,
            ],
        ));
    }
}
