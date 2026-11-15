<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Http;

use Modules\Agents\Domain\Agent;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAgentRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: Agent::NAME_MAX, minMessage: 'name cannot be empty', maxMessage: 'name cannot exceed {{ limit }} characters')]
        public ?string $name = null,
        #[Assert\Length(min: 1, max: Agent::ROLE_MAX, minMessage: 'role cannot be empty', maxMessage: 'role cannot exceed {{ limit }} characters')]
        public ?string $role = null,
        #[Assert\Length(min: 1, max: Agent::INSTRUCTIONS_MAX, minMessage: 'instructions cannot be empty', maxMessage: 'instructions cannot exceed {{ limit }} characters')]
        public ?string $instructions = null,
    ) {
    }
}
