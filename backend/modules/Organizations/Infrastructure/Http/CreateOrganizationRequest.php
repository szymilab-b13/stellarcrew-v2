<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Http;

use Modules\Organizations\Domain\Organization;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrganizationRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'name is required')]
        #[Assert\Length(max: Organization::NAME_MAX, maxMessage: 'name cannot exceed {{ limit }} characters')]
        public string $name,
        #[Assert\Length(max: Organization::SLUG_MAX, maxMessage: 'slug cannot exceed {{ limit }} characters')]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(-[a-z0-9]+)*$/', message: 'slug must be kebab-case')]
        public ?string $slug = null,
    ) {
    }
}
