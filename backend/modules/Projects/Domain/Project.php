<?php

declare(strict_types=1);

namespace Modules\Projects\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

use function preg_match;
use function sprintf;
use function trim;

class Project
{
    public const int NAME_MAX = 120;

    public const int SLUG_MAX = 120;

    public const int DESCRIPTION_MAX = 500;

    public function __construct(
        private readonly ProjectId $id,
        private readonly WorkspaceId $workspaceId,
        private string $name,
        private string $slug,
        private ?string $description,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->guardName($name);
        $this->guardSlug($slug);
        $this->guardDescription($description);
    }

    public static function create(
        ProjectId $id,
        WorkspaceId $workspaceId,
        string $name,
        string $slug,
        ?string $description,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $workspaceId,
            $name,
            $slug,
            $description,
            $now,
            $now,
        );
    }

    public function id(): ProjectId
    {
        return $this->id;
    }

    public function workspaceId(): WorkspaceId
    {
        return $this->workspaceId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
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

    public function changeDescription(?string $description, DateTimeImmutable $now): void
    {
        $this->guardDescription($description);
        $this->description = $description;
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
            throw new InvalidArgumentException('Project name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::NAME_MAX) {
            throw new InvalidArgumentException(sprintf('Project name cannot exceed %d characters.', self::NAME_MAX));
        }
    }

    private function guardSlug(string $slug): void
    {
        if ($slug === '') {
            throw new InvalidArgumentException('Project slug cannot be empty.');
        }

        if (mb_strlen($slug) > self::SLUG_MAX) {
            throw new InvalidArgumentException(sprintf('Project slug cannot exceed %d characters.', self::SLUG_MAX));
        }

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('Project slug must be kebab-case: "%s".', $slug));
        }
    }

    private function guardDescription(?string $description): void
    {
        if ($description === null) {
            return;
        }

        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            throw new InvalidArgumentException(
                sprintf('Project description cannot exceed %d characters.', self::DESCRIPTION_MAX),
            );
        }
    }
}
