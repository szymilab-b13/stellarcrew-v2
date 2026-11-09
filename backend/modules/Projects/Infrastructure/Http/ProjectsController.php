<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Http;

use function array_map;
use function array_values;

use DateTimeInterface;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Projects\Application\CreateProjectCommand;
use Modules\Projects\Application\CreateProjectCommandHandler;
use Modules\Projects\Application\Query\ListProjectsByWorkspaceQuery;
use Modules\Projects\Application\Query\ListProjectsByWorkspaceQueryHandler;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\WorkspaceId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class ProjectsController
{
    private const string WORKSPACE_ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private CreateProjectCommandHandler $create,
        private ListProjectsByWorkspaceQueryHandler $listByWorkspace,
    ) {
    }

    #[Route(
        '/api/workspaces/{workspaceId}/projects',
        name: 'projects_create',
        requirements: ['workspaceId' => self::WORKSPACE_ID_REQUIREMENT],
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/workspaces/{workspaceId}/projects',
        summary: 'Create a project inside the given workspace.',
        tags: ['projects'],
        responses: [
            new OA\Response(response: 201, description: 'Project created'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not a workspace member allowed to create projects'),
            new OA\Response(response: 404, description: 'Workspace not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function create(
        string $workspaceId,
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        CreateProjectRequest $input,
    ): JsonResponse {
        $project = ($this->create)(new CreateProjectCommand(
            actorId: $user->domainUser()->id(),
            workspaceId: WorkspaceId::fromString($workspaceId),
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
        ));

        return new JsonResponse($this->serialise($project), Response::HTTP_CREATED);
    }

    #[Route(
        '/api/workspaces/{workspaceId}/projects',
        name: 'projects_list_by_workspace',
        requirements: ['workspaceId' => self::WORKSPACE_ID_REQUIREMENT],
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/workspaces/{workspaceId}/projects',
        summary: 'List projects of the given workspace (ordered by creation time ascending).',
        tags: ['projects'],
        responses: [
            new OA\Response(response: 200, description: 'Projects list'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not allowed to view projects'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ],
    )]
    public function list(
        string $workspaceId,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $projects = ($this->listByWorkspace)(new ListProjectsByWorkspaceQuery(
            actorId: $user->domainUser()->id(),
            workspaceId: WorkspaceId::fromString($workspaceId),
        ));

        $items = array_values(array_map($this->serialise(...), $projects));

        return new JsonResponse(['items' => $items], Response::HTTP_OK);
    }

    /**
     * @return array{id: string, workspaceId: string, name: string, slug: string, description: ?string, createdAt: string, updatedAt: string}
     */
    private function serialise(Project $project): array
    {
        return [
            'id' => (string) $project->id(),
            'workspaceId' => (string) $project->workspaceId(),
            'name' => $project->name(),
            'slug' => $project->slug(),
            'description' => $project->description(),
            'createdAt' => $project->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $project->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
