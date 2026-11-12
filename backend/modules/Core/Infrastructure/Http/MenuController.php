<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Application\Menu\MenuAssembler;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class MenuController
{
    public function __construct(private MenuAssembler $menu)
    {
    }

    #[Route('/api/menu', name: 'core_menu', methods: ['GET'])]
    #[OA\Get(
        path: '/api/menu',
        summary: 'Return the navigation menu for the current user. Admin sees every section.',
        tags: ['core'],
        responses: [
            new OA\Response(response: 200, description: 'Menu sections scoped to the user'),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ],
    )]
    public function show(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $domain = $user->domainUser();
        $authUser = new AuthUser($domain->id(), $domain->email(), $domain->createdAt(), $domain->displayName());
        $sections = $this->menu->assemble($authUser, $domain->role());

        return new JsonResponse(['sections' => array_map($this->serialiseSection(...), $sections)]);
    }

    /**
     * @return array{id: string, labelKey: string, order: int, items: list<array<string, mixed>>}
     */
    private function serialiseSection(MenuSection $section): array
    {
        return [
            'id' => $section->id,
            'labelKey' => $section->labelKey,
            'order' => $section->order,
            'items' => array_map($this->serialiseItem(...), $section->items),
        ];
    }

    /**
     * @return array{id: string, labelKey: string, icon: string, path: string, order: int, disabledMilestoneKey: ?string}
     */
    private function serialiseItem(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'labelKey' => $item->labelKey,
            'icon' => $item->icon,
            'path' => $item->path,
            'order' => $item->order,
            'disabledMilestoneKey' => $item->disabledMilestoneKey,
        ];
    }
}
