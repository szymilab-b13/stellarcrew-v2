<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Http;

use DateTimeInterface;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Organizations\Application\CreateOrganizationCommand;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Domain\Organization;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class OrganizationsController
{
    public function __construct(
        private CreateOrganizationCommandHandler $create,
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
