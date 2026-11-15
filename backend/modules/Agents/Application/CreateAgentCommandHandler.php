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
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Api\LimitType;
use Modules\Shared\Api\PlanLimitsService;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;

final readonly class CreateAgentCommandHandler
{
    public const string AUDIT_ACTION = 'agents.agent.created';

    public const string AUDIT_RESOURCE_TYPE = 'agent';

    public function __construct(
        private AgentRepository $agents,
        private AgentMemberRepository $agentMembers,
        private OrganizationsApi $organizations,
        private PlanLimitsService $planLimits,
        private AuditLoggerInterface $audit,
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
            $command->roleDescription,
            $command->expertise,
            $command->workflow,
            $command->style,
            $command->outputFormat,
            $now,
        );

        $this->agents->save($agent);

        $this->agentMembers->save(AgentMember::grant(
            $agent->id(),
            $command->actorId,
            $command->actorId,
            $now,
        ));

        $this->audit->log(new AuditEntry(
            userId: $command->actorId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $agent->id(),
            metadata: [
                'after_state' => self::snapshot($agent),
            ],
        ));

        return $agent;
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Agent $agent): array
    {
        $templateId = $agent->sourceTemplateId();

        return [
            'id' => (string) $agent->id(),
            'organization_id' => (string) $agent->organizationId(),
            'owner_user_id' => (string) $agent->ownerUserId(),
            'source_template_id' => $templateId instanceof AgentTemplateId ? (string) $templateId : null,
            'slug' => $agent->slug(),
            'name' => $agent->name(),
            'role' => $agent->role(),
            'role_description' => $agent->roleDescription(),
            'expertise' => $agent->expertise(),
            'workflow' => $agent->workflow(),
            'style' => $agent->style(),
            'output_format' => $agent->outputFormat(),
            'is_active' => $agent->isActive(),
        ];
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
