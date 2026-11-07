<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AssignWorkspaceMemberRequest
{
    public const array ASSIGNABLE_ROLES = ['admin', 'member', 'guest'];

    public function __construct(
        #[Assert\NotBlank(message: 'workspaceId is required')]
        #[Assert\Uuid(message: 'workspaceId must be a valid UUID')]
        public string $workspaceId,
        #[Assert\NotBlank(message: 'role is required')]
        #[Assert\Choice(choices: self::ASSIGNABLE_ROLES, message: 'role must be one of: admin, member, guest')]
        public string $role,
    ) {
    }
}
