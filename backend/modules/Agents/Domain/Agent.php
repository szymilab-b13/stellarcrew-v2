<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

use function preg_match;
use function sprintf;
use function trim;

class Agent
{
    public const int SLUG_MAX = 80;

    public const int NAME_MAX = 80;

    public const int ROLE_MAX = 120;

    public const int INSTRUCTIONS_MAX = 8000;

    public const string SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    public function __construct(
        private readonly AgentId $id,
        private readonly OrganizationId $organizationId,
        private readonly UserId $ownerUserId,
        private readonly ?AgentId $sourceTemplateId,
        private readonly string $slug,
        private string $name,
        private string $role,
        private string $instructions,
        private bool $isActive,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->guardSlug($slug);
        $this->guardName($name);
        $this->guardRole($role);
        $this->guardInstructions($instructions);
    }

    public static function create(
        AgentId $id,
        OrganizationId $organizationId,
        UserId $ownerUserId,
        string $slug,
        string $name,
        string $role,
        string $instructions,
        DateTimeImmutable $now,
        ?AgentId $sourceTemplateId = null,
    ): self {
        return new self(
            $id,
            $organizationId,
            $ownerUserId,
            $sourceTemplateId,
            $slug,
            $name,
            $role,
            $instructions,
            true,
            $now,
            $now,
        );
    }

    public function id(): AgentId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function ownerUserId(): UserId
    {
        return $this->ownerUserId;
    }

    public function sourceTemplateId(): ?AgentId
    {
        return $this->sourceTemplateId;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function instructions(): string
    {
        return $this->instructions;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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

    public function changeRole(string $role, DateTimeImmutable $now): void
    {
        $this->guardRole($role);
        $this->role = $role;
        $this->updatedAt = $now;
    }

    public function updateInstructions(string $instructions, DateTimeImmutable $now): void
    {
        $this->guardInstructions($instructions);
        $this->instructions = $instructions;
        $this->updatedAt = $now;
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->isActive) {
            return;
        }

        $this->isActive = true;
        $this->updatedAt = $now;
    }

    public function deactivate(DateTimeImmutable $now): void
    {
        if (!$this->isActive) {
            return;
        }

        $this->isActive = false;
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

    private function guardSlug(string $slug): void
    {
        if ($slug === '') {
            throw new InvalidArgumentException('Agent slug cannot be empty.');
        }

        if (mb_strlen($slug) > self::SLUG_MAX) {
            throw new InvalidArgumentException(sprintf('Agent slug cannot exceed %d characters.', self::SLUG_MAX));
        }

        if (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('Agent slug must be kebab-case: "%s".', $slug));
        }
    }

    private function guardName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::NAME_MAX) {
            throw new InvalidArgumentException(sprintf('Agent name cannot exceed %d characters.', self::NAME_MAX));
        }
    }

    private function guardRole(string $role): void
    {
        $trimmed = trim($role);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent role cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::ROLE_MAX) {
            throw new InvalidArgumentException(sprintf('Agent role cannot exceed %d characters.', self::ROLE_MAX));
        }
    }

    private function guardInstructions(string $instructions): void
    {
        $trimmed = trim($instructions);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent instructions cannot be empty.');
        }

        if (mb_strlen($instructions) > self::INSTRUCTIONS_MAX) {
            throw new InvalidArgumentException(
                sprintf('Agent instructions cannot exceed %d characters.', self::INSTRUCTIONS_MAX),
            );
        }
    }
}
