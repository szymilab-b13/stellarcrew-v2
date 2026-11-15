<?php

declare(strict_types=1);

namespace Modules\Agents\Api;

use Modules\Agents\Api\Dto\AgentView;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

interface AgentsApi
{
    public function findById(AgentId $id): ?AgentView;

    public function findBySlug(OrganizationId $organizationId, string $slug): ?AgentView;

    /**
     * @return list<AgentView>
     */
    public function listForOwner(UserId $ownerUserId): array;

    public function countActiveFor(OrganizationId $organizationId): int;
}
