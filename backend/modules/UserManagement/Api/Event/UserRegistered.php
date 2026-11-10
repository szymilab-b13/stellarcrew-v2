<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;

final readonly class UserRegistered
{
    public function __construct(
        public UserId $userId,
        public string $email,
        public ?string $displayName,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
