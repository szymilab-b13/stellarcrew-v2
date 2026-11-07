<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeWorkspaceMemberRoleRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'role is required')]
        #[Assert\Choice(
            choices: AssignWorkspaceMemberRequest::ASSIGNABLE_ROLES,
            message: 'role must be one of: admin, member, guest',
        )]
        public string $role,
    ) {
    }
}
