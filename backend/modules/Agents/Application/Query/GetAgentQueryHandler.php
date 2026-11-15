<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Agents\Domain\AgentRepository;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class GetAgentQueryHandler
{
    public function __construct(
        private AgentRepository $agents,
        private AgentMemberRepository $agentMembers,
    ) {
    }

    public function __invoke(GetAgentQuery $query): Agent
    {
        $agent = $this->agents->findById($query->agentId);

        if (!$agent instanceof Agent || $agent->isDeleted()) {
            throw new NotFoundHttpException(
                sprintf('Agent "%s" not found.', (string) $query->agentId),
            );
        }

        if (!$this->agentMembers->exists($query->agentId, $query->actorId)) {
            throw new AccessDeniedException(
                sprintf('Cannot view agent "%s".', (string) $query->agentId),
            );
        }

        return $agent;
    }
}
