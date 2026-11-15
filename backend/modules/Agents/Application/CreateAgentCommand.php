<?php

declare(strict_types=1);

namespace Modules\Agents\Application;

use Modules\Shared\Kernel\UserId;

final readonly class CreateAgentCommand
{
    public function __construct(
        public UserId $actorId,
        public string $name,
        public string $role,
        public string $roleDescription,
        public string $expertise,
        public string $workflow,
        public string $style,
        public string $outputFormat,
        public ?string $slug = null,
    ) {
    }
}
