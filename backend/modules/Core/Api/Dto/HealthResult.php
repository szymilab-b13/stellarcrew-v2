<?php

declare(strict_types=1);

namespace Modules\Core\Api\Dto;

final readonly class HealthResult
{
    private function __construct(
        public string $name,
        public bool $ok,
        public ?string $reason = null,
    ) {
    }

    public static function ok(string $name): self
    {
        return new self($name, true);
    }

    public static function fail(string $name, string $reason): self
    {
        return new self($name, false, $reason);
    }

    public function isOk(): bool
    {
        return $this->ok;
    }
}
