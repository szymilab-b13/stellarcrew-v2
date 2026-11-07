<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetPasswordRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'newPassword is required')]
        public string $newPassword = '',
    ) {
    }
}
