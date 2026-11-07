<?php

declare(strict_types=1);

namespace Modules\UserManagement\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\UserId;

use function preg_match;
use function sprintf;
use function trim;

class UserProfile
{
    public const int DISPLAY_NAME_MAX = 100;

    public const string AVATAR_COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    private ?DateTimeImmutable $deactivatedAt;

    public function __construct(
        private readonly UserId $id,
        private readonly ?string $displayName,
        private readonly string $avatarColor,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deactivatedAt = null,
    ) {
        $this->guardDisplayName($displayName);
        $this->guardAvatarColor($avatarColor);
        $this->deactivatedAt = $deactivatedAt;
    }

    public static function create(
        UserId $id,
        ?string $displayName,
        string $avatarColor,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $displayName,
            $avatarColor,
            $now,
            $now,
        );
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function avatarColor(): string
    {
        return $this->avatarColor;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deactivatedAt(): ?DateTimeImmutable
    {
        return $this->deactivatedAt;
    }

    public function isDeactivated(): bool
    {
        return $this->deactivatedAt instanceof DateTimeImmutable;
    }

    public function deactivate(DateTimeImmutable $now): void
    {
        if ($this->deactivatedAt instanceof DateTimeImmutable) {
            return;
        }

        $this->deactivatedAt = $now;
        $this->updatedAt = $now;
    }

    public function reactivate(DateTimeImmutable $now): void
    {
        if (!$this->deactivatedAt instanceof DateTimeImmutable) {
            return;
        }

        $this->deactivatedAt = null;
        $this->updatedAt = $now;
    }

    private function guardDisplayName(?string $displayName): void
    {
        if ($displayName === null) {
            return;
        }

        $trimmed = trim($displayName);
        if ($trimmed === '') {
            throw new InvalidArgumentException('UserProfile display name cannot be blank when provided.');
        }

        if (mb_strlen($trimmed) > self::DISPLAY_NAME_MAX) {
            throw new InvalidArgumentException(
                sprintf('UserProfile display name cannot exceed %d characters.', self::DISPLAY_NAME_MAX),
            );
        }
    }

    private function guardAvatarColor(string $avatarColor): void
    {
        if (preg_match(self::AVATAR_COLOR_PATTERN, $avatarColor) !== 1) {
            throw new InvalidArgumentException(
                sprintf('UserProfile avatar color must match hex (#RRGGBB): "%s".', $avatarColor),
            );
        }
    }
}
