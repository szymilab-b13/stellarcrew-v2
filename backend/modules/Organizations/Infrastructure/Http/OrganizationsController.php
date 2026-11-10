<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Http;

use DateTimeInterface;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Organizations\Application\CreateOrganizationCommand;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Application\Query\GetMyOrganizationQuery;
use Modules\Organizations\Application\Query\GetMyOrganizationQueryHandler;
use Modules\Organizations\Application\UpdateOrganizationCommand;
use Modules\Organizations\Application\UpdateOrganizationCommandHandler;
use Modules\Organizations\Domain\Organization;
use Modules\Shared\Kernel\OrganizationId;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class OrganizationsController
{
    private const string ORGANIZATION_ID_REQUIREMENT = '[0-9a-fA-F-]{36}';

    public function __construct(
        private CreateOrganizationCommandHandler $create,
        private UpdateOrganizationCommandHandler $update,
        private GetMyOrganizationQueryHandler $getMy,
    ) {
    }

    #[Route(
        '/api/organizations',
        name: 'organizations_create',
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/organizations',
        summary: 'Create an organization owned by the current user. Manual fallback when the user has no organization.',
        tags: ['organizations'],
        responses: [
            new OA\Response(response: 201, description: 'Organization created'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 409, description: 'Current user already owns an organization'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function create(
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        CreateOrganizationRequest $input,
    ): JsonResponse {
        $organization = ($this->create)(new CreateOrganizationCommand(
            ownerUserId: $user->domainUser()->id(),
            name: $input->name,
            slug: $input->slug,
        ));

        return new JsonResponse($this->serialise($organization), Response::HTTP_CREATED);
    }

    #[Route(
        '/api/organizations/{id}',
        name: 'organizations_update',
        requirements: ['id' => self::ORGANIZATION_ID_REQUIREMENT],
        methods: ['PUT'],
    )]
    #[OA\Put(
        path: '/api/organizations/{id}',
        summary: 'Update an organization name or slug. Owner-only.',
        tags: ['organizations'],
        responses: [
            new OA\Response(response: 200, description: 'Organization updated'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the owner of the organization'),
            new OA\Response(response: 404, description: 'Organization not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        UpdateOrganizationRequest $input,
    ): JsonResponse {
        $organization = ($this->update)(new UpdateOrganizationCommand(
            actorId: $user->domainUser()->id(),
            organizationId: OrganizationId::fromString($id),
            name: $input->name,
            slug: $input->slug,
        ));

        return new JsonResponse($this->serialise($organization), Response::HTTP_OK);
    }

    #[Route(
        '/api/my/organization',
        name: 'organizations_get_my',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/my/organization',
        summary: 'Fetch the organization owned by the current user.',
        tags: ['organizations'],
        responses: [
            new OA\Response(response: 200, description: 'Organization returned'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 404, description: 'Current user does not own an organization'),
        ],
    )]
    public function getMy(
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $organization = ($this->getMy)(new GetMyOrganizationQuery(
            actorId: $user->domainUser()->id(),
        ));

        return new JsonResponse($this->serialise($organization), Response::HTTP_OK);
    }

    /**
     * @return array{id: string, name: string, slug: string, ownerUserId: string, createdAt: string, updatedAt: string}
     */
    private function serialise(Organization $organization): array
    {
        return [
            'id' => (string) $organization->id(),
            'name' => $organization->name(),
            'slug' => $organization->slug(),
            'ownerUserId' => (string) $organization->ownerUserId(),
            'createdAt' => $organization->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $organization->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
