<?php

declare(strict_types=1);

namespace Modules\Agents\Application;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentLimitReachedException;
use Modules\Agents\Domain\AgentMember;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Agents\Domain\AgentRepository;
use Modules\Agents\Domain\AgentSlugAlreadyTakenException;
use Modules\Agents\Domain\AgentSlugGenerator;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Api\LimitType;
use Modules\Shared\Api\PlanLimitsService;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;

final readonly class CreateAgentCommandHandler
{
    public function __construct(
        private AgentRepository $agents,
        private AgentMemberRepository $agentMembers,
        private OrganizationsApi $organizations,
        private PlanLimitsService $planLimits,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateAgentCommand $command): Agent
    {
        $organization = $this->organizations->findByOwner($command->actorId);
        if (!$organization instanceof OrganizationView) {
            throw OwnerHasNoOrganizationException::forOwner($command->actorId);
        }

        $organizationId = $organization->id;
        $usage = $this->agents->countActiveFor($organizationId);
        if ($this->planLimits->exceeded(LimitType::Agents, $organizationId, $usage)) {
            throw AgentLimitReachedException::forOrganization(
                $organizationId,
                $this->planLimits->maxFor(LimitType::Agents, $organizationId),
            );
        }

        $slug = $this->resolveSlug($organizationId, $command->slug, $command->name);
        $now = $this->clock->now();

        $agent = Agent::create(
            AgentId::generate(),
            $organizationId,
            $command->actorId,
            $slug,
            $command->name,
            $command->role,
            $command->instructions,
            $now,
        );

        $this->agents->save($agent);

        $this->agentMembers->save(AgentMember::grant(
            $agent->id(),
            $command->actorId,
            $command->actorId,
            $now,
        ));

        return $agent;
    }

    private function resolveSlug(OrganizationId $organizationId, ?string $provided, string $name): string
    {
        if ($provided !== null) {
            if ($this->agents->existsSlug($organizationId, $provided)) {
                throw AgentSlugAlreadyTakenException::forOrganization($organizationId, $provided);
            }

            return $provided;
        }

        $base = AgentSlugGenerator::fromName($name);
        $slug = $base;
        while ($this->agents->existsSlug($organizationId, $slug)) {
            $slug = AgentSlugGenerator::withRandomSuffix($base);
        }

        return $slug;
    }
}
