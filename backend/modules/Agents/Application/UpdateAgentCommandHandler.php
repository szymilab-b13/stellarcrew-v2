<?php

declare(strict_types=1);

namespace Modules\Agents\Application;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentRepository;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class UpdateAgentCommandHandler
{
    public function __construct(
        private AgentRepository $agents,
        private Clock $clock,
    ) {
    }

    public function __invoke(UpdateAgentCommand $command): Agent
    {
        $agent = $this->agents->findById($command->agentId);
        if (!$agent instanceof Agent || $agent->isDeleted()) {
            throw new NotFoundHttpException(
                sprintf('Agent "%s" not found.', (string) $command->agentId),
            );
        }

        if (!$agent->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedException(
                sprintf('Cannot update agent "%s".', (string) $command->agentId),
            );
        }

        $now = $this->clock->now();

        if ($command->name !== null) {
            $agent->rename($command->name, $now);
        }

        if ($command->role !== null) {
            $agent->changeRole($command->role, $now);
        }

        if ($command->roleDescription !== null) {
            $agent->updateRoleDescription($command->roleDescription, $now);
        }

        if ($command->expertise !== null) {
            $agent->updateExpertise($command->expertise, $now);
        }

        if ($command->workflow !== null) {
            $agent->updateWorkflow($command->workflow, $now);
        }

        if ($command->style !== null) {
            $agent->updateStyle($command->style, $now);
        }

        if ($command->outputFormat !== null) {
            $agent->updateOutputFormat($command->outputFormat, $now);
        }

        $this->agents->save($agent);

        return $agent;
    }
}
