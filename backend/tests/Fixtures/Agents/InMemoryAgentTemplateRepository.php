<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Agents;

use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;
use Modules\Shared\Kernel\AgentTemplateId;

final class InMemoryAgentTemplateRepository implements AgentTemplateRepository
{
    /** @var array<string, AgentTemplate> keyed by template id */
    private array $templates = [];

    public function save(AgentTemplate $template): void
    {
        $this->templates[(string) $template->id()] = $template;
    }

    public function findById(AgentTemplateId $id): ?AgentTemplate
    {
        return $this->templates[(string) $id] ?? null;
    }

    public function findBySlug(string $slug): ?AgentTemplate
    {
        foreach ($this->templates as $template) {
            if ($template->slug() === $slug) {
                return $template;
            }
        }

        return null;
    }

    public function existsSlug(string $slug): bool
    {
        return $this->findBySlug($slug) instanceof AgentTemplate;
    }

    public function listAll(): array
    {
        $result = array_values($this->templates);

        usort($result, static fn (AgentTemplate $a, AgentTemplate $b): int => strcmp($a->name(), $b->name()));

        return $result;
    }
}
