<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Http;

use DateTimeInterface;
use Modules\Agents\Application\CreateAgentCommand;
use Modules\Agents\Application\CreateAgentCommandHandler;
use Modules\Agents\Domain\Agent;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Shared\Kernel\AgentId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class AgentsController
{
    public function __construct(private CreateAgentCommandHandler $create)
    {
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
            instructions: $input->instructions,
            slug: $input->slug,
        ));

        return new JsonResponse($this->serialise($agent), Response::HTTP_CREATED);
    }

    /**
     * @return array{id: string, organizationId: string, ownerUserId: string, sourceTemplateId: ?string, slug: string, name: string, role: string, instructions: string, isActive: bool, createdAt: string, updatedAt: string}
     */
    private function serialise(Agent $agent): array
    {
        return [
            'id' => (string) $agent->id(),
            'organizationId' => (string) $agent->organizationId(),
            'ownerUserId' => (string) $agent->ownerUserId(),
            'sourceTemplateId' => $agent->sourceTemplateId() instanceof AgentId ? (string) $agent->sourceTemplateId() : null,
            'slug' => $agent->slug(),
            'name' => $agent->name(),
            'role' => $agent->role(),
            'instructions' => $agent->instructions(),
            'isActive' => $agent->isActive(),
            'createdAt' => $agent->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $agent->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
