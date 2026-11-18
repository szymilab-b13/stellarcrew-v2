<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Api;

use Modules\Agents\Api\AgentsApi;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Agents\Domain\AgentRepository;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class DefaultAgentsApi implements AgentsApi
{
    public function __construct(
        private AgentRepository $agents,
        private AgentMemberRepository $agentMembers,
    ) {
    }

    public function findById(AgentId $id): ?AgentView
    {
        $agent = $this->agents->findById($id);

        if (!$agent instanceof Agent || $agent->isDeleted()) {
            return null;
        }

        return $this->toView($agent);
    }

    public function findBySlug(OrganizationId $organizationId, string $slug): ?AgentView
    {
        $agent = $this->agents->findBySlug($organizationId, $slug);

        if (!$agent instanceof Agent || $agent->isDeleted()) {
            return null;
        }

        return $this->toView($agent);
    }

    /**
     * @return list<AgentView>
     */
    public function listForOwner(UserId $ownerUserId): array
    {
        $agentIds = $this->agentMembers->listAgentIdsFor($ownerUserId);
        if ($agentIds === []) {
            return [];
        }

        $views = [];
        foreach ($this->agents->findActiveByIds($agentIds) as $agent) {
            $views[] = $this->toView($agent);
        }

        return $views;
    }

    public function countActiveFor(OrganizationId $organizationId): int
    {
        return $this->agents->countActiveFor($organizationId);
    }

    private function toView(Agent $agent): AgentView
    {
        return new AgentView(
            id: $agent->id(),
            organizationId: $agent->organizationId(),
            ownerUserId: $agent->ownerUserId(),
            sourceTemplateId: $agent->sourceTemplateId(),
            slug: $agent->slug(),
            name: $agent->name(),
            role: $agent->role(),
            roleDescription: $agent->roleDescription(),
            expertise: $agent->expertise(),
            workflow: $agent->workflow(),
            style: $agent->style(),
            outputFormat: $agent->outputFormat(),
            isActive: $agent->isActive(),
            createdAt: $agent->createdAt(),
            updatedAt: $agent->updatedAt(),
            color: $agent->color(),
        );
    }
}
