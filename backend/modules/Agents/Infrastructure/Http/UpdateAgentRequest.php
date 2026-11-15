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
    ) {
    }
}
