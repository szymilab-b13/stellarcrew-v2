<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Http;

use Modules\UserManagement\Domain\UserProfile;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserRequest
{
    public const int PASSWORD_MIN = 12;

    public function __construct(
        #[Assert\NotBlank(message: 'email is required')]
        #[Assert\Email(message: 'email must be a valid email address')]
        public string $email,
        #[Assert\NotBlank(message: 'password is required')]
        #[Assert\Length(
            min: self::PASSWORD_MIN,
            minMessage: 'password must be at least {{ limit }} characters',
        )]
        public string $password,
        public bool $isSuperAdmin = false,
        #[Assert\Length(
            max: UserProfile::DISPLAY_NAME_MAX,
            maxMessage: 'displayName cannot exceed {{ limit }} characters',
        )]
        public ?string $displayName = null,
    ) {
    }
}
