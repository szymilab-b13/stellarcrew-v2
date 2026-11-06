<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Http;

use Modules\Workspaces\Domain\Workspace;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateWorkspaceRequest
{
    public function __construct(
        #[Assert\Length(min: 1, max: Workspace::NAME_MAX, minMessage: 'name cannot be empty', maxMessage: 'name cannot exceed {{ limit }} characters')]
        public ?string $name = null,
        #[Assert\Choice(choices: Workspace::ICON_COLORS, message: 'iconColor must be one of the supported palette values')]
        public ?string $iconColor = null,
        #[Assert\Length(min: 1, max: Workspace::INITIALS_MAX, minMessage: 'iconInitials cannot be empty', maxMessage: 'iconInitials cannot exceed {{ limit }} characters')]
        public ?string $iconInitials = null,
    ) {
    }
}
