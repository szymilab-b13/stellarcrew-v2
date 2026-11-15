<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\AgentTemplateId;

use function preg_match;
use function sprintf;
use function trim;

class AgentTemplate
{
    public const int SLUG_MAX = 100;

    public const int NAME_MAX = 100;

    public const int ROLE_MAX = 120;

    public const int ROLE_DESCRIPTION_MAX = 500;

    public const int SECTION_MAX = 2000;

    public const string SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    public function __construct(
        private readonly AgentTemplateId $id,
        private readonly string $slug,
        private readonly string $name,
        private readonly string $role,
        private readonly string $roleDescription,
        private readonly string $expertise,
        private readonly string $workflow,
        private readonly string $style,
        private readonly string $outputFormat,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        $this->guardSlug($slug);
        $this->guardName($name);
        $this->guardRole($role);
        $this->guardRoleDescription($roleDescription);
        $this->guardSection('expertise', $expertise);
        $this->guardSection('workflow', $workflow);
        $this->guardSection('style', $style);
        $this->guardSection('output format', $outputFormat);
    }

    public static function create(
        AgentTemplateId $id,
        string $slug,
        string $name,
        string $role,
        string $roleDescription,
        string $expertise,
        string $workflow,
        string $style,
        string $outputFormat,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $slug,
            $name,
            $role,
            $roleDescription,
            $expertise,
            $workflow,
            $style,
            $outputFormat,
            $now,
            $now,
        );
    }

    public function id(): AgentTemplateId
    {
        return $this->id;
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

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
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

    private function guardSlug(string $slug): void
    {
        if ($slug === '') {
            throw new InvalidArgumentException('Agent template slug cannot be empty.');
        }

        if (mb_strlen($slug) > self::SLUG_MAX) {
            throw new InvalidArgumentException(sprintf('Agent template slug cannot exceed %d characters.', self::SLUG_MAX));
        }

        if (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            throw new InvalidArgumentException(sprintf('Agent template slug must be kebab-case: "%s".', $slug));
        }
    }

    private function guardName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent template name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::NAME_MAX) {
            throw new InvalidArgumentException(sprintf('Agent template name cannot exceed %d characters.', self::NAME_MAX));
        }
    }

    private function guardRole(string $role): void
    {
        $trimmed = trim($role);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent template role cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::ROLE_MAX) {
            throw new InvalidArgumentException(sprintf('Agent template role cannot exceed %d characters.', self::ROLE_MAX));
        }
    }

    private function guardRoleDescription(string $value): void
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Agent template role description cannot be empty.');
        }

        if (mb_strlen($value) > self::ROLE_DESCRIPTION_MAX) {
            throw new InvalidArgumentException(
                sprintf('Agent template role description cannot exceed %d characters.', self::ROLE_DESCRIPTION_MAX),
            );
        }
    }

    private function guardSection(string $label, string $value): void
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException(sprintf('Agent template %s cannot be empty.', $label));
        }

        if (mb_strlen($value) > self::SECTION_MAX) {
            throw new InvalidArgumentException(
                sprintf('Agent template %s cannot exceed %d characters.', $label, self::SECTION_MAX),
            );
        }
    }
}
