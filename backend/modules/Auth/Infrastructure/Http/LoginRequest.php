<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'email is required')]
        #[Assert\Email(message: 'email must be a valid address')]
        #[Assert\Length(max: 254)]
        public string $email,
        #[Assert\NotBlank(message: 'password is required')]
        #[Assert\Length(max: 4096)]
        public string $password,
    ) {
    }
}
