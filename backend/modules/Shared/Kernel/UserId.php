<?php

declare(strict_types=1);

namespace Modules\Shared\Kernel;

use InvalidArgumentException;

use function sprintf;

use Stringable;
use Symfony\Component\Uid\Uuid;

final readonly class UserId implements Stringable
{
    public function __construct(
        public Uuid $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException(sprintf('Invalid UUID: "%s".', $value));
        }

        return new self(Uuid::fromString($value));
    }

    public static function generate(): self
    {
        return new self(Uuid::v7());
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
