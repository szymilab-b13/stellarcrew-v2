<?php

declare(strict_types=1);

namespace Modules\Organizations\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

use function preg_match;
use function sprintf;
use function trim;

class Organization
{
    public const int NAME_MAX = 100;

    public const int SLUG_MAX = 120;

    public function __construct(
        private readonly OrganizationId $id,
        private string $name,
        private string $slug,
        private readonly UserId $ownerUserId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->guardName($name);
        $this->guardSlug($slug);
    }

    public static function create(
        OrganizationId $id,
        string $name,
        string $slug,
        UserId $ownerUserId,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $name,
            $slug,
            $ownerUserId,
            $now,
            $now,
        );
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function ownerUserId(): UserId
    {
        return $this->ownerUserId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt instanceof DateTimeImmutable;
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        $this->guardName($name);
        $this->name = $name;
        $this->updatedAt = $now;
    }

    public function changeSlug(string $slug, DateTimeImmutable $now): void
    {
        $this->guardSlug($slug);
        $this->slug = $slug;
        $this->updatedAt = $now;
    }

    public function softDelete(DateTimeImmutable $now): void
    {
        if ($this->deletedAt instanceof DateTimeImmutable) {
            return;
        }

        $this->deletedAt = $now;
        $this->updatedAt = $now;
    }

    public function restore(DateTimeImmutable $now): void
    {
        if (!$this->deletedAt instanceof DateTimeImmutable) {
            return;
        }

        $this->deletedAt = null;
        $this->updatedAt = $now;
    }

    private function guardName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Organization name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::NAME_MAX) {
            throw new InvalidArgumentException(sprintf('Organization name cannot exceed %d characters.', self::NAME_MAX));
        }
    }

    private function guardSlug(string $slug): void
    {
        if ($slug === '') {
            throw new InvalidArgumentException('Organization slug cannot be empty.');
        }

        if (mb_strlen($slug) > self::SLUG_MAX) {
            throw new InvalidArgumentException(sprintf('Organization slug cannot exceed %d characters.', self::SLUG_MAX));
        }

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('Organization slug must be kebab-case: "%s".', $slug));
        }
    }
}
