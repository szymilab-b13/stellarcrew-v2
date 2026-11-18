<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\WorkerId;

interface AgentRepository
{
    public function save(Agent $agent): void;

    public function findById(AgentId $id): ?Agent;

    public function findBySlug(OrganizationId $organizationId, string $slug): ?Agent;

    public function existsSlug(OrganizationId $organizationId, string $slug): bool;

    public function countActiveFor(OrganizationId $organizationId): int;

    /**
     * @param list<AgentId> $ids
     *
     * @return list<Agent>
     */
    public function findActiveByIds(array $ids): array;

    /**
     * @return list<Agent>
     */
    public function findByDefaultWorker(WorkerId $workerId): array;
}
