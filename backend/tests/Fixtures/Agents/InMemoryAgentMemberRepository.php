<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Agents;

use Modules\Agents\Domain\AgentMember;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;

final class InMemoryAgentMemberRepository implements AgentMemberRepository
{
    /** @var array<string, AgentMember> keyed by member uuid */
    private array $members = [];

    public function save(AgentMember $member): void
    {
        $this->members[(string) $member->id()] = $member;
    }

    public function find(AgentId $agentId, UserId $userId): ?AgentMember
    {
        foreach ($this->members as $member) {
            if (!$member->agentId()->equals($agentId)) {
                continue;
            }

            if (!$member->userId()->equals($userId)) {
                continue;
            }

            return $member;
        }

        return null;
    }

    public function remove(AgentMember $member): void
    {
        unset($this->members[(string) $member->id()]);
    }

    public function exists(AgentId $agentId, UserId $userId): bool
    {
        return $this->find($agentId, $userId) instanceof AgentMember;
    }

    public function listMembersOf(AgentId $agentId): array
    {
        $result = [];
        foreach ($this->members as $member) {
            if (!$member->agentId()->equals($agentId)) {
                continue;
            }

            $result[] = $member;
        }

        return $result;
    }

    public function listAgentIdsFor(UserId $userId): array
    {
        $result = [];
        foreach ($this->members as $member) {
            if (!$member->userId()->equals($userId)) {
                continue;
            }

            $result[] = $member->agentId();
        }

        return $result;
    }
}
