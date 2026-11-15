<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use Modules\Shared\Kernel\AgentTemplateId;

interface AgentTemplateRepository
{
    public function save(AgentTemplate $template): void;

    public function findById(AgentTemplateId $id): ?AgentTemplate;

    public function findBySlug(string $slug): ?AgentTemplate;

    public function existsSlug(string $slug): bool;

    /**
     * @return list<AgentTemplate>
     */
    public function listAll(): array;
}
