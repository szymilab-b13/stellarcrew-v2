<?php

declare(strict_types=1);

namespace Modules\Agents\Api;

use Modules\Agents\Api\Dto\AgentTemplateView;
use Modules\Shared\Kernel\AgentTemplateId;

interface AgentTemplatesApi
{
    public function findById(AgentTemplateId $id): ?AgentTemplateView;

    public function findBySlug(string $slug): ?AgentTemplateView;

    /**
     * @return list<AgentTemplateView>
     */
    public function listAll(): array;
}
