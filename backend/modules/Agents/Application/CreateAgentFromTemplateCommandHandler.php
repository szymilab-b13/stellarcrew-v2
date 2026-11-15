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
use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Api\LimitType;
use Modules\Shared\Api\PlanLimitsService;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CreateAgentFromTemplateCommandHandler
{
    public const string AUDIT_ACTION = 'agents.agent.created_from_template';

    public function __construct(
        private AgentRepository $agents,
        private AgentMemberRepository $agentMembers,
        private AgentTemplateRepository $templates,
        private OrganizationsApi $organizations,
        private PlanLimitsService $planLimits,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateAgentFromTemplateCommand $command): Agent
    {
        $template = $this->templates->findBySlug($command->templateSlug);
        if (!$template instanceof AgentTemplate) {
            throw new NotFoundHttpException(
                sprintf('Agent template "%s" not found.', $command->templateSlug),
            );
        }

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

        $name = $command->name ?? $template->name();
        $slug = $this->resolveSlug($organizationId, $command->slug, $name);
        $now = $this->clock->now();

        $agent = Agent::create(
            AgentId::generate(),
            $organizationId,
            $command->actorId,
            $slug,
            $name,
            $command->role ?? $template->role(),
            $command->roleDescription ?? $template->roleDescription(),
            $command->expertise ?? $template->expertise(),
            $command->workflow ?? $template->workflow(),
            $command->style ?? $template->style(),
            $command->outputFormat ?? $template->outputFormat(),
            $now,
            $template->id(),
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
            resourceType: CreateAgentCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $agent->id(),
            metadata: [
                'after_state' => CreateAgentCommandHandler::snapshot($agent),
                'template_slug' => $template->slug(),
            ],
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
