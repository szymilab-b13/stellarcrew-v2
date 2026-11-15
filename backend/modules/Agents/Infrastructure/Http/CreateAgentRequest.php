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
        #[Assert\NotBlank(message: 'roleDescription is required')]
        #[Assert\Length(max: Agent::ROLE_DESCRIPTION_MAX, maxMessage: 'roleDescription cannot exceed {{ limit }} characters')]
        public string $roleDescription,
        #[Assert\NotBlank(message: 'expertise is required')]
        #[Assert\Length(max: Agent::SECTION_MAX, maxMessage: 'expertise cannot exceed {{ limit }} characters')]
        public string $expertise,
        #[Assert\NotBlank(message: 'workflow is required')]
        #[Assert\Length(max: Agent::SECTION_MAX, maxMessage: 'workflow cannot exceed {{ limit }} characters')]
        public string $workflow,
        #[Assert\NotBlank(message: 'style is required')]
        #[Assert\Length(max: Agent::SECTION_MAX, maxMessage: 'style cannot exceed {{ limit }} characters')]
        public string $style,
        #[Assert\NotBlank(message: 'outputFormat is required')]
        #[Assert\Length(max: Agent::SECTION_MAX, maxMessage: 'outputFormat cannot exceed {{ limit }} characters')]
        public string $outputFormat,
        #[Assert\Length(max: Agent::SLUG_MAX, maxMessage: 'slug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'slug must be kebab-case')]
        public ?string $slug = null,
    ) {
    }
}
