<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
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

    public const int ROLE_DESCRIPTION_MAX = 500;

    public const int SECTION_MAX = 2000;

    public const string SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    public const string COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    public function __construct(
        private readonly AgentId $id,
        private readonly OrganizationId $organizationId,
        private readonly UserId $ownerUserId,
        private readonly ?AgentTemplateId $sourceTemplateId,
        private readonly string $slug,
        private string $name,
        private string $role,
        private string $roleDescription,
        private string $expertise,
        private string $workflow,
        private string $style,
        private string $outputFormat,
        private bool $isActive,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
        private ?string $color = null,
    ) {
        $this->guardSlug($slug);
        $this->guardName($name);
        $this->guardRole($role);
        $this->guardRoleDescription($roleDescription);
        $this->guardSection('expertise', $expertise);
        $this->guardSection('workflow', $workflow);
        $this->guardSection('style', $style);
        $this->guardSection('output format', $outputFormat);
        $this->guardColor($color);
    }

    public static function create(
        AgentId $id,
        OrganizationId $organizationId,
        UserId $ownerUserId,
        string $slug,
        string $name,
        string $role,
        string $roleDescription,
        string $expertise,
        string $workflow,
        string $style,
        string $outputFormat,
        DateTimeImmutable $now,
        ?AgentTemplateId $sourceTemplateId = null,
    ): self {
        return new self(
            $id,
            $organizationId,
            $ownerUserId,
            $sourceTemplateId,
            $slug,
            $name,
            $role,
            $roleDescription,
            $expertise,
            $workflow,
            $style,
            $outputFormat,
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

    public function sourceTemplateId(): ?AgentTemplateId
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

    public function roleDescription(): string
    {
        return $this->roleDescription;
    }

    public function expertise(): string
    {
        return $this->expertise;
    }

    public function workflow(): string
    {
        return $this->workflow;
    }

    public function style(): string
    {
        return $this->style;
    }

    public function outputFormat(): string
    {
        return $this->outputFormat;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function assignColor(?string $color, DateTimeImmutable $now): void
    {
        $this->guardColor($color);
        if ($this->color === $color) {
            return;
        }

        $this->color = $color;
        $this->updatedAt = $now;
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

    public function toSystemPrompt(): string
    {
        return sprintf(
            "<role>\n%s\n</role>\n\n<expertise>\n%s\n</expertise>\n\n<workflow>\n%s\n</workflow>\n\n<style>\n%s\n</style>\n\n<output_format>\n%s\n</output_format>",
            $this->roleDescription,
            $this->expertise,
            $this->workflow,
            $this->style,
            $this->outputFormat,
        );
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

    public function updateRoleDescription(string $value, DateTimeImmutable $now): void
    {
        $this->guardRoleDescription($value);
        $this->roleDescription = $value;
        $this->updatedAt = $now;
    }

    public function updateExpertise(string $value, DateTimeImmutable $now): void
    {
        $this->guardSection('expertise', $value);
        $this->expertise = $value;
        $this->updatedAt = $now;
    }

    public function updateWorkflow(string $value, DateTimeImmutable $now): void
    {
        $this->guardSection('workflow', $value);
        $this->workflow = $value;
        $this->updatedAt = $now;
    }

    public function updateStyle(string $value, DateTimeImmutable $now): void
    {
        $this->guardSection('style', $value);
        $this->style = $value;
        $this->updatedAt = $now;
    }

    public function updateOutputFormat(string $value, DateTimeImmutable $now): void
    {
        $this->guardSection('output format', $value);
        $this->outputFormat = $value;
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

    private function guardRoleDescription(string $value): void
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent role description cannot be empty.');
        }

        if (mb_strlen($value) > self::ROLE_DESCRIPTION_MAX) {
            throw new InvalidArgumentException(
                sprintf('Agent role description cannot exceed %d characters.', self::ROLE_DESCRIPTION_MAX),
            );
        }
    }

    private function guardSection(string $label, string $value): void
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException(sprintf('Agent %s cannot be empty.', $label));
        }

        if (mb_strlen($value) > self::SECTION_MAX) {
            throw new InvalidArgumentException(
                sprintf('Agent %s cannot exceed %d characters.', $label, self::SECTION_MAX),
            );
        }
    }

    private function guardColor(?string $color): void
    {
        if ($color === null) {
            return;
        }

        if (preg_match(self::COLOR_PATTERN, $color) !== 1) {
            throw new InvalidArgumentException(sprintf('Agent color must be #RRGGBB hex: "%s".', $color));
        }
    }
}
