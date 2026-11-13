<?php

declare(strict_types=1);

namespace Modules\Agents\Application;

use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;

final readonly class UpdateAgentCommand
{
    public function __construct(
        public UserId $actorId,
        public AgentId $agentId,
        public ?string $name = null,
        public ?string $role = null,
        public ?string $instructions = null,
    ) {
    }
}
