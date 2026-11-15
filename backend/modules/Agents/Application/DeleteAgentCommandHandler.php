<?php

declare(strict_types=1);

namespace Modules\Agents\Application;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentRepository;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class DeleteAgentCommandHandler
{
    public function __construct(
        private AgentRepository $agents,
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

        $agent->softDelete($this->clock->now());
        $this->agents->save($agent);
    }
}
