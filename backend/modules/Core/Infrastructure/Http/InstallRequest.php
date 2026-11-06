<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class InstallRequest
{
    /**
     * @param list<string> $agents
     */
    public function __construct(
        #[Assert\NotBlank(message: 'email is required')]
        #[Assert\Email(message: 'email must be a valid address')]
        #[Assert\Length(max: 254)]
        public string $email,
        #[Assert\NotBlank(message: 'password is required')]
        #[Assert\Length(min: 10, minMessage: 'password must be at least {{ limit }} characters long')]
        public string $password,
        #[Assert\Length(max: 100)]
        public ?string $displayName = null,
        #[Assert\Length(max: 100)]
        public ?string $instanceName = null,
        #[Assert\Choice(choices: ['en', 'pl'], message: 'locale must be one of: en, pl')]
        public string $locale = 'en',
        #[Assert\Length(max: 100)]
        public ?string $workspaceName = null,
        #[Assert\All([new Assert\Type(type: 'string'), new Assert\Length(max: 60)])]
        public array $agents = [],
    ) {
    }
}
