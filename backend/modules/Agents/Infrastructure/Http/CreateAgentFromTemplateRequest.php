<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Http;

use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentTemplate;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAgentFromTemplateRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'templateSlug is required')]
        #[Assert\Length(max: AgentTemplate::SLUG_MAX, maxMessage: 'templateSlug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'templateSlug must be kebab-case')]
        public string $templateSlug,
        #[Assert\Length(min: 1, max: Agent::NAME_MAX, minMessage: 'name cannot be empty', maxMessage: 'name cannot exceed {{ limit }} characters')]
        public ?string $name = null,
        #[Assert\Length(min: 1, max: Agent::ROLE_MAX, minMessage: 'role cannot be empty', maxMessage: 'role cannot exceed {{ limit }} characters')]
        public ?string $role = null,
        #[Assert\Length(min: 1, max: Agent::ROLE_DESCRIPTION_MAX, minMessage: 'roleDescription cannot be empty', maxMessage: 'roleDescription cannot exceed {{ limit }} characters')]
        public ?string $roleDescription = null,
        #[Assert\Length(min: 1, max: Agent::SECTION_MAX, minMessage: 'expertise cannot be empty', maxMessage: 'expertise cannot exceed {{ limit }} characters')]
        public ?string $expertise = null,
        #[Assert\Length(min: 1, max: Agent::SECTION_MAX, minMessage: 'workflow cannot be empty', maxMessage: 'workflow cannot exceed {{ limit }} characters')]
        public ?string $workflow = null,
        #[Assert\Length(min: 1, max: Agent::SECTION_MAX, minMessage: 'style cannot be empty', maxMessage: 'style cannot exceed {{ limit }} characters')]
        public ?string $style = null,
        #[Assert\Length(min: 1, max: Agent::SECTION_MAX, minMessage: 'outputFormat cannot be empty', maxMessage: 'outputFormat cannot exceed {{ limit }} characters')]
        public ?string $outputFormat = null,
        #[Assert\Length(max: Agent::SLUG_MAX, maxMessage: 'slug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'slug must be kebab-case')]
        public ?string $slug = null,
    ) {
    }
}
