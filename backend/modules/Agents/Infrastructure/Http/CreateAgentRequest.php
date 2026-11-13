<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Http;

use Modules\Agents\Domain\Agent;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAgentRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'name is required')]
        #[Assert\Length(max: Agent::NAME_MAX, maxMessage: 'name cannot exceed {{ limit }} characters')]
        public string $name,
        #[Assert\NotBlank(message: 'role is required')]
        #[Assert\Length(max: Agent::ROLE_MAX, maxMessage: 'role cannot exceed {{ limit }} characters')]
        public string $role,
        #[Assert\NotBlank(message: 'instructions is required')]
        #[Assert\Length(max: Agent::INSTRUCTIONS_MAX, maxMessage: 'instructions cannot exceed {{ limit }} characters')]
        public string $instructions,
        #[Assert\Length(max: Agent::SLUG_MAX, maxMessage: 'slug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'slug must be kebab-case')]
        public ?string $slug = null,
    ) {
    }
}
