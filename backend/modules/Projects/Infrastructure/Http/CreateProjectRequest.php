<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Http;

use Modules\Projects\Domain\Project;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProjectRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'name is required')]
        #[Assert\Length(max: Project::NAME_MAX, maxMessage: 'name cannot exceed {{ limit }} characters')]
        public string $name,
        #[Assert\Length(max: Project::SLUG_MAX, maxMessage: 'slug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'slug must be kebab-case')]
        public ?string $slug = null,
        #[Assert\Length(max: Project::DESCRIPTION_MAX, maxMessage: 'description cannot exceed {{ limit }} characters')]
        public ?string $description = null,
    ) {
    }
}
