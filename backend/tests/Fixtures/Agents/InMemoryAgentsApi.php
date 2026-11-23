<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Agents;

use Modules\Agents\Api\AgentsApi;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class InMemoryAgentsApi implements AgentsApi
{
    /**
     * @param list<AgentView> $agents
     */
    public function __construct(private array $agents = [])
    {
    }

    public function findById(AgentId $id): ?AgentView
    {
        foreach ($this->agents as $agent) {
            if ($agent->id->value->equals($id->value)) {
                return $agent;
            }
        }

        return null;
    }

    public function findBySlug(OrganizationId $organizationId, string $slug): ?AgentView
    {
        foreach ($this->agents as $agent) {
            if ($agent->slug === $slug && $agent->organizationId->value->equals($organizationId->value)) {
                return $agent;
            }
        }

        return null;
    }

    /**
     * @return list<AgentView>
     */
    public function listForOwner(UserId $ownerUserId): array
    {
        return $this->agents;
    }

    public function countActiveFor(OrganizationId $organizationId): int
    {
        $count = 0;
        foreach ($this->agents as $agent) {
            if ($agent->isActive && $agent->organizationId->value->equals($organizationId->value)) {
                ++$count;
            }
        }

        return $count;
    }
}
