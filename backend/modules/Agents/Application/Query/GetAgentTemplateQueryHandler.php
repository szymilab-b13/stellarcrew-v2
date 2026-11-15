<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;
use Modules\Shared\Kernel\AgentTemplateId;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class GetAgentTemplateQueryHandler
{
    public function __construct(private AgentTemplateRepository $templates)
    {
    }

    public function __invoke(GetAgentTemplateQuery $query): AgentTemplate
    {
        $template = Uuid::isValid($query->idOrSlug)
            ? $this->templates->findById(AgentTemplateId::fromString($query->idOrSlug))
            : $this->templates->findBySlug($query->idOrSlug);

        if (!$template instanceof AgentTemplate) {
            throw new NotFoundHttpException(sprintf('Agent template "%s" not found.', $query->idOrSlug));
        }

        return $template;
    }
}
