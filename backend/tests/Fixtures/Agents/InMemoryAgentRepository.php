<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Agents;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentRepository;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\WorkerId;

final class InMemoryAgentRepository implements AgentRepository
{
    /** @var array<string, Agent> keyed by agent id */
    private array $agents = [];

    public function save(Agent $agent): void
    {
        $this->agents[(string) $agent->id()] = $agent;
    }

    public function findById(AgentId $id): ?Agent
    {
        return $this->agents[(string) $id] ?? null;
    }

    public function findBySlug(OrganizationId $organizationId, string $slug): ?Agent
    {
        foreach ($this->agents as $agent) {
            if (!$agent->organizationId()->equals($organizationId)) {
                continue;
            }

            if ($agent->isDeleted()) {
                continue;
            }

            if ($agent->slug() === $slug) {
                return $agent;
            }
        }

        return null;
    }

    public function existsSlug(OrganizationId $organizationId, string $slug): bool
    {
        return $this->findBySlug($organizationId, $slug) instanceof Agent;
    }

    public function countActiveFor(OrganizationId $organizationId): int
    {
        $count = 0;
        foreach ($this->agents as $agent) {
            if (!$agent->organizationId()->equals($organizationId)) {
                continue;
            }

            if ($agent->isDeleted()) {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    public function findActiveByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $wanted = [];
        foreach ($ids as $id) {
            $wanted[(string) $id] = true;
        }

        $result = [];
        foreach ($this->agents as $agent) {
            if (!isset($wanted[(string) $agent->id()])) {
                continue;
            }

            if ($agent->isDeleted()) {
                continue;
            }

            $result[] = $agent;
        }

        usort($result, static fn (Agent $a, Agent $b): int => strcmp($a->name(), $b->name()));

        return $result;
    }

    public function findByDefaultWorker(WorkerId $workerId): array
    {
        $result = [];
        foreach ($this->agents as $agent) {
            $assigned = $agent->defaultWorkerId();
            if ($assigned instanceof WorkerId && $assigned->equals($workerId)) {
                $result[] = $agent;
            }
        }

        return $result;
    }
}
