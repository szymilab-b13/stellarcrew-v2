<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;

final readonly class ListAgentTemplatesQueryHandler
{
    public function __construct(private AgentTemplateRepository $templates)
    {
    }

    /**
     * @return list<AgentTemplate>
     */
    public function __invoke(): array
    {
        return $this->templates->listAll();
    }
}
