<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Agents\Domain\AgentRepository;

final readonly class ListAgentsQueryHandler
{
    public function __construct(
        private AgentRepository $agents,
        private AgentMemberRepository $agentMembers,
    ) {
    }

    /**
     * @return list<Agent>
     */
    public function __invoke(ListAgentsQuery $query): array
    {
        $agentIds = $this->agentMembers->listAgentIdsFor($query->actorId);
        if ($agentIds === []) {
            return [];
        }

        return $this->agents->findActiveByIds($agentIds);
    }
}
