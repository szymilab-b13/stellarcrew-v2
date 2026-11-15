<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;

interface AgentMemberRepository
{
    public function save(AgentMember $member): void;

    public function find(AgentId $agentId, UserId $userId): ?AgentMember;

    public function remove(AgentMember $member): void;

    public function exists(AgentId $agentId, UserId $userId): bool;

    /**
     * @return list<AgentMember>
     */
    public function listMembersOf(AgentId $agentId): array;

    /**
     * @return list<AgentId>
     */
    public function listAgentIdsFor(UserId $userId): array;
}
