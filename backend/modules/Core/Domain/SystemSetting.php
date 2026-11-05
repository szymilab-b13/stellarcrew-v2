<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

use DateTimeImmutable;

final class SystemSetting
{
    public function __construct(private readonly string $name, private ?string $value, private DateTimeImmutable $updatedAt)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeValue(?string $value, DateTimeImmutable $now): void
    {
        $this->value = $value;
        $this->updatedAt = $now;
    }
}
