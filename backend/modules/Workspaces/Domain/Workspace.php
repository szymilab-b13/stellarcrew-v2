<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;
use function mb_strtoupper;
use function mb_substr;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

use function preg_match;
use function sprintf;
use function trim;

class Workspace
{
    public const int NAME_MAX = 100;

    public const int SLUG_MAX = 120;

    public const int INITIALS_MAX = 3;

    public const string ICON_COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    public function __construct(
        private readonly WorkspaceId $id,
        private string $name,
        private readonly string $slug,
        private string $iconColor,
        private string $iconInitials,
        private readonly UserId $ownerId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->guardName($name);
        $this->guardSlug($slug);
        $this->guardIconColor($iconColor);
        $this->guardInitials($iconInitials);
    }

    public static function create(
        WorkspaceId $id,
        string $name,
        string $slug,
        string $iconColor,
        UserId $ownerId,
        DateTimeImmutable $now,
        ?string $iconInitials = null,
    ): self {
        return new self(
            $id,
            $name,
            $slug,
            $iconColor,
            $iconInitials ?? self::deriveInitials($name),
            $ownerId,
            $now,
            $now,
        );
    }

    public static function deriveInitials(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '--';
        }

        return mb_strtoupper(mb_substr($trimmed, 0, 2));
    }

    public function id(): WorkspaceId
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

    public function iconColor(): string
    {
        return $this->iconColor;
    }

    public function iconInitials(): string
    {
        return $this->iconInitials;
    }

    public function ownerId(): UserId
    {
        return $this->ownerId;
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

    public function changeIcon(string $color, string $initials, DateTimeImmutable $now): void
    {
        $this->guardIconColor($color);
        $this->guardInitials($initials);
        $this->iconColor = $color;
        $this->iconInitials = $initials;
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

    private function guardName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Workspace name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::NAME_MAX) {
            throw new InvalidArgumentException(sprintf('Workspace name cannot exceed %d characters.', self::NAME_MAX));
        }
    }

    private function guardSlug(string $slug): void
    {
        if ($slug === '') {
            throw new InvalidArgumentException('Workspace slug cannot be empty.');
        }

        if (mb_strlen($slug) > self::SLUG_MAX) {
            throw new InvalidArgumentException(sprintf('Workspace slug cannot exceed %d characters.', self::SLUG_MAX));
        }

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('Workspace slug must be kebab-case: "%s".', $slug));
        }
    }

    private function guardIconColor(string $color): void
    {
        if (preg_match(self::ICON_COLOR_PATTERN, $color) !== 1) {
            throw new InvalidArgumentException(sprintf('Workspace icon color must be a hex color (#RRGGBB): "%s".', $color));
        }
    }

    private function guardInitials(string $initials): void
    {
        $trimmed = trim($initials);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Workspace initials cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::INITIALS_MAX) {
            throw new InvalidArgumentException(
                sprintf('Workspace initials cannot exceed %d characters.', self::INITIALS_MAX),
            );
        }
    }
}
