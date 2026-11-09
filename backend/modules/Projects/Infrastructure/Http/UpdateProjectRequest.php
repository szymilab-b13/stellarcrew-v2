<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Http;

use Modules\Projects\Domain\Project;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProjectRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: Project::NAME_MAX, minMessage: 'name cannot be empty', maxMessage: 'name cannot exceed {{ limit }} characters')]
        public ?string $name = null,
        #[Assert\Length(max: Project::SLUG_MAX, maxMessage: 'slug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'slug must be kebab-case')]
        public ?string $slug = null,
        #[Assert\Length(max: Project::DESCRIPTION_MAX, maxMessage: 'description cannot exceed {{ limit }} characters')]
        public ?string $description = null,
    ) {
    }
}
