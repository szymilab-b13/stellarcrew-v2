<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Http;

use function array_map;
use function array_values;

use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Dto\WorkspaceView;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;
use Modules\Workspaces\Api\WorkspacesApi;
use Modules\Workspaces\Application\CreateWorkspaceCommand;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Application\DeleteWorkspaceCommand;
use Modules\Workspaces\Application\DeleteWorkspaceCommandHandler;
use Modules\Workspaces\Application\UpdateWorkspaceCommand;
use Modules\Workspaces\Application\UpdateWorkspaceCommandHandler;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class WorkspacesController
{
    private const string ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private WorkspacesApi $workspaces,
        private OrganizationsApi $organizations,
        private CreateWorkspaceCommandHandler $create,
        private UpdateWorkspaceCommandHandler $update,
        private DeleteWorkspaceCommandHandler $delete,
    ) {
    }

    #[Route('/api/workspaces', name: 'workspaces_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/workspaces',
        summary: 'List workspaces where the current user is a member.',
        tags: ['workspaces'],
        responses: [
            new OA\Response(response: 200, description: 'Workspace list'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function list(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $views = $this->workspaces->listForUser($user->domainUser()->id());

        return new JsonResponse(array_values(array_map($this->serialise(...), $views)));
    }

    #[Route('/api/workspaces', name: 'workspaces_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/workspaces',
        summary: 'Create a workspace owned by the current user.',
        tags: ['workspaces'],
        responses: [
            new OA\Response(response: 201, description: 'Workspace created'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function create(
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        CreateWorkspaceRequest $input,
    ): JsonResponse {
        $ownerId = $user->domainUser()->id();
        $organization = $this->organizations->findByOwner($ownerId);
        if (!$organization instanceof OrganizationView) {
            throw OwnerHasNoOrganizationException::forOwner($ownerId);
        }

        $workspace = ($this->create)(new CreateWorkspaceCommand(
            ownerId: $ownerId,
            organizationId: $organization->id,
            name: $input->name,
            iconColor: $input->iconColor,
            iconInitials: $input->iconInitials,
        ));

        $view = $this->workspaces->findById($workspace->id());

        return new JsonResponse($view instanceof WorkspaceView ? $this->serialise($view) : null, Response::HTTP_CREATED);
    }

    #[Route('/api/workspaces/{id}', name: 'workspaces_update', requirements: ['id' => self::ID_REQUIREMENT], methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/workspaces/{id}',
        summary: 'Rename or re-skin a workspace (owner or admin).',
        tags: ['workspaces'],
        responses: [
            new OA\Response(response: 200, description: 'Workspace updated'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not owner or admin'),
            new OA\Response(response: 404, description: 'Workspace not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        UpdateWorkspaceRequest $input,
    ): JsonResponse {
        $workspace = ($this->update)(new UpdateWorkspaceCommand(
            workspaceId: WorkspaceId::fromString($id),
            actorId: $user->domainUser()->id(),
            name: $input->name,
            iconColor: $input->iconColor,
            iconInitials: $input->iconInitials,
        ));

        $view = $this->workspaces->findById($workspace->id());

        return new JsonResponse($view instanceof WorkspaceView ? $this->serialise($view) : null);
    }

    #[Route('/api/workspaces/{id}', name: 'workspaces_delete', requirements: ['id' => self::ID_REQUIREMENT], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/workspaces/{id}',
        summary: 'Soft-delete a workspace (owner only).',
        tags: ['workspaces'],
        responses: [
            new OA\Response(response: 204, description: 'Workspace deleted'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not owner'),
            new OA\Response(response: 404, description: 'Workspace not found'),
        ],
    )]
    public function delete(string $id, #[CurrentUser] SecurityUser $user): JsonResponse
    {
        ($this->delete)(new DeleteWorkspaceCommand(
            workspaceId: WorkspaceId::fromString($id),
            actorId: $user->domainUser()->id(),
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{id: string, name: string, slug: string, iconColor: string, iconInitials: string, ownerId: string, role?: string}
     */
    private function serialise(WorkspaceView $view): array
    {
        $payload = [
            'id' => $view->id,
            'name' => $view->name,
            'slug' => $view->slug,
            'iconColor' => $view->iconColor,
            'iconInitials' => $view->iconInitials,
            'ownerId' => $view->ownerId,
        ];

        if ($view->role !== null) {
            $payload['role'] = $view->role;
        }

        return $payload;
    }
}
