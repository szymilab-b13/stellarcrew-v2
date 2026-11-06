<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Http;

use Modules\Workspaces\Domain\Workspace;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkspaceRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'name is required')]
        #[Assert\Length(max: Workspace::NAME_MAX, maxMessage: 'name cannot exceed {{ limit }} characters')]
        public string $name,
        #[Assert\Choice(choices: Workspace::ICON_COLORS, message: 'iconColor must be one of the supported palette values')]
        public ?string $iconColor = null,
        #[Assert\Length(max: Workspace::INITIALS_MAX, maxMessage: 'iconInitials cannot exceed {{ limit }} characters')]
        public ?string $iconInitials = null,
    ) {
    }
}
