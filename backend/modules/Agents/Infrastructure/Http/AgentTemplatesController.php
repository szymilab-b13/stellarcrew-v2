<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Http;

use DateTimeInterface;
use Modules\Agents\Application\Query\GetAgentTemplateQuery;
use Modules\Agents\Application\Query\GetAgentTemplateQueryHandler;
use Modules\Agents\Application\Query\ListAgentTemplatesQueryHandler;
use Modules\Agents\Domain\AgentTemplate;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AgentTemplatesController
{
    public function __construct(
        private ListAgentTemplatesQueryHandler $list,
        private GetAgentTemplateQueryHandler $get,
    ) {
    }

    #[Route('/api/agent-templates', name: 'agent_templates_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/agent-templates',
        summary: 'List all predefined agent templates (public catalog for authenticated users).',
        tags: ['agent-templates'],
        responses: [
            new OA\Response(response: 200, description: 'List of agent templates ordered by name'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function list(): JsonResponse
    {
        $templates = ($this->list)();

        return new JsonResponse([
            'items' => array_map($this->serialise(...), $templates),
        ], Response::HTTP_OK);
    }

    #[Route('/api/agent-templates/{idOrSlug}', name: 'agent_templates_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/agent-templates/{idOrSlug}',
        summary: 'Fetch a single agent template by UUID or slug.',
        tags: ['agent-templates'],
        responses: [
            new OA\Response(response: 200, description: 'Agent template payload'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 404, description: 'Template not found'),
        ],
    )]
    public function get(string $idOrSlug): JsonResponse
    {
        $template = ($this->get)(new GetAgentTemplateQuery(idOrSlug: $idOrSlug));

        return new JsonResponse($this->serialise($template), Response::HTTP_OK);
    }

    /**
     * @return array<string, string>
     */
    private function serialise(AgentTemplate $template): array
    {
        return [
            'id' => (string) $template->id(),
            'slug' => $template->slug(),
            'name' => $template->name(),
            'role' => $template->role(),
            'roleDescription' => $template->roleDescription(),
            'expertise' => $template->expertise(),
            'workflow' => $template->workflow(),
            'style' => $template->style(),
            'outputFormat' => $template->outputFormat(),
            'createdAt' => $template->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $template->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
