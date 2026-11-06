<?php

declare(strict_types=1);

namespace Modules\Auth\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;

final readonly class AuthUser
{
    public function __construct(
        public UserId $id,
        public string $email,
        public DateTimeImmutable $createdAt,
        public ?string $displayName = null,
    ) {
    }
}
