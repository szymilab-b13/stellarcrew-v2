<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Http;

use DateTimeInterface;
use Modules\Agents\Api\AgentColorPalette;
use Modules\Agents\Application\CreateAgentCommand;
use Modules\Agents\Application\CreateAgentCommandHandler;
use Modules\Agents\Application\CreateAgentFromTemplateCommand;
use Modules\Agents\Application\CreateAgentFromTemplateCommandHandler;
use Modules\Agents\Application\DeleteAgentCommand;
use Modules\Agents\Application\DeleteAgentCommandHandler;
use Modules\Agents\Application\Query\GetAgentQuery;
use Modules\Agents\Application\Query\GetAgentQueryHandler;
use Modules\Agents\Application\Query\ListAgentsQuery;
use Modules\Agents\Application\Query\ListAgentsQueryHandler;
use Modules\Agents\Application\UpdateAgentCommand;
use Modules\Agents\Application\UpdateAgentCommandHandler;
use Modules\Agents\Domain\Agent;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class AgentsController
{
    private const string AGENT_ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private CreateAgentCommandHandler $create,
        private CreateAgentFromTemplateCommandHandler $createFromTemplate,
        private ListAgentsQueryHandler $list,
        private GetAgentQueryHandler $get,
        private UpdateAgentCommandHandler $update,
        private DeleteAgentCommandHandler $delete,
    ) {
    }

    #[Route('/api/agents', name: 'agents_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/agents',
        summary: 'Create a new agent owned by the current user inside their organization.',
        tags: ['agents'],
        responses: [
            new OA\Response(response: 201, description: 'Agent created'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 409, description: 'Owner has no organization, agent limit reached, or slug already taken'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function create(
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        CreateAgentRequest $input,
    ): JsonResponse {
        $agent = ($this->create)(new CreateAgentCommand(
            actorId: $user->domainUser()->id(),
            name: $input->name,
            role: $input->role,
            roleDescription: $input->roleDescription,
            expertise: $input->expertise,
            workflow: $input->workflow,
            style: $input->style,
            outputFormat: $input->outputFormat,
            slug: $input->slug,
        ));

        return new JsonResponse($this->serialise($agent), Response::HTTP_CREATED);
    }

    #[Route('/api/agents/from-template', name: 'agents_create_from_template', methods: ['POST'])]
    #[OA\Post(
        path: '/api/agents/from-template',
        summary: 'Create a new agent by deep-copying a predefined template, with optional per-field overrides.',
        tags: ['agents'],
        responses: [
            new OA\Response(response: 201, description: 'Agent created from template'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 404, description: 'Template not found'),
            new OA\Response(response: 409, description: 'Owner has no organization, agent limit reached, or slug already taken'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function createFromTemplate(
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        CreateAgentFromTemplateRequest $input,
    ): JsonResponse {
        $agent = ($this->createFromTemplate)(new CreateAgentFromTemplateCommand(
            actorId: $user->domainUser()->id(),
            templateSlug: $input->templateSlug,
            name: $input->name,
            role: $input->role,
            roleDescription: $input->roleDescription,
            expertise: $input->expertise,
            workflow: $input->workflow,
            style: $input->style,
            outputFormat: $input->outputFormat,
            slug: $input->slug,
        ));

        return new JsonResponse($this->serialise($agent), Response::HTTP_CREATED);
    }

    #[Route('/api/agents', name: 'agents_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/agents',
        summary: 'List agents the current user owns or has been granted access to.',
        tags: ['agents'],
        responses: [
            new OA\Response(response: 200, description: 'List of agents accessible to the current user'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function list(
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $agents = ($this->list)(new ListAgentsQuery(actorId: $user->domainUser()->id()));

        return new JsonResponse(array_map($this->serialise(...), $agents));
    }

    #[Route(
        '/api/agents/{id}',
        name: 'agents_get',
        requirements: ['id' => self::AGENT_ID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/agents/{id}',
        summary: 'Get a single agent by id (requires AgentMember membership).',
        tags: ['agents'],
        responses: [
            new OA\Response(response: 200, description: 'Agent'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a member of the agent'),
            new OA\Response(response: 404, description: 'Agent not found'),
        ],
    )]
    public function get(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $agent = ($this->get)(new GetAgentQuery(
            actorId: $user->domainUser()->id(),
            agentId: AgentId::fromString($id),
        ));

        return new JsonResponse($this->serialise($agent), Response::HTTP_OK);
    }

    #[Route(
        '/api/agents/{id}',
        name: 'agents_update',
        requirements: ['id' => self::AGENT_ID_REQUIREMENT],
        methods: ['PUT'],
    )]
    #[OA\Put(
        path: '/api/agents/{id}',
        summary: 'Update an agent owned by the current user (partial: name/role/5 prompt sections).',
        tags: ['agents'],
        responses: [
            new OA\Response(response: 200, description: 'Agent updated'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the agent owner'),
            new OA\Response(response: 404, description: 'Agent not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        UpdateAgentRequest $input,
    ): JsonResponse {
        $agent = ($this->update)(new UpdateAgentCommand(
            actorId: $user->domainUser()->id(),
            agentId: AgentId::fromString($id),
            name: $input->name,
            role: $input->role,
            roleDescription: $input->roleDescription,
            expertise: $input->expertise,
            workflow: $input->workflow,
            style: $input->style,
            outputFormat: $input->outputFormat,
        ));

        return new JsonResponse($this->serialise($agent), Response::HTTP_OK);
    }

    #[Route(
        '/api/agents/{id}',
        name: 'agents_delete',
        requirements: ['id' => self::AGENT_ID_REQUIREMENT],
        methods: ['DELETE'],
    )]
    #[OA\Delete(
        path: '/api/agents/{id}',
        summary: 'Soft-delete an agent owned by the current user (idempotent).',
        tags: ['agents'],
        responses: [
            new OA\Response(response: 204, description: 'Agent soft-deleted'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the agent owner'),
            new OA\Response(response: 404, description: 'Agent not found'),
        ],
    )]
    public function delete(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
    ): Response {
        ($this->delete)(new DeleteAgentCommand(
            actorId: $user->domainUser()->id(),
            agentId: AgentId::fromString($id),
        ));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{id: string, organizationId: string, ownerUserId: string, sourceTemplateId: ?string, slug: string, name: string, role: string, roleDescription: string, expertise: string, workflow: string, style: string, outputFormat: string, isActive: bool, color: string, createdAt: string, updatedAt: string}
     */
    private function serialise(Agent $agent): array
    {
        return [
            'id' => (string) $agent->id(),
            'organizationId' => (string) $agent->organizationId(),
            'ownerUserId' => (string) $agent->ownerUserId(),
            'sourceTemplateId' => $agent->sourceTemplateId() instanceof AgentTemplateId ? (string) $agent->sourceTemplateId() : null,
            'slug' => $agent->slug(),
            'name' => $agent->name(),
            'role' => $agent->role(),
            'roleDescription' => $agent->roleDescription(),
            'expertise' => $agent->expertise(),
            'workflow' => $agent->workflow(),
            'style' => $agent->style(),
            'outputFormat' => $agent->outputFormat(),
            'isActive' => $agent->isActive(),
            'color' => $agent->color() ?? AgentColorPalette::deriveFromSlug($agent->slug()),
            'createdAt' => $agent->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $agent->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
